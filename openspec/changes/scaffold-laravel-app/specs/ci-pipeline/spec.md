## Purpose

Defines the GitHub Actions quality gate for the Address Book app: style, static analysis and tests against MySQL on every pull request and push to main, so no change merges without passing the same checks developers run locally.

## ADDED Requirements

This document details *changes* to requirements and is additive to the [`design.md`](../../design.md) document, not a replacement.

### Requirement: CI runs on pull requests and main

The system SHALL run the CI workflow on every pull request and on every push to `main`.

#### Scenario: Pull request triggers CI

- **Given** a pull request is opened against `main`
- **When** the workflow triggers
- **Then** a CI check appears on the pull request

#### Scenario: Push to main triggers CI

- **Given** a commit lands on `main`
- **When** the push event fires
- **Then** the CI workflow runs for that commit

### Requirement: CI runs the quality checks against MySQL on PHP 8.2

The system SHALL run Pint in check mode, Larastan and the Pest suite on PHP 8.2 with a MySQL service container.

#### Scenario: All checks pass

- **Given** a branch with clean style, analysis and passing tests
- **When** CI runs
- **Then** the job succeeds

#### Scenario: Style violation fails the job

- **Given** a commit with a Pint violation
- **When** CI runs
- **Then** the job fails at the Pint step and later steps are not required to run

#### Scenario: Failing test fails the job

- **Given** a commit with a failing Pest test
- **When** CI runs
- **Then** the job fails and reports the test name

#### Scenario: Database is ready before tests

- **Given** the MySQL service container is starting
- **When** the workflow reaches the migration step
- **Then** it waits on the container health check before connecting

### Requirement: CI builds front-end assets

The system SHALL build the Vite assets in CI so a broken asset build fails the check.

#### Scenario: Asset build breaks

- **Given** a commit that breaks the asset build
- **When** CI runs
- **Then** the job fails at the build step

### Requirement: Security criteria

The system SHALL meet the non-functional criterion below.

#### Scenario: No production secrets in CI

- **Given** the workflow file
- **When** it is reviewed
- **Then** it uses only throwaway database credentials and no repository secrets

### Requirement: Performance criteria

The system SHALL meet the non-functional criterion below.

#### Scenario: Warm-cache runtime

- **Given** Composer and npm caches are warm
- **When** CI runs on an unchanged dependency set
- **Then** the job finishes in under 5 minutes (target, observed not enforced)

## Traceability

- Proposal element (GitHub Actions with MySQL) -> all requirements above
- Design Decision 7 -> CI triggers, steps and MySQL service; Decision 6 -> Pint and Larastan steps
- Requirement -> Task(s): see `tasks.md` CI group and `tests.md`
