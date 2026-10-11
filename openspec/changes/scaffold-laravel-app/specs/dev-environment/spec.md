## Purpose

Defines the local development environment for the Address Book app: a Laravel 13 application pinned to PHP 8.3, run through Laravel Sail with MySQL, and tested with Pest, so every developer and CI runs the same stack as production.

## ADDED Requirements

This document details *changes* to requirements and is additive to the [`design.md`](../../design.md) document, not a replacement.

### Requirement: Laravel 13 application on PHP 8.3

The system SHALL be a Laravel 13 application whose `composer.json` requires PHP `^8.3` and sets `config.platform.php` to 8.3, so dependencies resolve for the production PHP version.

#### Scenario: Dependencies resolve on PHP 8.3

- **Given** a fresh checkout with `composer.lock` committed
- **When** `composer install` runs on PHP 8.3
- **Then** it succeeds and `laravel/framework` is a 13.x release

#### Scenario: Newer-PHP-only dependency rejected

- **Given** a package that requires PHP 8.4 or newer
- **When** it is added with `composer require`
- **Then** Composer refuses to install it because of the platform pin

### Requirement: Official Livewire starter kit with Fortify authentication

The system SHALL be created from the official Laravel Livewire starter kit (non-WorkOS) so that login, logout, registration, password reset, email verification and TOTP two-factor routes exist and have passing tests.

#### Scenario: Auth routes registered

- **Given** the scaffolded application
- **When** `php artisan route:list` is run
- **Then** it lists `/login`, `/logout`, `/forgot-password`, `/reset-password/{token}` and `/two-factor-challenge`

#### Scenario: No paid UI dependency

- **Given** the committed `composer.json`
- **When** its dependencies are inspected
- **Then** `livewire/flux-pro` is not present

### Requirement: One-command local environment with MySQL

The system SHALL provide a Sail configuration with a MySQL service so that `./vendor/bin/sail up -d` starts the app and database ready for migrations.

#### Scenario: Environment starts

- **Given** Docker is running and `.env` is copied from `.env.example`
- **When** `./vendor/bin/sail up -d` and `sail artisan migrate` run
- **Then** migrations complete and the home page returns HTTP 200

#### Scenario: Missing configuration is documented

- **Given** a new contributor reads the README
- **When** they follow the setup section
- **Then** every command needed to run the app and tests is listed

### Requirement: Tests run with Pest against MySQL

The system SHALL use Pest as its test framework and SHALL run the test suite against a MySQL database, not SQLite.

#### Scenario: Suite passes locally

- **Given** the Sail environment is running
- **When** `./vendor/bin/sail artisan test` runs
- **Then** all tests pass

#### Scenario: Tests use MySQL

- **Given** the test environment configuration
- **When** the default database connection is read inside a test
- **Then** its driver is `mysql`

### Requirement: Repository hygiene

The system SHALL keep `.env`, `vendor/` and `.worktrees/` out of version control and ship `.env.example` with placeholder values only.

#### Scenario: Secrets not tracked

- **Given** a fresh clone
- **When** `git ls-files` is searched for `.env`
- **Then** only `.env.example` is present

### Requirement: Reliability criteria

The system SHALL meet the non-functional criterion below.

#### Scenario: Version parity

- **Given** local Sail, CI and the host
- **When** the PHP minor version is compared
- **Then** all three report 8.3

## Traceability

- Proposal element (PHP 8.3 pinned) -> Requirement: Laravel 13 application on PHP 8.3
- Proposal element (starter kit) -> Requirement: Official Livewire starter kit with Fortify authentication
- Proposal element (Sail with MySQL) -> Requirement: One-command local environment with MySQL
- Proposal element (Pest) -> Requirement: Tests run with Pest against MySQL
- Design Decisions 1 -> PHP requirement; 2 -> starter kit; 3 and 4 -> environment and tests; 5 -> Pest
- Requirement -> Task(s): see `tasks.md` Execution groups 1 to 5 and `tests.md`
- Security NFAC: see functional scenario: Secrets not tracked
