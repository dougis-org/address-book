---
name: tests
description: Tests for the scaffold-laravel-app change
---

# Tests

## Overview

Tests for the `scaffold-laravel-app` change (issue #1). All work follows strict TDD: write the failing test, make it pass with the simplest code, then refactor. Test commands run through Sail (`./vendor/bin/sail`) unless stated.

## Testing Steps

For each task in `tasks.md`:

1. **Write a failing test:** capture the requirement first; run it and confirm it fails for the right reason.
2. **Write code to pass the test:** simplest implementation only.
3. **Refactor:** improve structure while the test stays green.

## Test Cases

### 1. Application scaffold (Tasks Group 1; spec: dev-environment)

- [x] `tests/Feature/PlatformTest.php`: PHP version is 8.3.x (`PHP_VERSION`), maps to scenario "Dependencies resolve on PHP 8.3"
- [x] `tests/Feature/PlatformTest.php`: installed Laravel major version is 13 (`app()->version()` starts with `13.`)
- [x] `tests/Feature/AuthRoutesTest.php`: routes `login`, `logout`, `password.request`, `password.reset` and `two-factor.login` exist, maps to "Auth routes registered"
- [x] `tests/Feature/DependencyPolicyTest.php`: `composer.json` does not require `livewire/flux-pro`, maps to "No paid UI dependency"
- [x] Shell check (documented in README): `composer require` of a package needing PHP 8.3 is refused, maps to "Newer-PHP-only dependency rejected"
- [x] Starter-kit auth tests (login, logout, password reset, two-factor challenge) pass under Pest

### 2. Sail and MongoDB (Tasks Group 2; spec: dev-environment)

- [x] `tests/Feature/DatabaseTest.php`: `config('database.default')` is `mongodb` and the connection answers a `ping` command, maps to "Tests use MongoDB"
- [x] `tests/Feature/HomePageTest.php`: `GET /` returns 200, maps to "Environment starts"
- [x] `tests/Feature/UserPersistenceTest.php`: a user registered through the kit is stored in the MongoDB `users` collection, and the database is empty at the start of each test, maps to "Tests use MongoDB"
- [x] Kit auth and settings tests pass on MongoDB; `tests/Feature/Settings/SecurityTest.php` asserts passkeys are not shown (design Decision 8)
- [x] Manual: `sail up -d && sail artisan test` succeeds on a clean clone
- [x] `tests/Feature/RepositoryHygieneTest.php`: `.env` is not tracked and `.env.example` exists with no non-placeholder secrets, maps to "Secrets not tracked"

### 3. Quality tooling (Tasks Group 3; specs: dev-environment, ci-pipeline)

- [x] `composer lint` (Pint `--test`) exits 0 on the scaffold and non-zero on a deliberately mis-formatted file
- [x] `composer analyse` (Larastan level 7) exits 0 on the scaffold
- [x] `composer test` runs the Pest suite and exits 0
- [x] README walk-through on a clean clone reproduces the above, maps to "Missing configuration is documented"

### 4. GitHub Actions CI (Tasks Group 4; spec: ci-pipeline)

- [x] PR run is green, maps to "All checks pass" and "Pull request triggers CI"
- [x] Push-to-main run triggers after merge, maps to "Push to main triggers CI"
- [ ] Throwaway branch with a Pint violation fails CI, maps to "Style violation fails the job" — **not exercised in CI** (throwaway PR pushes were blocked); verified locally
- [ ] Throwaway branch with a failing Pest test fails CI, maps to "Failing test fails the job" — **not exercised in CI** (blocked); verified locally
- [ ] Throwaway branch breaking the asset build fails CI, maps to "Asset build breaks" — **not exercised** (blocked)
- [x] Workflow review: MongoDB service has a health check, no repository secrets referenced, maps to "Database is ready before tests" and "No production secrets in CI"
- [x] Observation: warm-cache runtime under 5 minutes (target only) (observed: 1m11s to 1m36s)
