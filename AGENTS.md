# SYNC PROJECT

Practice project for offline data management and synchronization. This project const of a simple CRUD.

## Stack

### Frontend

- Blade templates
- Livewire v4
- Alpine.js
- Service workers
- IndexedDB

### Backend

- Laravel Framework v13
- MySQL 8.0

### Infraestructure

- Docker v29.6.1
- Docker Compose v5.3

## Commands

### Development

`docker compose exec app pnpm dev`

### Production

`docker compose exec app pnpm build`

### Test

`docker compose exec app php artisan test`

## Conventions

- **Git Language:** All commit messages and name branchs MUST be written strictly in English.
- **Commits Format:** Use Conventional Commits.
    - _Correct:_ `feat(auth): add JWT validation middleware`
    - _Incorrect:_ `fix: corregido el error al iniciar sesión`

## Do Not Do

- Do not make a single giant commit at the end of a task; keep your commits atomic.
- Do not use past-tense verbs in commit messages (e.g., do not use `added`, use `add`).
- Do not mix code refactoring with new logic in the same commit.

## Workflow

- Before starting a non-trivial task, propose a plan and wait for my OK.
- One task at a time; when finished, tell me what you changed so I can review it.
- If you are not at least 80% sure, ask. Do not make things up.
- **Git Flow & Branches:** Always work on independent feature branches (`feature/feature-name` or `bugfix/bug-name`) created from `dev`. Never commit directly to `main` or `dev`.
- **Atomic Commits (Mandatory):** Make a commit for each logical change or completed subtask. A commit must contain a single unit of work (e.g., create the migration, then another commit for the model, and another for the controller). Do not accumulate changes from different files or purposes into a single commit.
- **Pre-validation:** Before every commit, run `[comando test]` and `[comando lint]`. If they fail, do not make the commit.
