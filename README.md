# Address Book

A private, shared address book built with Laravel 13, Livewire (Flux UI), Fortify authentication
with TOTP two-factor, and MongoDB (Atlas in production).

## Stack and version policy

| Part | Version | Notes |
|---|---|---|
| PHP | **8.3** | Pinned in `composer.json` (`require.php` and `config.platform.php`), Sail and CI |
| Laravel | 13.x | Latest major; requires PHP 8.3 |
| Database | MongoDB | `mongodb/laravel-mongodb` 5.x; Atlas Local locally and in CI, Atlas in production |
| Tests | Pest 4 | Run against a real MongoDB `testing` database |
| Style / analysis | Pint, Larastan (PHPStan level 7) | |

Do not add a dependency that needs a newer PHP than 8.3: Composer will refuse it because of the
platform pin. To check, run `composer require some/package-needing-php-8.4` and expect a
resolution error.

> **Deployment note:** the current production host (Exact Hosting) runs PHP 8.2, and the app needs
> PHP 8.3 and the `mongodb` PHP extension, plus outbound access to Atlas. Deployment is tracked in
> a separate issue and is blocked until the host meets these requirements.

## Requirements

Docker with Compose. No local PHP, Composer or Node is needed.

## First-time setup

```bash
git clone <repo-url> address-book && cd address-book
cp .env.example .env

# Fetch Sail without a local PHP (throwaway container; it has no mongodb extension,
# so skip the scripts and platform checks here)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php83-composer:latest composer install --ignore-platform-reqs --no-scripts

export WWWUSER=$(id -u) WWWGROUP=$(id -g)
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Open <http://localhost>. Run `./vendor/bin/sail npm run dev` for hot reloading while developing.

`.env` is never committed. `.env.example` holds placeholders only; the local database needs no
credentials. Production uses an Atlas connection string in `MONGODB_URI`.

## Everyday commands

All commands run inside the Sail container:

| Task | Command |
|---|---|
| Start / stop | `./vendor/bin/sail up -d` / `./vendor/bin/sail down` |
| Run tests | `./vendor/bin/sail composer test` (or `sail artisan test`) |
| Check code style | `./vendor/bin/sail composer lint` (`composer lint:fix` to fix) |
| Static analysis | `./vendor/bin/sail composer analyse` |
| All checks, as CI runs them | `./vendor/bin/sail composer ci:check` |
| Build assets | `./vendor/bin/sail npm run build` |

Tests use the `testing` database on the same MongoDB service and drop it before every test
(`tests/Concerns/RefreshMongoDatabase.php`). Write the failing test first.

## Continuous integration

`.github/workflows/ci.yml` runs on every pull request and on pushes to `main`: asset build, Pint,
Larastan and Pest on PHP 8.3 against an Atlas Local MongoDB service container. It uses no
repository secrets.

## Known limitations

- **Passkeys are disabled.** The starter kit stores passkeys in a SQL-backed Eloquent model, which
  does not work on MongoDB. Login uses a password plus optional TOTP two-factor authentication.
