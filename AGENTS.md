# AGENTS.md — Cave of Conspiracies (PHP)

## Project layout
- `/` contains `index.php`, `views/`, `assets/`, `lib/`
- Entry point: `index.php`
- PHP: 8.2+; DB: MySQL 8

## Setup
- No composer deps yet.
- If tests needed later, put them in `tests/` and add a `setup.sh`.

## Run (dev)
php -S 0.0.0.0:8080 -t .

## Coding guidelines
- Use PDO prepared statements.
- Keep functions in `lib/` and views in `views/`.
- Prefer small functions; no globals in views.

## Tasks Codex may do
- Add feature flags, fix bugs, write unit tests, refactor.
- For DB migrations: create SQL in `/migrations`, idempotent.

## Done criteria
- Code compiles; basic flows manually verifiable.
- If tests exist, they pass with `php -m` sanity + any test runner we add.

