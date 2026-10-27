# DigitalPassport

DigitalPassport is a web application for university students to record and present their digital skills. The system includes student profiles, evidence-backed skills, assessments, administrator review, and a downloadable Digital Skills Passport.

## Planned Features

- Student and administrator accounts with role-based access.
- Student profiles, skills, projects, and certifications.
- Evidence uploads and administrator review with clear record statuses.
- Predefined assessments with automatic scoring and attempt history.
- An online passport summary and a downloadable PDF.

## Technology

| Area | Technology |
| --- | --- |
| Backend | Laravel (PHP) REST API with Sanctum authentication |
| Database | MySQL |
| Frontend | Vue 3, Tailwind CSS, and shadcn-vue |

## Repository Structure

```text
backend/                    Laravel API backend
  app/Models/               Eloquent models
  app/Http/Controllers/     Request handlers
  database/migrations/      Database schema changes
  routes/api.php            API route definitions
  config/                   Application configuration
frontend/                   Vue frontend
  src/components/ui/        Reusable shadcn-vue components
  src/lib/                  API client and shared helpers
  src/pages/                Page components
  src/router/               Frontend routes
```

## Run Locally

**Requirements:** PHP 8.2+, Composer, Node.js 22.12+, npm, MySQL (via XAMPP or standalone).

### Backend (Laravel API)

```cmd
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=8000
```

> Make sure MySQL is running and the `digitalpassport` database exists before running migrations.
> Update `backend/.env` with your local MySQL credentials if needed.

### Frontend (Vue)

```cmd
cd frontend
npm ci
npm run dev
```

Open the URL printed by Vite (usually `http://localhost:5173`). The frontend proxies `/api` requests to the Laravel server at `http://127.0.0.1:8000`.

### Available API Endpoints

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/api/health` | No | Health check |
| GET | `/api/user` | Yes (Sanctum) | Get authenticated user |

## Git Workflow

`master` is the shared integration branch. Each developer works on their own branch.

1. Update your local `master` from the remote repository.
2. Switch to your branch and merge the updated `master` into it.
3. Make changes, commit them, and push your branch.
4. Open a pull request into `master` for review.
5. After a merge, all developers update from `master` before continuing work.

```cmd
git fetch origin
git switch master
git pull origin master
git switch YOUR_BRANCH
git merge master
```

Resolve any merge conflicts on your branch before pushing. Keep secrets, installed dependencies, and generated files out of commits; see `.gitignore`.
