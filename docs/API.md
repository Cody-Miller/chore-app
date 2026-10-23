# BeChore API Reference (for AI agents building Home Assistant voice automations)

BeChore is a self-hosted household chore and pet-medication tracker (Laravel 12). This document is everything an agent needs to wire a **Home Assistant voice assistant** to it: what each endpoint does, the exact request and response shapes, every error code and what to say for it, and example Home Assistant configuration.

**What the API can do**

- See what needs doing now: due, upcoming, one-time, and snoozed chores, plus due and upcoming medication doses
- Complete a chore, for anyone in the household, or split 50/50 between two people
- Snooze and unsnooze chores
- Give a pet its medication, with name matching and dose-slot selection done on the server
- List pets, pills, people, and history
- Undo a recent chore completion or dose
- Read stats: chore points per person and missed doses

**What it can't do:** create, edit, or delete chores, pets, or pills; edit past logs; manage tags. Those are web-UI only. Don't guess or invent endpoints.

Every response includes a short English sentence written to be **spoken aloud as is**. Most voice flows only need to make the call and speak `message` (on success) or `error` (on failure).

---

## 1. Connection

| Item | Value |
| --- | --- |
| Base URL | `{BASE_URL}/api` (for example `http://192.168.1.10/api`) |
| Auth | `Authorization: Bearer {TOKEN}`: a Laravel Sanctum personal access token |
| Request bodies | JSON, with `Content-Type: application/json` |
| `Accept` header | Optional. The API always answers in JSON |
| Rate limit | 60 requests per minute per token. Going over returns `429` with a `Retry-After` header |
| Server timezone | `America/New_York`. Timestamps are ISO 8601 with an offset, and `"HH:MM"` values are 24-hour local time |

**Getting a token:** log in to the web UI and go to **Profile → API Tokens**. The token is shown only once, never expires, and can be revoked on the same page.

**Scope:** any valid token can see and act on everything in the household. Nothing is filtered by the token's owner.

**Who gets credit:** actions are credited to a shared **"API User"** account by default, because the voice assistant can't tell who is speaking. Send `by` with a person's name to credit them instead (see §3).

---

## 2. Response format

**Success:**
```json
{ "success": true, "message": "Logged Kage's 8:00 AM dose of Metacam.", "data": { ... } }
```
`message` appears on actions and on list endpoints that summarize (`/summary`, `/chores/due`, `/pills/today`, and so on). Plain listing endpoints (`/chores`, `/pets`, `/users`, ...) usually return only `data`.

**Error:**
```json
{
  "success": false,
  "error": "Luna has more than one medication: Gabapentin or Thyroid Pill. Which one?",
  "code": "ambiguous_pill",
  "candidates": [
    { "name": "Gabapentin", "slug": "gabapentin", "pet": "Luna" },
    { "name": "Thyroid Pill", "slug": "thyroid-pill", "pet": "Luna" }
  ]
}
```

- `error` is a sentence you can speak.
- `code` is a stable identifier to branch on (full list in §6).
- Some errors carry extra keys: `candidates`, `next_time`, `scheduled_times`, `errors`, or `field`.

All errors share this shape, including authentication (`401`), unknown routes (`404`), validation (`422`), and rate limiting (`429`).

**Robust check:** a call succeeded when the HTTP status is 2xx **and** `success === true`. Speak `message` if it's present and `error` otherwise.

The only exception is `GET /api/user`, which returns the raw user object without the `success` wrapper.

**HTTP statuses used:** `200` OK (including dry runs) · `201` something was logged · `401` bad token · `404` not found · `405` wrong method · `409` ambiguous, so ask the user · `422` refused or invalid · `429` rate limited · `500` server error

---

## 3. Names: how pets, pills, chores, and people are matched

**Pets, pills, and chores** are identified by **name or slug**, both in URL paths (`/api/chores/do-the-dishes`, `/api/pets/Kage`) and in body fields (`"pet": "kage"`). URL-encode names in paths; for example, a space becomes `%20`.

The server tries these rules in order and stops at the first one that matches anything:

1. Exact slug (`do-the-dishes`)
2. Exact name, case-insensitive (`do the dishes`)
3. The input converted to a slug (`Do The Dishes!` → `do-the-dishes`)
4. **Whole words** within the name (`dishes` → "Do the Dishes", `litter box` → "Clean Litter Box"). Partial words don't count, so `dish` won't match "Dishes".

The outcome depends on how many items match:

- **One** match: it's used.
- **Several**: `409 ambiguous_<thing>` with a `candidates` list. Ask the user which one; never pick for them.
- **None**: `404 <thing>_not_found`. If there are 10 or fewer possible options, the error lists them in `candidates`, and the `error` sentence suggests them ("Try Kage, Luna or Sunny.").

Only **active** pills exist for the API. Paused or deleted pills and pets are never matched.

**People** go in the fields `by`, `split_with`, `for`, and `user`. A person can be given as a **first name**, a **full name**, or a **numeric id** from `GET /api/users`, all case-insensitive.

- If two people share a first name, you get `409 ambiguous_user`; send the id instead.
- If `by` is omitted, the action is credited to **API User**.
- Only first names are ever returned.

Speech-to-text often mishears names. When you get a `*_not_found` with `candidates`, read the options back to the user and retry with the one they choose.

---

## 4. Time rules

- **Effective day.** "Today" runs from **03:00 to 02:59:59 the next calendar day**. A dose logged at 01:30 Tuesday counts toward **Monday**.
  - A dose scheduled at `"01:00"` is the *last* dose of the effective day, not the first.
- **Dose slots.** Each pill has daily `scheduled_times` (`"HH:MM"`). Logging a dose fills exactly one slot for the current effective day. Each slot has a `status`:
  - `given`: logged today
  - `due`: its time has passed and it hasn't been logged (this includes overdue doses)
  - `upcoming`: later today
- **`at`** (optional on chore completions and doses): when it actually happened.
  - It accepts ISO 8601 (`2026-10-23T08:15:00-04:00`) or plain English the server can parse (`"30 minutes ago"`, `"2 hours ago"`, `"today 8am"`).
  - It must be within the **last 24 hours** and not in the future; otherwise you get `422 invalid_time`.
  - The dose slot is worked out as of that moment.
- **`time`** (optional on doses): which slot to fill. Accepts `"20:00"`, `"8pm"`, `"8:00 PM"`, and similar.
- **`since`** (history filters): any parseable time (`"yesterday"`, `"7 days ago"`, ISO 8601).

---

## 5. Endpoints

### Overview

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/api/user` | Auth check (raw user, no wrapper) |
| GET | `/api/users` | Household members (`id`, `first_name`) |
| GET | `/api/summary` | Everything due or coming up, chores and meds, in one call |
| GET | `/api/stats` | Chore points per person, doses given, missed doses |
| GET | `/api/chores` | All chores, with filters |
| GET | `/api/chores/due` | Chores that are due |
| GET | `/api/chores/upcoming` | Chores due within the next 3 days |
| GET | `/api/chores/one-time` | One-time chores |
| GET | `/api/chores/snoozed` | Snoozed chores |
| GET | `/api/chores/{chore}` | One chore, with its 10 most recent completions |
| POST | `/api/chores/complete` | Complete a chore named in the body |
| POST | `/api/chores/{chore}/complete` | Complete a chore named in the path |
| POST | `/api/chores/{chore}/snooze` | Snooze a chore |
| DELETE | `/api/chores/{chore}/snooze` | Unsnooze a chore |
| GET | `/api/chore-logs` | Chore completion history |
| DELETE | `/api/chore-logs/{id\|last}` | Undo a completion (within 24 hours) |
| GET | `/api/pets` | All pets with their pills and today's doses |
| GET | `/api/pets/{pet}` | One pet |
| GET | `/api/pets/{pet}/pills` | One pet's pills (older endpoint, still supported) |
| GET | `/api/pills` | All pills, with filters |
| GET | `/api/pills/due` | Doses due now, across all pets |
| GET | `/api/pills/upcoming` | Doses still to come today |
| GET | `/api/pills/today` | Today's full dose schedule |
| GET | `/api/pills/{pill}` | One pill, with its 10 most recent doses |
| POST | `/api/pills/give` | Give medication (the server works out the pill and slot) |
| POST | `/api/pills/{pill}/give` | Give a pill named in the path |
| POST | `/api/record-medication` | Older name for `/api/pills/give` |
| GET | `/api/pill-logs` | Dose history |
| DELETE | `/api/pill-logs/{id\|last}` | Undo a dose (within 24 hours) |

---

### 5.1 People, summary, and stats

#### `GET /api/users`
```json
{ "success": true, "data": [ { "id": 2, "first_name": "Cody" }, { "id": 3, "first_name": "Sarah" } ] }
```
The shared API User is not listed.

#### `GET /api/summary?for={person}`
The best single call for "what needs doing?".
- `for` (optional): limit chores to that person's plus unassigned ones. The default is everyone's.

```json
{
  "success": true,
  "message": "2 chores are due: Do the Dishes and Scoop Litter. 1 medication dose is due: Kage's Metacam at 8:00 AM. Coming up: 1 chore and 2 medication doses.",
  "data": {
    "chores": {
      "due": [ /* chore objects */ ],
      "upcoming": [ /* chore objects */ ],
      "one_time": [ /* chore objects */ ],
      "snoozed_count": 1
    },
    "pills": {
      "due": [ /* dose objects */ ],
      "upcoming": [ /* dose objects */ ],
      "given_today": 3
    }
  }
}
```
When nothing is due, the message reads like "No chores are due. No medication doses are due."

#### `GET /api/stats?period=week|month|year`
- `week` (the default) is the current calendar week, Monday to Sunday.
- `month` and `year` are the past 30 days and 365 days.
- Missed doses are only counted up to the end of yesterday.

```json
{
  "success": true,
  "message": "This week, Sarah has the most chore points with 18, then Cody with 12. 2 medication doses were missed.",
  "data": {
    "period": "week",
    "start": "2026-10-19T00:00:00-04:00",
    "end": "2026-10-25T23:59:59-04:00",
    "chore_points": [ { "first_name": "Sarah", "points": 18 }, { "first_name": "Cody", "points": 12 } ],
    "doses_given": [ { "first_name": "Cody", "doses": 9 }, { "first_name": "API", "doses": 4 } ],
    "missed_doses": [ { "pet": "Kage", "pill": "Metacam", "missed": 2 } ]
  }
}
```
Chore points are each chore's weight, halved for split completions. Actions credited to the shared account show up as `"API"`.

---

### 5.2 Chores

**Chore object**
```json
{
  "id": 7,
  "name": "Do the Dishes",
  "slug": "do-the-dishes",
  "description": "Load and run the dishwasher.",
  "weight": 2,
  "status": "due",
  "repeats": "every 1 day",
  "occurrence_hours": 24,
  "assigned_to": "Sarah",
  "tags": ["Kitchen"],
  "last_completed_at": "2026-10-21T19:02:11-04:00",
  "last_completed_by": "Cody",
  "next_due_at": "2026-10-22T19:02:11-04:00",
  "snoozed_until": null,
  "url": "http://192.168.1.10/chores/do-the-dishes"
}
```

| Field | Notes |
| --- | --- |
| `status` | One of the values below |
| `repeats` | Speakable recurrence, e.g. `"every 3 days"` or `"every 1 month and 2 days"`; `"one time"` for one-time chores |
| `assigned_to` | First name, or `null` for "anyone" |
| `next_due_at` | `last_completed_at + occurrence_hours`. `null` for one-time chores and chores never completed |
| `weight` | Effort points, used by stats |

`status` values:

- `due`: on the Due list. Either never done, or its due date has passed.
- `upcoming`: due within about 3 days. On its due date itself, a chore still counts as `upcoming`; it becomes `due` the next day, matching the website.
- `scheduled`: not due for more than 3 days.
- `one_time`: a one-time chore.
- `snoozed`: snoozed.

One-time chores stay on the one-time list even after they're completed, until someone deletes them on the website. Completing one logs it but doesn't remove it.

#### `GET /api/chores?tag=&assigned=&occurrence=`
Every chore, alphabetical. All filters are optional:
- `tag`: an exact tag name
- `assigned`: `everyone` (unassigned), `assigned` (assigned to anyone), or a person's name
- `occurrence`: `recurring` or `one-time`

#### `GET /api/chores/due`, `/upcoming`, `/one-time` (each takes `?for={person}`) and `GET /api/chores/snoozed`
Each returns a list of chore objects plus a `message`, for example:
- "3 chores are due: Do the Dishes, Laundry and Take Out Trash."
- "1 chore is coming up: Clean Litter Box."
- "No chores are snoozed."

Long lists end with "and N more".

#### `GET /api/chores/{chore}`
A single chore object with an extra `recent_logs` array (the 10 latest chore-log objects, see below).

#### `POST /api/chores/complete` and `POST /api/chores/{chore}/complete`

| Field | Required | Notes |
| --- | --- | --- |
| `chore` | yes, for `/chores/complete` | Name or slug (§3). Not used by the path form |
| `by` | no | Who did it. Defaults to API User |
| `split_with` | no | Split 50/50 with this person. **Requires `by`** |
| `at` | no | When it was done (§4). Defaults to now |
| `dry_run` | no | `true`: validate and describe the action without saving anything |

**201 Created**
```json
{
  "success": true,
  "message": "Marked Do the Dishes as done for Sarah. It's next due tomorrow.",
  "data": {
    "dry_run": false,
    "completed_at": "2026-10-23T19:05:00-04:00",
    "completed_by": "Sarah",
    "split_with": null,
    "log_id": 812,
    "logs": [ /* chore-log objects (2 when split) */ ],
    "chore": { /* updated chore object */ }
  }
}
```
The message has one of these forms:
- "Marked X as done."
- "Marked X as done for Sarah."
- "Marked X as done, split between Cody and Sarah."

For recurring chores, the message also ends with "It's next due …".

A **dry run** returns `200` with "Would mark X as done…" and no `log_id`.

You can complete a chore at any time; it doesn't have to be due. Completing a snoozed chore doesn't remove the snooze.

#### `POST /api/chores/{chore}/snooze`
The snooze applies to everyone, and replaces any existing snooze on that chore.

| Field | Notes |
| --- | --- |
| `duration` | `"3 days"`, `"a week"`, `"2 weeks"`, `"one month"`, `"1 year"`, and so on. Units are hour, day, week, month, or year; a month counts as 4 weeks |
| `hours` | Use instead of `duration`; an integer from 1 to 8064 |
| `by` | Optional; recorded as the person who snoozed it |

The maximum is 1 year. It returns the updated chore object with a message like "Snoozed Clean Gutters until October 30."

#### `DELETE /api/chores/{chore}/snooze`
Returns the chore object. The message is "Clean Gutters is no longer snoozed." or, if it wasn't snoozed, "Clean Gutters wasn't snoozed."

#### `GET /api/chore-logs?chore=&user=&since=&limit=`
Newest first. `limit` defaults to 20, with a maximum of 100. All filters are optional.

**Chore-log object**
```json
{
  "id": 812,
  "chore": "Do the Dishes",
  "chore_slug": "do-the-dishes",
  "completed_at": "2026-10-23T19:05:00-04:00",
  "completed_by": "Sarah",
  "weight_percentage": 100,
  "split_group_id": null
}
```
Both halves of a split share a `split_group_id` and have `weight_percentage: 50`.

#### `DELETE /api/chore-logs/{id}` or `DELETE /api/chore-logs/last`
Undoes a completion. `last` means the most recently *created* chore log, whoever made it.

- If it was a split, both halves are removed.
- Only logs created in the **last 24 hours** can be undone; older ones return `422 undo_window_passed`.

The message reads like "Removed the Do the Dishes completion from 7:05 PM."

---

### 5.3 Pets and pills

**Dose object** (one slot on one day). This is used by `/pills/due`, `/pills/upcoming`, `/pills/today`, and `/summary`:
```json
{
  "pet": "Kage",
  "pet_slug": "kage",
  "pill": "Metacam",
  "pill_slug": "metacam",
  "dosage": "0.5 ml",
  "time": "08:00",
  "label": "8:00 AM",
  "scheduled_at": "2026-10-23T08:00:00-04:00",
  "status": "due",
  "given_at": null,
  "given_by": null
}
```
Inside a pill object's `doses` array, the first five fields (`pet` through `dosage`) are left out.

**Pill object**
```json
{
  "id": 1,
  "name": "Metacam",
  "slug": "metacam",
  "dosage": "0.5 ml",
  "description": "Give with food.",
  "active": true,
  "pet": "Kage",
  "pet_slug": "kage",
  "scheduled_times": ["08:00", "20:00"],
  "doses": [
    { "time": "08:00", "label": "8:00 AM", "scheduled_at": "...", "status": "given", "given_at": "2026-10-23T08:04:00-04:00", "given_by": "Sarah" },
    { "time": "20:00", "label": "8:00 PM", "scheduled_at": "...", "status": "upcoming", "given_at": null, "given_by": null }
  ],
  "completed_today": ["08:00"],
  "next_scheduled": "20:00",
  "tags": ["Pain Relief"],
  "last_given_at": "2026-10-23T08:04:00-04:00",
  "last_given_by": "Sarah"
}
```

- `scheduled_times` is in effective-day order, so a `"01:00"` slot comes last.
- `next_scheduled` is the slot a give request with no `time` would fill: the earliest `due` slot, or else the earliest `upcoming` one. It's `null` when every slot is given.
- `pet` and `pet_slug` are left out when the pill is nested inside a pet.

**Pet object**
```json
{
  "id": 1,
  "name": "Kage",
  "slug": "kage",
  "species": "Cat",
  "breed": "Siamese",
  "birth_date": "2014-05-02",
  "age": 12,
  "notes": "Hide pills in a treat.",
  "photo_url": "http://192.168.1.10/storage/pet_photos/kage.jpg",
  "pills": [ /* active pill objects, alphabetical */ ]
}
```
`age` is in whole years, or `null` when there's no birth date.

#### `GET /api/pets` · `GET /api/pets/{pet}`
`/api/pets` returns all pets, alphabetical, each with its active pills and today's doses. `/api/pets/{pet}` returns one pet.

#### `GET /api/pets/{pet}/pills` (older endpoint)
`data` is `{ "pet": "Kage", "pills": [ /* pill objects */ ] }`. New integrations should use `GET /api/pets/{pet}` instead.

#### `GET /api/pills?pet=&tag=&include_inactive=true`
Lists pill objects alphabetically. Only active pills are included unless `include_inactive=true`.

#### `GET /api/pills/due` · `/upcoming` · `/today`
Each returns a list of dose objects across all pets, in time order, plus a `message`:
- due: "2 medication doses are due: Kage's Metacam at 8:00 AM and Luna's Thyroid Pill at 9:00 AM."
- upcoming: "1 medication dose is coming up later today: Kage's Metacam at 8:00 PM."
- today: "3 of 5 medication doses have been given today. Still due: Kage's Metacam at 8:00 AM."

#### `GET /api/pills/{pill}`
A single pill object plus `recent_logs` (the 10 latest pill-log objects).

#### `POST /api/pills/give` (also `POST /api/pills/{pill}/give` and the older `POST /api/record-medication`)

| Field | Required | Notes |
| --- | --- | --- |
| `pet` | one of `pet`/`pill` | Pet name |
| `pill` | one of `pet`/`pill` | Pill name. Not used by the `/pills/{pill}/give` path form |
| `time` | no | A specific slot (`"8pm"`, `"20:00"`). Defaults to the next one, as described below |
| `early` | no | `true` allows filling a slot that isn't due yet |
| `by` | no | Who gave it. Defaults to API User |
| `notes` | no | Up to 500 characters |
| `at` | no | When it was given (§4). Defaults to now |
| `dry_run` | no | `true`: work everything out without saving |

Booleans can be sent as `true`/`false`, `"true"`/`"false"`, `1`/`0`, or `"yes"`/`"no"`.

**How the server picks the pill:**
1. If both `pet` and `pill` are missing → `422 pet_or_pill_required`.
2. **`pill` given:** it's matched among active pills, limited to that pet's pills when `pet` is also given. A pill name alone is enough, because the pet is worked out from the pill.
   - Several matches → `409 ambiguous_pill`.
   - None → `404 pill_not_found` (when the pet was given, its pills are listed as candidates).
3. **Only `pet` given:**
   - The pet has exactly 1 active pill → that pill is used.
   - It has 0 → `422 no_active_pills`.
   - It has more than 1 → `409 ambiguous_pill`, listing them. The server **never guesses** between several pills, even if only one is due.

**How it picks the slot** (as of `at`, which defaults to now):
- **`time` given:**
  - It must be one of the pill's scheduled times (otherwise `422 invalid_slot`, with `scheduled_times`).
  - It must not already be given today (otherwise `422 slot_already_given`).
- **`time` not given:** the server uses the earliest slot that is **due**.
  - If nothing is due but some slots are later today, the earliest one is chosen.
  - If every slot is given → `422 all_doses_given`.
- **A slot that isn't due yet is refused unless `early: true`** → `422 dose_not_due`, with `next_time` and `next_label`. This applies even when `time` names it. Nothing is saved.

**201 Created**
```json
{
  "success": true,
  "message": "Logged Kage's 8:00 AM dose of Metacam.",
  "data": {
    "log_id": 451,
    "dry_run": false,
    "pet": "Kage",
    "pet_slug": "kage",
    "pill": "Metacam",
    "pill_slug": "metacam",
    "dosage": "0.5 ml",
    "scheduled_time": "08:00",
    "scheduled_label": "8:00 AM",
    "early": false,
    "administered_at": "2026-10-23T08:04:00-04:00",
    "administered_by": "API",
    "remaining_today": ["20:00"]
  }
}
```
The message has one of these forms:
- "Logged Kage's 8:00 AM dose of Metacam."
- "… of Metacam early."
- "…, given by Sarah."

A **dry run** returns `200`, without `log_id` or `remaining_today`, and a message starting "Would log …".

**This call is not idempotent:** each success fills another slot. If a request's outcome is unknown (timeout, network error), check `GET /api/pills/today` (or `GET /api/pill-logs?limit=1`) **before** retrying.

#### `GET /api/pill-logs?pet=&pill=&user=&since=&limit=`
Newest first. `limit` defaults to 20, with a maximum of 100.

**Pill-log object**
```json
{
  "id": 451,
  "pet": "Kage",
  "pill": "Metacam",
  "pill_slug": "metacam",
  "dosage": "0.5 ml",
  "scheduled_time": "08:00",
  "administered_at": "2026-10-23T08:04:00-04:00",
  "given_by": "API",
  "notes": null
}
```

#### `DELETE /api/pill-logs/{id}` or `DELETE /api/pill-logs/last`
Undoes a dose that was created in the last 24 hours. The message reads like "Removed Kage's 8:00 AM dose of Metacam."

---

## 6. Error codes

| `code` | HTTP | Meaning | What the voice assistant should do |
| --- | --- | --- | --- |
| `ambiguous_pet` / `ambiguous_pill` / `ambiguous_chore` | 409 | Several matches | Read the `candidates` names and ask which one, then retry with the chosen `slug` |
| `ambiguous_user` | 409 | Two people share that first name | Ask which one, then retry with their `id` |
| `pet_not_found` / `pill_not_found` / `chore_not_found` / `user_not_found` | 404 | No match | Speak `error`, which includes suggestions when there are only a few options. Ask the user to repeat or choose |
| `pet_or_pill_required` | 422 | A give request named neither a pet nor a pill | Ask which pet |
| `no_active_pills` | 422 | The pet has no active medications | Speak `error` |
| `dose_not_due` | 422 | The next dose is later today; nothing was logged | Speak `error` and ask "Log it early?". If the user says yes, retry with `early: true` |
| `all_doses_given` | 422 | Every dose for today is already logged | Speak `error`. Don't retry |
| `slot_already_given` | 422 | That specific time was already logged | Speak `error` (it says who logged it and when) |
| `invalid_slot` | 422 | `time` isn't on the pill's schedule | Speak `error` (it lists the valid times) |
| `invalid_time` | 422 | `at` or `since` couldn't be understood, is in the future, or is more than 24 hours ago | Speak `error` |
| `split_requires_by` | 422 | `split_with` was sent without `by` | Ask who did the chore |
| `invalid_split` | 422 | `by` and `split_with` are the same person | Ask again |
| `invalid_duration` | 422 | The snooze length couldn't be understood | Ask "for how long?" |
| `log_not_found` | 404 | No log with that id, or nothing to undo | Speak `error` |
| `undo_window_passed` | 422 | The log is more than 24 hours old | Speak `error`; the user can fix it on the website |
| `validation_failed` | 422 | A field has the wrong type or is missing; `errors` holds the details per field | A bug in the automation. Log it |
| `unauthenticated` | 401 | The token is missing, invalid, or revoked | Configuration problem. Log it |
| `not_found` / `method_not_allowed` | 404 / 405 | Wrong URL or HTTP method | A bug in the automation |
| `rate_limited` | 429 | More than 60 requests per minute | Wait for `Retry-After` |
| `server_error` / `http_error` | 500 / other | A server problem | Say it didn't work and log it |

---

## 7. Voice intents → API calls

| User says | Call |
| --- | --- |
| "What needs doing?" / "What's due?" | `GET /api/summary`, then speak `message` |
| "What chores are due?" | `GET /api/chores/due` |
| "What chores are coming up?" | `GET /api/chores/upcoming` |
| "What's due for Sarah?" | `GET /api/chores/due?for=Sarah` |
| "Did the pets get their meds?" / "What meds are due?" | `GET /api/pills/due` (or `/api/pills/today` for the full picture) |
| "I did the dishes." | `POST /api/chores/complete` `{"chore": "dishes"}` |
| "Sarah did the dishes." | `POST /api/chores/complete` `{"chore": "dishes", "by": "Sarah"}` |
| "Cody and Sarah cleaned the garage." | `POST /api/chores/complete` `{"chore": "clean the garage", "by": "Cody", "split_with": "Sarah"}` |
| "I mowed the lawn an hour ago." | `POST /api/chores/complete` `{"chore": "mow the lawn", "at": "1 hour ago"}` |
| "Snooze the gutters for a week." | `POST /api/chores/gutters/snooze` `{"duration": "1 week"}` |
| "Unsnooze the gutters." | `DELETE /api/chores/gutters/snooze` |
| "When did someone last do the litter box?" | `GET /api/chores/litter%20box`, then read `last_completed_at` and `last_completed_by` |
| "I gave Kage her meds." | `POST /api/pills/give` `{"pet": "Kage"}` (a `409` means ask which medication) |
| "I gave Luna her thyroid pill." | `POST /api/pills/give` `{"pet": "Luna", "pill": "thyroid"}` |
| "I gave the Metacam." | `POST /api/pills/give` `{"pill": "Metacam"}` |
| "Give Luna the 8 PM dose early." | `POST /api/pills/give` `{"pet": "Luna", "time": "8pm", "early": true}` |
| "Sarah gave Kage his meds at 7." | `POST /api/pills/give` `{"pet": "Kage", "by": "Sarah", "at": "today 7am"}` (only if 7 is in the last 24 hours) |
| "Undo that." | `DELETE /api/chore-logs/{log_id}` or `/api/pill-logs/{log_id}`, using the `log_id` from the last action. If it wasn't kept, use `.../last` on the matching type |
| "Who's done the most chores this week?" | `GET /api/stats?period=week` |
| "What pets do we have?" / "What meds does Kage take?" | `GET /api/pets` / `GET /api/pets/Kage` |

---

## 8. Safety rules for automations

1. **Never send `early: true` on your own.** Only send it when the user explicitly said "early", or answered yes after hearing a `dose_not_due` error. Early doses are how double-dosing happens.
2. **Never pick from `candidates` yourself.** Read the choices and let the user decide.
3. **Use `dry_run: true` when unsure.** If the request was unclear or speech-to-text confidence was low, call with `dry_run: true`, speak the "Would …" message as a confirmation question, and send the real call (without `dry_run`) only after the user says yes.
4. **Keep the `log_id`** from every successful action for the rest of the conversation, so "undo that" removes exactly that entry. Prefer the id to `last`, because `last` is whatever was logged most recently, possibly by someone else on the website.
5. **Don't retry POSTs blindly.** Chore completions and doses aren't idempotent. After a timeout, check `/api/pills/today` or `/api/chore-logs?limit=1` first.
6. **Only send `by` when the speaker said who did it.** Otherwise leave it out, and the action is credited to API User.
7. **Speak the server's sentences.** `message` and `error` are already worded for speech, so there's no need to rebuild them from `data`.

---

## 9. Home Assistant integration

The YAML below targets recent Home Assistant releases (2024+), which have `response_variable` and `conversation` triggers. **Check field names against the HA version you're configuring**, especially how `rest_command` reports non-2xx responses.

### 9.1 Secrets and REST commands

`secrets.yaml`
```yaml
chore_api_auth: "Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

`configuration.yaml`: `rest_command` doesn't let the HTTP method be templated, so define one command per method.
```yaml
rest_command:
  chore_api_get:
    url: "http://192.168.1.10/api/{{ path }}"
    method: get
    headers:
      Authorization: !secret chore_api_auth
      Accept: application/json
    timeout: 10

  chore_api_post:
    url: "http://192.168.1.10/api/{{ path }}"
    method: post
    headers:
      Authorization: !secret chore_api_auth
      Accept: application/json
    content_type: "application/json"
    payload: "{{ body | default({}) | tojson }}"
    timeout: 10

  chore_api_delete:
    url: "http://192.168.1.10/api/{{ path }}"
    method: delete
    headers:
      Authorization: !secret chore_api_auth
      Accept: application/json
    timeout: 10
```
When called with `response_variable`, the result exposes `status` (the HTTP code) and `content` (the parsed JSON body, since the API always returns `application/json`).

Empty strings in the body are treated as missing, so templates can send `"pill": ""` safely.

### 9.2 A script the Assist LLM agent can call as a tool

Scripts exposed to Assist become tools for an LLM conversation agent. The script returns a short object, and the agent speaks `speech`.
```yaml
script:
  chore_app_give_medication:
    alias: "Give pet medication"
    description: >-
      Log that a pet was given its medication. Use when someone says they gave a pet
      its pill, meds or medicine. Only set early to true if the user explicitly asks to
      log a dose early. If the result code is ambiguous_pill, ask which medication.
    fields:
      pet:
        description: "Pet name, e.g. Kage"
        selector: { text: {} }
      pill:
        description: "Medication name, if the user said one"
        selector: { text: {} }
      by:
        description: "Who gave it, only if the user said so"
        selector: { text: {} }
      early:
        description: "True only if the user explicitly asked to log it early"
        selector: { boolean: {} }
    sequence:
      - action: rest_command.chore_api_post
        data:
          path: pills/give
          body: >-
            {{ {'pet': pet | default(''), 'pill': pill | default(''), 'by': by | default(''), 'early': early | default(false)} }}
        response_variable: result
      - variables:
          reply: >-
            {% set c = result.content if result.content is mapping else {} %}
            {{ {
              'ok': c.get('success', false),
              'speech': c.get('message') or c.get('error') or 'The chore app did not respond.',
              'code': c.get('code'),
              'candidates': c.get('candidates', []) | map(attribute='name') | list,
              'log_id': (c.get('data') or {}).get('log_id')
            } }}
      - stop: "done"
        response_variable: reply
```
Build tools for the other actions the same way. Each one maps to a single endpoint:
- `chore_app_whats_due` → `chore_api_get` with `path: summary`
- `chore_app_complete_chore` → `chore_api_post` with `path: chores/complete` and fields `chore`, `by`, `split_with`
- `chore_app_snooze_chore` → `chore_api_post` with `path: "chores/{{ chore | urlencode }}/snooze"`
- `chore_app_undo` → `chore_api_delete` with `path: "pill-logs/{{ log_id | default('last') }}"` or the chore equivalent

### 9.3 Sentence-trigger automation (no LLM)

For fixed phrases handled by Assist's built-in matcher, `{pet}` is a wildcard slot:
```yaml
automation:
  - alias: "Voice: gave pet meds"
    triggers:
      - trigger: conversation
        command:
          - "I gave {pet} [his|her|their] (meds|medicine|medication|pills)"
          - "{pet} got [his|her|their] (meds|medicine|medication|pills)"
    actions:
      - action: rest_command.chore_api_post
        data:
          path: pills/give
          body: "{{ {'pet': trigger.slots.pet} }}"
        response_variable: result
      - set_conversation_response: >-
          {{ result.content.message if result.content.success else result.content.error }}

  - alias: "Voice: what needs doing"
    triggers:
      - trigger: conversation
        command:
          - "what needs doing"
          - "what('s| is) due [today]"
    actions:
      - action: rest_command.chore_api_get
        data:
          path: summary
        response_variable: result
      - set_conversation_response: "{{ result.content.message }}"
```
Sentence triggers can't hold a conversation, so a `409` (which pill?) or a `dose_not_due` is simply spoken back to the user. Use the LLM-tool approach (9.2) when you want follow-up questions.

---

## 10. curl examples

```bash
export BASE_URL="http://192.168.1.10"
export TOKEN="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
H=(-H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json")

curl -s "${H[@]}" "$BASE_URL/api/summary"
curl -s "${H[@]}" "$BASE_URL/api/pills/due"
curl -s "${H[@]}" -X POST -d '{"pet":"kage","dry_run":true}' "$BASE_URL/api/pills/give"
curl -s "${H[@]}" -X POST -d '{"pet":"kage","pill":"metacam","notes":"with breakfast"}' "$BASE_URL/api/pills/give"
curl -s "${H[@]}" -X POST -d '{"chore":"dishes","by":"Sarah"}' "$BASE_URL/api/chores/complete"
curl -s "${H[@]}" -X POST -d '{"duration":"1 week"}' "$BASE_URL/api/chores/clean-gutters/snooze"
curl -s "${H[@]}" -X DELETE "$BASE_URL/api/pill-logs/last"
```

---

## 11. Quick checklist

- Every request needs `Authorization: Bearer …`. Bodies are JSON.
- Check `success` and speak `message` or `error`. Branch on `code`.
- Names can be a slug, a name, or unique whole words. A `409` means ask the user; never guess.
- People are given by first name, full name, or id. With no `by`, the action is credited to API User.
- "Today" runs from 03:00 to 02:59. Doses are slots, and a not-yet-due slot needs `early: true`.
- `at` can backdate an action up to 24 hours. `dry_run` previews without saving.
- POST actions are not idempotent. Keep `log_id` for "undo that". Undo only works within 24 hours.
- Creating, editing, and deleting chores, pets, or pills is web-only.
