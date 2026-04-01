# Task Management API

A Laravel REST API for managing tasks with priority-based ordering, status progression rules, and a daily report endpoint.


## Requirements

- PHP >= 8.1
- Composer
- MySQL 8.x
- Laravel 10.x


## Local Setup

### 1. Clone the repository

git clone https://github.com/OtienoFaithAtieno/Task-Management-API.git
cd Task-Management-API


### 2. Install dependencies

composer install

### 3. Configure environment

cp .env .env
php artisan key:generate


Edit `.env` and set your MySQL credentials:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_api
DB_USERNAME=root
DB_PASSWORD=your_password


### 4. Create the database

`sql
CREATE DATABASE task_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
`

### 5. Run migrations

`bash
php artisan migrate
`

### 6. Start the development server

`bash
php artisan serve
`

The API will be available at `http://localhost:8000`.

---

## Deployment on Railway

Railway offers free-tier MySQL and PHP hosting — ideal for this assignment.

### Steps

1. **Create a Railway account** at [railway.app](https://railway.app)

2. **New Project → Deploy from GitHub Repo** — connect your repository

3. **Add a MySQL plugin** inside the project dashboard

4. **Set environment variables** in Railway - Settings - Variables:
   
   APP_KEY=base64:YOUR_KEY_HERE        # output of: php artisan key:generate --show
   APP_ENV=production
   APP_DEBUG=false
   DB_CONNECTION=mysql
   DB_HOST=${{MYSQLHOST}}
   DB_PORT=${{MYSQLPORT}}
   DB_DATABASE=${{MYSQLDATABASE}}
   DB_USERNAME=${{MYSQLUSER}}
   DB_PASSWORD=${{MYSQLPASSWORD}}
   

5. **Add a start command** in Railway → Settings → Deploy:
   
   php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
  
6. Railway will assign a public URL 

---

## Deployment on Render (Alternative)

1. Create a **Web Service** connected to your GitHub repo
2. Add a **MySQL** database from Render's dashboard
3. Set the same environment variables as above using Render's credentials
4. Set the **Start Command**:
   ```
   php artisan migrate --force  && php artisan serve --host=0.0.0.0 --port=$PORT
   ```

---

## API Endpoints

Base URL (local): `http://localhost:8000/api`

---

### 1. Create a Task

**POST** `/api/tasks`

**Rules:**
- `title` must be unique per `due_date`
- `due_date` must be today or later
- `priority` must be `low`, `medium`, or `high`
- `status` defaults to `pending`

**Request Body:**
```json
{
  "title": "Fix login bug",
  "due_date": "2026-04-05",
  "priority": "high"
}
```

**Success Response (201):**
```json
{
  "message": "Task created successfully.",
  "data": {
    "id": 1,
    "title": "Fix login bug",
    "due_date": "2026-04-05",
    "priority": "high",
    "status": "pending",
    "created_at": "2026-03-29T10:00:00.000000Z",
    "updated_at": "2026-03-29T10:00:00.000000Z"
  }
}
```

**Error Response (422) — duplicate title on same due_date:**
```json
{
  "message": "Validation failed.",
  "errors": {
    "title": ["A task with this title already exists for the given due date."]
  }
}
```

---

### 2. List Tasks

**GET** `/api/tasks`  
**GET** `/api/tasks?status=pending`

Sorted by priority (`high - medium - low`), then `due_date` ascending.

**Success Response (200):**
```json
{
  "message": "Tasks retrieved successfully.",
  "data": [
    {
      "id": 1,
      "title": "Fix login bug",
      "due_date": "2026-04-05",
      "priority": "high",
      "status": "pending",
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

**Empty Response (200):**
```json
{
  "message": "No tasks found.",
  "data": []
}
```

---

### 3. Update Task Status

**PATCH** `/api/tasks/{id}/status`

Advances status strictly: `pending → in_progress → done`. No body required.

**Success Response (200):**
```json
{
  "message": "Task status updated to 'in_progress'.",
  "data": {
    "id": 1,
    "status": "in_progress",
    ...
  }
}
```

**Error (422) — already done:**
```json
{
  "message": "Task is already in its final status (done) and cannot be advanced further."
}
```

---

### 4. Delete a Task

**DELETE** `/api/tasks/{id}`

Only tasks with `status = done` can be deleted.

**Success Response (200):**
```json
{
  "message": "Task deleted successfully."
}
```

**Error (403) — task not done:**
```json
{
  "message": "Forbidden. Only tasks with status \"done\" can be deleted."
}
```

---

### 5. Daily Report (Bonus)

**GET** `/api/tasks/report?date=2026-04-01`

Returns counts grouped by priority and status for all tasks with the given `due_date`.

**Success Response (200):**
```json
{
  "date": "2026-04-01",
  "summary": {
    "high":   { "pending": 2, "in_progress": 1, "done": 0 },
    "medium": { "pending": 1, "in_progress": 0, "done": 3 },
    "low":    { "pending": 0, "in_progress": 0, "done": 1 }
  }
}
```

---

## cURL Examples

```bash
# Create task
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Write tests","due_date":"2026-04-10","priority":"high"}'

# List all tasks
curl http://localhost:8000/api/tasks \
  -H "Accept: application/json"

# List pending tasks only
curl "http://localhost:8000/api/tasks?status=pending" \
  -H "Accept: application/json"

# Advance task status (id=1)
curl -X PATCH http://localhost:8000/api/tasks/1/status \
  -H "Accept: application/json"

# Delete a done task (id=3)
curl -X DELETE http://localhost:8000/api/tasks/3 \
  -H "Accept: application/json"

# Daily report
curl "http://localhost:8000/api/tasks/report?date=2026-04-01" \
  -H "Accept: application/json"
```

---

## Project Structure

```
Task Management API/
  http/
    Controller/
        TaskController.php   # All API logic
    requests/
        storeTask.php
        updateStatus.php
  model/
    Task.php                 # Eloquent model with status transition map
  tasksTable.php
  api.php                    #All API routes
```

---

## Business Rules Summary

Rule | Implementation |
---
No duplicate title per due_date | DB unique constraint + Laravel validation |
due_date must be today or later | `after_or_equal:today` validation rule |
Priority sort: high - medium - low | `ORDERBY FIELD(priority, 'high','medium','low')` |
Status progression: pending-in_progress-done only | Transition map in `Task::$statusTransitions` |
Only `done` tasks can be deleted | 403 returned otherwise |
Report route before `{id}` routes | Route ordering in `api.php` prevents conflict |
