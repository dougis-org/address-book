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

- [ ] `tests/Feature/PlatformTest.php`: PHP version is 8.3.x (`PHP_VERSION`), maps to scenario "Dependencies resolve on PHP 8.3"
- [ ] `tests/Feature/PlatformTest.php`: installed Laravel major version is 13 (`app()->version()` starts with `13.`)
- [ ] `tests/Feature/AuthRoutesTest.php`: routes `login`, `logout`, `password.request`, `password.reset` and `two-factor.login` exist, maps to "Auth routes registered"
- [ ] `tests/Feature/DependencyPolicyTest.php`: `composer.json` does not require `livewire/flux-pro`, maps to "No paid UI dependency"
- [ ] Shell check (documented in README): `composer require` of a package needing PHP 8.3 is refused, maps to "Newer-PHP-only dependency rejected"
- [ ] Starter-kit auth tests (login, logout, password reset, two-factor challenge) pass under Pest

### 2. Sail and MySQL (Tasks Group 2; spec: dev-environment)

- [ ] `tests/Feature/DatabaseTest.php`: `config('database.default')` is `mysql` and the connection can run `select 1`, maps to "Tests use MySQL"
- [ ] `tests/Feature/HomePageTest.php`: `GET /` returns 200, maps to "Environment starts"
- [ ] Manual: `sail up -d && sail artisan migrate` succeeds on a clean clone
- [ ] `tests/Feature/RepositoryHygieneTest.php`: `.env` is not tracked and `.env.example` exists with no non-placeholder secrets, maps to "Secrets not tracked"

### 3. Quality tooling (Tasks Group 3; specs: dev-environment, ci-pipeline)

- [ ] `composer lint` (Pint `--test`) exits 0 on the scaffold and non-zero on a deliberately mis-formatted file
- [ ] `composer analyse` (Larastan level 5) exits 0 on the scaffold
- [ ] `composer test` runs the Pest suite and exits 0
- [ ] README walk-through on a clean clone reproduces the above, maps to "Missing configuration is documented"

### 4. GitHub Actions CI (Tasks Group 4; spec: ci-pipeline)

- [ ] PR run is green, maps to "All checks pass" and "Pull request triggers CI"
- [ ] Push-to-main run triggers after merge, maps to "Push to main triggers CI"
- [ ] Throwaway branch with a Pint violation fails CI, maps to "Style violation fails the job"
- [ ] Throwaway branch with a failing Pest test fails CI, maps to "Failing test fails the job"
- [ ] Throwaway branch breaking the asset build fails CI, maps to "Asset build breaks"
- [ ] Workflow review: MySQL service has a health check, no repository secrets referenced, maps to "Database is ready before tests" and "No production secrets in CI"
- [ ] Observation: warm-cache runtime under 5 minutes (target only)
