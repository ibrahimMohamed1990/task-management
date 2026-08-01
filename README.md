# Task Management System API

A RESTful API for managing projects and tasks, built with **Laravel 12** and **Laravel Sanctum** for token authentication.

## Features

- Token-based auth (register / login / logout) via Sanctum
- Projects module — full CRUD, scoped to the authenticated user
- Tasks module — full CRUD, nested under projects, with filtering by status/priority and title search
- Dashboard endpoint with aggregate stats (projects, tasks, overdue tasks)
- Form Request validation, API Resource transformers, pagination, soft deletes
- Consistent JSON error responses and proper HTTP status codes
- Factories + seeders for realistic sample data
- Feature test suite (PHPUnit)
- Queued job + Mail notification for tasks that become overdue (bonus)

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum 4
- MySQL (or any Eloquent-supported DB)

---

## 1. Repository Layout

This repo contains the **custom application code only** — models, controllers, form requests,
resources, migrations, routes, factories, seeders, tests, and config overrides. It does not
commit the generic Laravel framework skeleton (`public/`, `artisan`, most of `config/`,
`resources/`, `storage/`), since that's identical to a stock `laravel new` project and just adds
noise to the diff.

```
app/
  Console/Commands/NotifyOverdueTasks.php
  Http/Controllers/Api/{AuthController,ProjectController,TaskController,DashboardController}.php
  Http/Requests/{Auth,Project,Task}/...
  Http/Resources/{UserResource,ProjectResource,TaskResource}.php
  Jobs/SendOverdueTaskNotification.php
  Models/{User,Project,Task}.php
  Notifications/TaskOverdueNotification.php
bootstrap/app.php            # routing + JSON exception handling
config/sanctum.php
database/
  migrations/
  factories/
  seeders/DatabaseSeeder.php
routes/{api,web,console}.php
tests/Feature/{AuthTest,ProjectTest,TaskTest,DashboardTest}.php
postman/Task-Management-API.postman_collection.json
setup.sh                     # scaffolds the full Laravel app around this code
```

## 2. Installation

> This sandbox environment could not reach `packagist.org`, so the project could not be
> `composer install`-ed here. `setup.sh` automates the two steps below for you.

**Requirements:** PHP >= 8.2, Composer, MySQL (or SQLite), Node not required.

```bash
git clone <your-repo-url> task-manager
cd task-manager
./setup.sh
```

`setup.sh` will:
1. Run `composer create-project laravel/laravel` into a temp directory to get the standard
   framework skeleton (public/, artisan, resources/, base config/, storage/).
2. Copy that skeleton into this repo **without overwriting** the custom `app/`, `database/`,
   `routes/`, `bootstrap/app.php`, `config/sanctum.php`, `composer.json`, `.env.example`, and
   `phpunit.xml` already provided here.
3. Run `composer require laravel/sanctum` and `composer install`.
4. Copy `.env.example` → `.env` and run `php artisan key:generate`.

If you'd rather do it by hand:

```bash
composer create-project laravel/laravel tmp-skeleton
composer require laravel/sanctum
# copy tmp-skeleton's public/, artisan, resources/, storage/, and remaining config/*.php
# into this repo, then:
composer install
cp .env.example .env
php artisan key:generate
```

## 3. Environment Setup

Edit `.env`:

```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database   # needed for the overdue-task notification job
```

Then:

```bash
php artisan migrate --seed
php artisan serve
```

The API is now available at `http://localhost:8000/api`.

### Sample login (from the seeder)

```
email:    demo@example.com
password: password
```

### Running the overdue-task notification job

```bash
php artisan queue:work            # in one terminal, to process queued jobs
php artisan tasks:notify-overdue  # dispatches SendOverdueTaskNotification for each overdue task
```
`tasks:notify-overdue` is also registered on Laravel's scheduler (`routes/console.php`) to run
daily at 08:00 — hook `php artisan schedule:work` (or a cron entry) up in production.

### Running tests

```bash
php artisan test
```
Tests run against an in-memory SQLite DB (see `phpunit.xml`), so no extra setup is required.

---

## 4. API Documentation

Base URL: `/api`

All authenticated endpoints require the header:
```
Authorization: Bearer <token>
Accept: application/json
```

### Auth

| Method | Endpoint            | Description                    | Auth |
|--------|----------------------|---------------------------------|------|
| POST   | `/auth/register`    | Create an account, returns token | No |
| POST   | `/auth/login`       | Log in, returns token           | No |
| POST   | `/auth/logout`      | Revoke current token            | Yes |
| GET    | `/auth/me`          | Current user profile            | Yes |

**Register**
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```
`201 Created` → `{ "message", "user", "access_token", "token_type" }`

**Login**
```http
POST /api/auth/login
{ "email": "jane@example.com", "password": "password123" }
```
`200 OK` → `{ "message", "user", "access_token", "token_type" }`
`422` on invalid credentials.

**Logout**
```http
POST /api/auth/logout
Authorization: Bearer <token>
```
`200 OK` → `{ "message": "Logged out successfully." }`

---

### Projects

All project endpoints are scoped to the authenticated user — you cannot see, update, or delete
another user's project (a `403` is returned instead).

| Method | Endpoint             | Description                       |
|--------|-----------------------|------------------------------------|
| GET    | `/projects`          | List own projects (paginated, `?status=active\|completed\|archived`, `?per_page=`) |
| POST   | `/projects`          | Create a project                  |
| GET    | `/projects/{id}`     | View a project (with its tasks)   |
| PUT    | `/projects/{id}`     | Update a project                  |
| DELETE | `/projects/{id}`     | Soft delete a project              |

**Create Project**
```http
POST /api/projects
Authorization: Bearer <token>

{
  "name": "Website Revamp",
  "description": "Redesign the marketing site",
  "status": "active"
}
```
`status` is optional (`active` | `completed` | `archived`, defaults to `active`).
`201 Created` → `{ "message", "data": { ...project } }`

**List Projects**
```
GET /api/projects?status=active&per_page=15
```
`200 OK` → `{ "data": [...], "meta": { "current_page", "last_page", "per_page", "total" } }`

---

### Tasks

Tasks are nested under a project: `/projects/{project}/tasks/...`. Ownership of the parent
project is verified on every request.

| Method | Endpoint                                 | Description |
|--------|--------------------------------------------|--------------|
| GET    | `/projects/{project}/tasks`               | List tasks (paginated, filterable) |
| POST   | `/projects/{project}/tasks`               | Create a task |
| GET    | `/projects/{project}/tasks/{task}`        | View a task |
| PUT    | `/projects/{project}/tasks/{task}`        | Update a task |
| DELETE | `/projects/{project}/tasks/{task}`        | Soft delete a task |

**Query filters on the list endpoint:**
- `?status=todo|in_progress|done`
- `?priority=low|medium|high`
- `?search=<text>` — matches against the task title
- `?per_page=<n>`
(filters can be combined, e.g. `?status=todo&priority=high&search=login`)

**Create Task**
```http
POST /api/projects/1/tasks
Authorization: Bearer <token>

{
  "title": "Fix login bug",
  "description": "Session expires too early",
  "priority": "high",
  "status": "todo",
  "due_date": "2026-08-15"
}
```
`201 Created` → `{ "message", "data": { ...task, "is_overdue": false } }`

---

### Dashboard

| Method | Endpoint      | Description |
|--------|----------------|--------------|
| GET    | `/dashboard`  | Aggregate stats for the authenticated user |

```http
GET /api/dashboard
Authorization: Bearer <token>
```
```json
{
  "data": {
    "total_projects": 6,
    "active_projects": 4,
    "total_tasks": 40,
    "completed_tasks": 12,
    "pending_tasks": 28,
    "overdue_tasks": 5
  }
}
```
"Overdue" = `due_date` is in the past and `status` is not `done`.

---

### Error format

Validation errors:
```json
{
  "message": "The given data was invalid.",
  "errors": { "name": ["The name field is required."] }
}
```
| Status | Meaning |
|--------|---------|
| 401 | Unauthenticated (missing/invalid token) |
| 403 | Authenticated but not the resource owner |
| 404 | Resource not found |
| 422 | Validation failure |
| 500 | Unhandled server error |

---

## 5. Database Schema

```
users
  id, name, email, password, timestamps

projects
  id, user_id (FK -> users), name, description, status[active|completed|archived],
  timestamps, deleted_at

tasks
  id, project_id (FK -> projects), title, description,
  priority[low|medium|high], status[todo|in_progress|done],
  due_date, overdue_notified, timestamps, deleted_at
```

Relationships:
```
User      hasMany   Project
Project   hasMany   Task
Project   belongsTo User
Task      belongsTo Project
```

Migration files live in `database/migrations/`; run `php artisan migrate` to build the schema,
or `php artisan schema:dump` after migrating to produce a SQL dump if your submission requires one.

## 6. Sample Data

`php artisan db:seed` (or `migrate --seed`) creates:
- `demo@example.com` / `password` plus 4 random users
- 3 projects per user (random status)
- Each project gets 4 mixed tasks, 2 overdue tasks, and 2 completed tasks

## 7. Postman Collection

Import `postman/Task-Management-API.postman_collection.json`. It ships with a `base_url`
variable (`http://localhost:8000/api`) and auto-captures `access_token`, `project_id`, and
`task_id` into collection variables as you run requests, so the whole flow (register → create
project → create task → filter → dashboard) works end to end without manual copy/paste.