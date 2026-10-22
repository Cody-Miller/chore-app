# Setting up ntfy reminders

BeChore can push reminders to your phone through [ntfy](https://ntfy.sh), an open-source push notification server you run yourself next to the app. It doesn't need a mail server.

- **Chores:** at 8:00 AM, you get one notification for each chore that is due today or overdue.
- **Medications:** you get a notification at each scheduled dose time, unless the dose has already been logged.
- Each notification has a **Done** (chores) or **Given** (pills) button that logs the completion as you, without opening the app.

Reminders are opt-in in two places. Each person sets an ntfy topic on their Profile page, and each chore or medication has its own **Reminders** switch.

## How the pieces fit together

```
BeChore ──(publishes)──▶ ntfy server ──(pushes)──▶ ntfy app on your phone
   ▲                                                      │
   └──────────── Done / Given button (HTTP POST) ─────────┘
```

Two addresses matter:

| Setting | Who uses it | Example |
| --- | --- | --- |
| `NTFY_PUBLIC_URL` | Your phone, to receive notifications | `http://192.168.1.20:8090`, `https://ntfy.example.com` |
| `APP_URL` | Your phone, when you tap **Done** | `http://192.168.1.20`, `https://chores.example.com` |

**Both must be reachable from your phone.** BeChore itself talks to ntfy over the Docker network (`NTFY_URL=http://ntfy`), so that address never leaves the server.

### Choosing how phones reach the server

| Option | Works | Effort |
| --- | --- | --- |
| **Home Wi-Fi only** (use the server's LAN IP) | Only while you're home. Away, notifications are delayed or missed and Done buttons fail. | None |
| **[Tailscale](https://tailscale.com)** (recommended) | Anywhere, privately. Install Tailscale on the server and phones, then use the server's Tailscale IP or MagicDNS name. | Low |
| **Reverse proxy with HTTPS** (Caddy, nginx, Traefik) | Anywhere, but exposed to the internet. Set `NTFY_BEHIND_PROXY=true`. | Medium |

## 1. Configure `.env`

Add these to BeChore's `.env` (they're also in `.env.example`):

```dotenv
APP_URL=http://192.168.1.20          # how phones reach BeChore

NTFY_URL=http://ntfy                 # how BeChore reaches ntfy (the Docker service name)
NTFY_TOKEN=                          # filled in during step 3
NTFY_PUBLIC_URL=http://192.168.1.20:8090   # how phones reach ntfy
NTFY_CHORE_TIME=08:00                # when chore reminders go out (app timezone)
# NTFY_BEHIND_PROXY=true             # only when ntfy sits behind a reverse proxy
# FORWARD_NTFY_PORT=8090             # host port for ntfy (default 8090)
```

## 2. Start ntfy and migrate

The `ntfy` service is defined in `docker-compose.yml`. It uses image `binwiederhier/ntfy:v2.28.0`, keeps its data in the `sail-ntfy` volume, and requires a login for every topic (`deny-all`).

```bash
./sail up -d ntfy
```

```bash
curl http://localhost:8090/v1/health
```

The health check should return `{"healthy":true}`. Then add the new database columns:

```bash
./sail artisan migrate
```

## 3. Create ntfy accounts

The server starts with no accounts. Create one account that BeChore publishes with, and one account per person. Each command prompts for a password.

**BeChore's publisher account:** it can only write to `bechore-*` topics, never read them.

```bash
./sail exec ntfy ntfy user add bechore
```

```bash
./sail exec ntfy ntfy access bechore 'bechore-*' write-only
```

```bash
./sail exec ntfy ntfy token add --label="BeChore app" bechore
```

Copy the `tk_...` token it prints into `NTFY_TOKEN` in `.env`.

**One account per person:** each person can only read their own topic. Repeat the next two commands for each person, changing the name each time.

```bash
./sail exec ntfy ntfy user add cody
```

```bash
./sail exec ntfy ntfy access cody bechore-cody read-only
```

To check the result, `./sail exec ntfy ntfy access` lists every user and what they can access.

## 4. Run the scheduler

`php artisan reminders:send` runs every minute through Laravel's scheduler. The scheduler needs something to start it.

- **Development:** leave this running in a terminal.

  ```bash
  ./sail artisan schedule:work
  ```

- **Production:** add a cron entry on the host (`crontab -e`). Adjust the path to where the app lives.

  ```
  * * * * * cd /path/to/chore-app && ./sail artisan schedule:run >> /dev/null 2>&1
  ```

  If PHP runs directly on the host instead of in Sail, use `php artisan schedule:run` in place of `./sail artisan schedule:run`.

To confirm it's registered, run `./sail artisan schedule:list`. It should show `reminders:send` every minute.

## 5. Set up each phone

1. Install the ntfy app: [Android (Google Play)](https://play.google.com/store/apps/details?id=io.heckel.ntfy), [Android (F-Droid)](https://f-droid.org/en/packages/io.heckel.ntfy/), or [iPhone (App Store)](https://apps.apple.com/us/app/ntfy/id1625396347).
2. In the app's settings, add a user for your server. Use your `NTFY_PUBLIC_URL`, plus the username and password from step 3.
3. Subscribe to your topic (for example `bechore-cody`). Choose **Use another server** and enter your `NTFY_PUBLIC_URL`.
4. Allow notifications when the app asks.
   - **Android:** exempt ntfy from battery optimization so notifications arrive on time.
   - **iPhone:** Apple only allows instant background delivery through its push service, so the server sets `NTFY_UPSTREAM_BASE_URL=https://ntfy.sh`. For each message, ntfy.sh only receives a "check for new messages" signal with the message ID. Your phone then fetches the actual text from your server. Without this, iPhone notifications can take a long time to arrive.

## 6. Turn reminders on in BeChore

1. Go to **Profile → Notifications** and enter your topic (for example `bechore-cody`). Click **Save**, then **Send Test Notification**. A test message should appear on your phone within a few seconds. If it doesn't, the page shows ntfy's error.
2. Edit a chore or medication and turn the **Reminders** switch on.

## How reminders behave

**Chores**
- Sent once per chore per person per day, at `NTFY_CHORE_TIME` or the first scheduler run after it.
- Sent for chores that are due today or overdue. Snoozed chores are skipped.
- One-time chores remind each morning until they're completed once.
- A chore assigned to someone only notifies that person. Unassigned chores notify everyone with a topic.

**Medications**
- Sent at each scheduled time to everyone with a topic, unless that dose has already been logged.
- If the server was down, a dose is still sent up to 60 minutes late. Doses later than that are skipped rather than sent.

**Done / Given buttons**
- The button logs the completion as the person whose topic received the notification.
- If someone else already did it, the button reports that and nothing is logged twice.
- The links are signed, so they can't be changed to complete a different chore or log as someone else.
- Chore links expire after 24 hours. Medication links expire when that dose's day ends (3:00 AM).
- When one person taps Done, the other person's notification stays on their phone until they dismiss it.

## Troubleshooting

| Problem | What to check |
| --- | --- |
| "Send Test Notification" shows *NTFY_URL is not set* | Add `NTFY_URL` to `.env`. If config is cached, run `./sail artisan config:clear`. |
| Test fails with *Could not resolve host: ntfy* | The ntfy container isn't running (`./sail up -d ntfy`), or BeChore isn't running in the same Docker network. |
| Test fails with 401 or 403 | `NTFY_TOKEN` is wrong, or the `bechore` user lacks `write-only` access to `bechore-*`. Check with `./sail exec ntfy ntfy access`. |
| Test succeeds but nothing shows on the phone | Check that the phone is subscribed to exactly that topic, on your server rather than ntfy.sh, and logged in as a user with read access. |
| No chore or pill reminders | Is the scheduler running (`schedule:list` / `schedule:work`)? Is **Reminders** on for that item? Is the chore actually due today? Is it after `NTFY_CHORE_TIME`? Run `./sail artisan reminders:send` by hand to see what it sends. |
| Reminders arrive at the wrong time | The app timezone is set in `config/app.php` (`America/New_York`). |
| Done/Given button fails | The phone can't reach `APP_URL`, for example because you're away from home without Tailscale, or the link expired. |
| Notifications are late on iPhone | `NTFY_PUBLIC_URL` must be correct and `NTFY_UPSTREAM_BASE_URL` set. Both are already configured in `docker-compose.yml`. |

## Security notes

- Every topic requires a login (`NTFY_AUTH_DEFAULT_ACCESS=deny-all`). BeChore's token can publish but not read, and each person can only read their own topic.
- Done links are signed with `APP_KEY` and only cover one chore or dose, for one person, for a limited time.
- To revoke BeChore's access, run `./sail exec ntfy ntfy token list` to find the token, then `./sail exec ntfy ntfy token remove bechore <token>`. Then create a new token.
