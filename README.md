# BeChore

A self-hosted Laravel app for tracking household chores (and pet medication schedules) between people who don't work the same hours. Chores are weighted so effort is comparable across different tasks, completions are logged with a timestamp, and overdue/snoozed chores surface automatically based on how often they recur.

## Features

- **Chores** — create recurring chores with a name, description, weight, and recurrence interval (in hours).
- **Chore logging** — log completions, edit/delete past logs, and see who did what and when.
- **Snoozing** — temporarily snooze a chore so it drops off the due list without logging a completion (`App\Http\Controllers\ChoreSnoozeController`).
- **Graphs** — visual breakdowns of completions and missed chores over time, built with [LarapexCharts](https://github.com/ArielMejiaDev/larapex-charts).
- **Pet & pill tracking** — manage pets and their medication schedules, log doses, and view an at-a-glance pill dashboard.
- **API tokens** — issue personal access tokens (Laravel Sanctum) for scripting against the app's API.

## Tech stack

- PHP 8.3+ / Laravel 12
- Blade + [Laravel Breeze](https://laravel.com/docs/starter-kits#breeze-and-inertia) for auth scaffolding
- Tailwind CSS + Alpine.js, bundled with Vite
- MySQL
- [Laravel Sail](https://laravel.com/docs/sail) for local Docker development

## Getting started

### Prerequisites

- Docker Desktop (or Docker Engine + Compose)
- Composer (only needed once, to install dependencies before Sail exists — see below)

### 1. Clone and install dependencies

Sail itself ships as a Composer dependency, so you need Composer available once to bootstrap it. The easiest way is to run Composer inside a throwaway container:

```bash
git clone <repo-url> chore-app
cd chore-app

docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

If you already have PHP + Composer installed locally, you can just run `composer install` instead.

### 2. Configure the environment

```bash
cp .env.example .env
```

The defaults target a database host named `chore-app-mysql-1` (the Sail-generated container name — based on the project directory). Adjust `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` as desired; Sail will provision the MySQL container to match whatever you put here.

### 3. Start the stack with Sail

```bash
./sail up -d
```

This brings up the app container plus MySQL, Redis, Meilisearch, Mailpit, and Selenium. Consider adding a shell alias so you don't have to type `./sail` every time:

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

### 4. Finish the install

```bash
./sail artisan key:generate
./sail artisan migrate --seed
./sail npm install
./sail npm run dev   # or: ./sail npm run build
```

The app is now available at `http://localhost` (or whatever `APP_PORT` you set in `.env`).

### Running tests

```bash
./sail artisan test
```

### Everyday commands

| Command | Purpose |
| --- | --- |
| `./sail up -d` | Start all containers in the background |
| `./sail down` | Stop all containers |
| `./sail artisan ...` | Run an Artisan command inside the app container |
| `./sail composer ...` | Run Composer inside the app container |
| `./sail npm run dev` | Start the Vite dev server with hot reload |
| `./sail mysql` | Open a MySQL shell against the app's database |

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
