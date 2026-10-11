## Context

- Relevant architecture: Greenfield. The repo root currently holds `docs/`, `openspec/`, `scripts/`, agent tooling dirs and `.github/` (skills, prompts). The Laravel app is created at the repo root; those directories are left alone.
- Dependencies: Docker (only local runtime available; no PHP or Composer on the dev machine), GitHub Actions, Laravel 13, Livewire starter kit (Fortify, Livewire 4, Flux UI, Tailwind), Pest, Pint, Larastan, MySQL.
- Interfaces/contracts touched: `composer.json` (PHP and framework constraints), `.env.example`, `compose.yaml`, `.github/workflows/ci.yml`. Later issues build on these.

## Goals / Non-Goals

### Goals

- One-command local environment (Sail + MySQL) that matches production's PHP 8.3.
- A CI gate that runs the same checks as local, against MySQL.
- Test-first workflow ready: Pest installed, an example feature test per starter-kit auth flow passing.

### Non-Goals

- Customizing auth, adding domain code, or deploying (issues #2 to #8).

## Decisions

### Decision 1: Laravel 13 on PHP 8.3, pinned in three places

- Chosen: `composer.json` has `"php": "^8.3"` and `config.platform.php = "8.3.0"`; Sail runtime is `8.3`; CI uses `shivammathur/setup-php` with `8.3`.
- Alternatives considered: Laravel 12 on PHP 8.2 (matches today's host, but its Livewire kit has no Fortify/2FA); unpinned versions.
- Rationale: Latest Laravel (13; no 14 exists) and the Fortify-based kit with 2FA. Composer resolves dependencies for the platform PHP, so a dependency that needs 8.4 can't slip in.
- Trade-offs: The host currently runs PHP 8.2, so deployment (#8) needs the host upgraded to 8.3 first.

### Decision 2: Livewire starter kit (non-WorkOS)

- Chosen: Official Livewire starter kit with Laravel's built-in Fortify auth.
- Alternatives considered: React/Vue/Svelte (Inertia) kits (heavier JS toolchain, TypeScript, Wayfinder codegen); bare Fortify with hand-written views (more code to own); WorkOS variant (external auth provider, not wanted); Jetstream (no longer developed).
- Rationale: PHP-first, small JS footprint, ships login, password reset and TOTP 2FA, which cover issues #2 to #4. Frontend assets are built in CI, so the host needs no Node.
- Trade-offs: Flux UI has free and Pro components; we use only free ones. Starter-kit code is ours to maintain after generation.

### Decision 3: Generate the app in a container, commit the result

- Chosen: Run the Laravel installer from a PHP 8.3 Docker container with a pinned `laravel/laravel` 13.x release, answering prompts non-interactively (Livewire, Pest, no WorkOS). Then install Sail with `sail:install --with=mysql`.
- Alternatives considered: Install PHP/Composer on the host; hand-assemble files.
- Rationale: Reproducible without changing the dev machine, and keeps PHP at 8.3.
- Trade-offs: First step is slower than a native installer. The installer defaults to the newest framework; we explicitly constrain it and verify with `composer show laravel/framework`.

### Decision 4: MySQL for local, CI and tests

- Chosen: Sail `mysql` service; CI uses a `mysql` service container; Pest feature tests use `RefreshDatabase` against MySQL (separate `testing` database).
- Alternatives considered: SQLite in-memory for tests (faster, but diverges from production).
- Rationale: Avoids "passes on SQLite, fails on MySQL" for migrations and collation. Version matched to the host once known (default 8.0).
- Trade-offs: Slower test runs; mitigated by running tests in parallel later if needed.

### Decision 5: Pest as the test framework

- Chosen: Pest with the Laravel plugin; starter-kit tests are Pest-style.
- Alternatives considered: PHPUnit directly.
- Rationale: Stated preference. Pest runs PHPUnit underneath, so tooling compatibility stays.
- Trade-offs: Contributors need to know Pest syntax.

### Decision 6: Quality tools: Pint (check mode) and Larastan

- Chosen: `pint --test` and `phpstan analyse` with Larastan at level 5 initially, config committed; both run locally via composer scripts and in CI.
- Alternatives considered: PHP_CodeSniffer; higher PHPStan level from the start.
- Rationale: Pint is Laravel's default; level 5 passes on generated code without noise and can be raised later.
- Trade-offs: Lower level misses some issues at first.

### Decision 7: Single CI workflow with cached dependencies

- Chosen: `.github/workflows/ci.yml` triggered on `pull_request` and push to `main`. Steps: checkout, setup PHP 8.3 (required extensions), setup Node and build assets, Composer install with cache, copy `.env.example`, generate key, Pint, Larastan, Pest against the MySQL service with a health check.
- Alternatives considered: Separate workflows per check; running inside Sail in CI.
- Rationale: One status check is easy to make required; native `setup-php` is faster than running Docker Compose in CI.
- Trade-offs: CI environment differs slightly from Sail (mitigated by identical PHP and MySQL versions).

## Proposal to Design Mapping

- Proposal element: PHP 8.3 pinned everywhere
  - Design decision: Decision 1
  - Validation approach: CI fails if the lockfile needs a newer PHP; test asserts `PHP_VERSION` is 8.3 in CI and Sail.
- Proposal element: Official starter kit with 2FA
  - Design decision: Decision 2
  - Validation approach: Starter-kit auth tests (login, reset, 2FA challenge routes) pass in Pest.
- Proposal element: Sail with MySQL
  - Design decision: Decisions 3 and 4
  - Validation approach: `sail up -d` then `sail artisan migrate` then `sail artisan test` succeed.
- Proposal element: Pest, Pint, Larastan
  - Design decision: Decisions 5 and 6
  - Validation approach: Composer scripts `test`, `lint`, `analyse` exit 0.
- Proposal element: GitHub Actions with MySQL
  - Design decision: Decision 7
  - Validation approach: Workflow runs green on the PR; failing a check fails the job.

## Functional Requirements Mapping

- Requirement: Developer can start the app locally with one command.
  - Design element: Sail `compose.yaml`.
  - Acceptance criteria reference: specs/dev-environment/spec.md
  - Testability notes: Manual verification plus CI smoke test of `/` returning 200.
- Requirement: Tests run against MySQL.
  - Design element: Decision 4.
  - Acceptance criteria reference: specs/dev-environment/spec.md, specs/ci-pipeline/spec.md
  - Testability notes: A test asserts the default connection driver is `mysql`.
- Requirement: CI blocks merges on style, static analysis, or test failures.
  - Design element: Decision 7.
  - Acceptance criteria reference: specs/ci-pipeline/spec.md
  - Testability notes: Verified by a deliberately failing commit on a throwaway branch.

## Non-Functional Requirements Mapping

- Requirement category: reliability
  - Requirement: Local, CI and host share PHP 8.3 and compatible MySQL.
  - Design element: Decisions 1 and 4.
  - Acceptance criteria reference: specs/dev-environment/spec.md
  - Testability notes: Version assertions in CI.
- Requirement category: security
  - Requirement: No secrets committed; `.env` ignored; CI uses no production secrets.
  - Design element: `.env.example` with placeholders; CI uses throwaway credentials.
  - Acceptance criteria reference: specs/ci-pipeline/spec.md
  - Testability notes: Review of the workflow and `.gitignore`.
- Requirement category: operability
  - Requirement: CI completes in a reasonable time (target under 5 minutes).
  - Design element: Composer and npm caching.
  - Acceptance criteria reference: specs/ci-pipeline/spec.md
  - Testability notes: Observe the first runs; not a hard gate.

## Risks / Trade-offs

- Risk/trade-off: Installer pulls Laravel 13.
  - Impact: App incompatible with host PHP.
  - Mitigation: Explicit constraint and platform pin; verify before commit.
- Risk/trade-off: Flux Pro dependency.
  - Impact: License requirement.
  - Mitigation: Free components only; check `composer.json` for `livewire/flux-pro`.
- Risk/trade-off: Generated starter-kit code is a large first diff.
  - Impact: Hard to review.
  - Mitigation: Commit the pristine generated app first, then config changes in separate commits.

## Rollback / Mitigation

- Rollback trigger: The scaffold PR cannot go green or breaks the repo layout.
- Rollback steps: Close the PR / revert the merge commit; the repo returns to its docs-only state.
- Data migration considerations: None (no deployed data or schema).
- Verification after rollback: `main` has no `composer.json` and CI workflow is absent.

## Operational Blocking Policy

- If CI checks fail: fix on the branch; do not merge or disable the check. Flaky infrastructure failures may be re-run once, then investigated.
- If security checks fail: treat as blocking; no merge until fixed or the finding is documented as a false positive in the PR.
- If required reviews are blocked/stale: re-request review after 2 business days; escalate to the repo owner (Doug) after 5.
- Escalation path and timeout: owner decides; if blocked more than a week, reduce scope or split the PR.

## Open Questions

- Host MySQL/MariaDB version (default 8.0 until confirmed).
- Livewire vs Inertia kit confirmation.
- Larastan starting level (default 5).
