# Tasks

Ownership metadata:

- Implementer: Doug Hubbard (with Claude)
- Reviewer(s): Doug Hubbard
- Required approvals: 1 (repo owner), plus a green CI check

All paths are relative to the project root. Work happens in `.worktrees/scaffold-laravel-app`.
Strict BDD/TDD: for every group, write the failing test from `tests.md` first, confirm it fails, then implement. Do not start a group's implementation before its test step is done.

## Preparation

- [x] **Step 1: Sync default branch:** from the primary checkout, `git fetch origin` and confirm `origin/main` is current
- [x] **Step 2: Working branch published:** `git branch -r` lists `origin/scaffold-laravel-app` (created during propose); if missing, `git push -u origin scaffold-laravel-app`
- [x] **Step 3: Docs/proposal approved:** owner has approved `proposal.md`, `design.md` and `specs/`

## Preflight

- [x] **Verify `pr-review-toolkit:review-pr` is available**: check the skills list for `pr-review-toolkit:review-pr`. If missing, halt, tell the user the plugin is required, give install guidance, and do not proceed until they confirm.
- [x] **Verify Docker works:** `docker version` and `docker compose version` succeed
- [x] **Confirm open questions:** MongoDB architecture decision recorded (Doug), host `ext-mongodb` check noted for #8, starter-kit choice (Livewire) confirmed

## Execution

- [x] **Step 1: Confirm worktree:** `git worktree list` shows `.worktrees/scaffold-laravel-app`; `cd` into it. Never check out another branch in the primary checkout.
- [x] **Step 2: Branch on remote:** `git ls-remote --heads origin scaffold-laravel-app` returns a ref
- [x] **Issue lifecycle: mark in-progress:** run `gh issue edit 1 --add-label "in-progress"`; discover the GitHub Project (`gh project list --owner dougis-org --format json`), resolve the "In Progress" option via `gh project field-list`, move the item with `gh project item-edit`. If no project item is found, warn and continue. If the token lacks `project` scope, tell the user to run `gh auth refresh -s project` and skip only the project move.

### Group 1: Application scaffold (dev-environment)

- [x] **Test first:** write the failing checks in `tests.md` section 1 (version, route list, no `flux-pro`); verify they fail with no app present
- [x] Generate the app with the Laravel installer in a PHP 8.3 container: Livewire starter kit, Pest, no WorkOS, Laravel 13.x; verify `composer show laravel/framework` prints 13.x
- [x] Set `"php": "^8.3"` and `config.platform.php` 8.3 in `composer.json`; verify `composer update --dry-run` stays on 13.x
- [x] Confirm no `livewire/flux-pro` in `composer.json`; verify with `grep`
- [x] Commit the pristine generated app separately from later config edits; verify with `git log --stat`
- [x] Group verification: tests from section 1 pass

### Group 2: Sail and MongoDB (dev-environment)

- [x] **Test first:** write the failing database-driver test and a persistence test (kit user registration stored in MongoDB) (`tests.md` section 2); verify they fail
- [x] Install Sail with MongoDB (`sail:install --with=mongodb`), runtime PHP 8.3, and add `ext-mongodb` to the runtime if missing; verify `compose.yaml` has a `mongodb` service and `8.3` runtime, and `php -m` lists `mongodb`
- [x] Install `mongodb/laravel-mongodb` ^5.11; set `mongodb` as the default connection, base the `User` model on the package, move cache/session/queue off SQL tables, and add a `RefreshMongoDatabase` test trait; verify the kit's auth tests pass
- [x] Update `.env.example` for MongoDB (`MONGODB_URI`, `MONGODB_DATABASE`, placeholders only); verify `sail up -d` starts and `sail artisan test` can reach it
- [x] Configure Pest to run against MongoDB (`phpunit.xml` env, `testing` database); verify `sail artisan test` passes and the driver test is green
- [x] Add `.worktrees/` to `.gitignore`; verify `git check-ignore .worktrees` succeeds

### Group 3: Quality tooling (dev-environment, ci-pipeline)

- [x] **Test first:** run `pint --test` and `phpstan` before configuring them; verify they fail or are missing
- [x] Add Pint config (`pint.json`) and Larastan (`phpstan.neon`, level 7 as shipped by the kit); verify both exit 0 on the scaffold
- [x] Add composer scripts `lint`, `analyse`, `test`; verify each runs from `sail composer`
- [x] Write README: setup, run, test, lint, version policy (PHP 8.3); verify a clean-clone walk-through of the README works

### Group 4: GitHub Actions CI (ci-pipeline)

- [x] **Test first:** open a draft PR with a deliberately failing check or push a branch with a lint error; verify the missing workflow means no CI result (red/absent baseline)
- [x] Write `.github/workflows/ci.yml`: triggers on pull_request and push to main, PHP 8.3 with required extensions including `mongodb`, MongoDB service with health check, Composer and npm caches, asset build, Pint, Larastan, Pest
- [ ] Verify the workflow is green on the PR
- [ ] Verify a deliberate lint error and a deliberate failing test each fail CI on a throwaway branch (delete the branch afterwards)
- [x] Verify the workflow uses no repository secrets (`grep -n secrets .github/workflows/ci.yml` returns nothing)

## Pre-Commit Code Review

- [ ] **Before every commit**, spawn a dedicated sub-agent to run the `openspec-review-code` skill. The primary agent must automatically apply all clearly-correct findings without stopping or asking, re-run tests, then commit.

## Validation

- [x] Run unit/feature tests: `./vendor/bin/sail artisan test`
- [x] Run style check: `./vendor/bin/sail composer lint`
- [x] Run static analysis: `./vendor/bin/sail composer analyse`
- [x] Run asset build: `./vendor/bin/sail npm run build`
- [x] Run `openspec validate scaffold-laravel-app --strict`
- [x] Security/quality: confirm `.env` is untracked and no secrets are in the diff
- [x] All completed tasks marked complete

## Remote push validation

Determine whether the change is docs-only (`git diff --name-only origin/main` and check every file ends in `.md`).

- Full path (any non-`.md` file changed): run unit, feature and regression tests, lint, analyse and the asset build; all must pass.
- Docs-only path: run the build only.

If any required step fails, fix it before pushing.

## PR and Merge

- [x] Confirm the `openspec-review-code` sub-agent ran and all findings were addressed before the final commit
- [ ] Commit all changes and push to the working branch
- [ ] Open a PR from `scaffold-laravel-app` to `main`; the body MUST include `Closes #1`
- [ ] **Issue lifecycle: mark in-review:** `gh issue edit 1 --add-label "in-review" --remove-label "in-progress"`, then move the project item to "In Review" (same discovery as above; warn and skip if not found)
- [ ] Open the PR, then immediately start in parallel (no fixed sleeps): (a) a sub-agent running `pr-review-toolkit:review-pr`; (b) `gh pr checks <PR-URL> --required --watch --fail-fast`
- [ ] **Triage loop until review-pr reports zero findings, required checks pass, and no thread is unresolved.** Each iteration: collect failing checks (`gh pr checks <PR-URL> --required --json name,bucket,link`, logs via `gh run view <run-id> --log-failed`), unresolved review threads (GraphQL `reviewThreads`), and review-pr findings; fix in one commit; run Remote push validation; push once; reply to and resolve each addressed thread; re-watch checks. At most two fix iterations; if the third review still raises issues, stop and escalate to the user with the remaining items.
- [ ] Enable auto-merge only after the triage loop is clean: `gh pr merge <PR-URL> --auto --merge` (never `--admin`)
- [ ] Watch for merge: poll `gh pr view <PR-URL> --json state` until MERGED; if CLOSED, notify the user

Blocking resolution flow:

- CI failure: fix, commit, validate locally, push, re-run checks
- Security finding: remediate, commit, validate locally, push, re-scan
- Review comment: address, commit, validate locally, push, confirm resolved

## Post-Merge

- [ ] From the primary checkout: `git checkout main` and `git pull --ff-only`
- [ ] Verify the merged changes appear on `main` (`composer.json` and `.github/workflows/ci.yml` present)
- [ ] Mark all remaining tasks complete (`- [x]`)
- [ ] Update repository documentation impacted by the change (README)
- [ ] Sync approved spec deltas into `openspec/specs/dev-environment/spec.md` and `openspec/specs/ci-pipeline/spec.md`; update relative links to `../../changes/archive/YYYY-MM-DD-scaffold-laravel-app/design.md` and `.../tasks.md`
- [ ] Archive: move `openspec/changes/scaffold-laravel-app/` to `openspec/changes/archive/YYYY-MM-DD-scaffold-laravel-app/` in a single commit that includes both the new location and the deletion of the old
- [ ] Confirm the archive directory exists and the original is gone
- [ ] Create a doc branch: `git checkout -b doc/archive-YYYY-MM-DD-scaffold-laravel-app` and `git push -u origin doc/archive-YYYY-MM-DD-scaffold-laravel-app`
- [ ] Open a PR titled `docs: archive scaffold-laravel-app (YYYY-MM-DD)`; do not push directly to `main`
- [ ] Immediately enable auto-merge: `gh pr merge <DOC-PR-URL> --auto --merge`
- [ ] Monitor the doc PR until it merges
- [ ] Remove the worktree and prune: `git worktree remove .worktrees/scaffold-laravel-app`, `git fetch --prune`, `git branch -D scaffold-laravel-app doc/archive-YYYY-MM-DD-scaffold-laravel-app`
