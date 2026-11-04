# DigitalPassport

DigitalPassport is a web application for university students to record and present their digital skills. The system combines verified student records with job matching and CV preparation tools for early career planning.

## Planned Features

- Student and administrator accounts with role-based access.
- Student profiles, skills, projects, and certifications.
- Evidence uploads and administrator review with clear record statuses.
- Predefined assessments with automatic scoring and attempt history.
- An online passport summary and a downloadable PDF.
- AI job recommendations based on verified skills, projects, certifications, assessments, and student preferences.
- Ranked job matches with clear reasons, matched skills, and missing skills.
- An editable ATS friendly CV with PDF and Word downloads.

## Job Listing Strategy

JobStreet is the preferred job source. Its official SEEK API does not provide public job search access, so the application uses a provider based design:

1. Use an approved JobStreet integration when suitable access is available.
2. Use the Jooble Malaysia API as the main live fallback.
3. Use Remotive for suitable remote roles.
4. Use the latest cached listings from the configured providers while a provider is temporarily unavailable.

Every listing records its source and application URL. Provider results are cached to reduce API usage and keep the recommendation feature available during temporary provider outages.

## Technology

| Area | Technology |
| --- | --- |
| Backend | Laravel (PHP) REST API with Sanctum authentication |
| Database | MySQL |
| Frontend | Vue 3, Tailwind CSS, and shadcn-vue |
| AI service | Ollama with a Qwen model, with a verified profile fallback when the local model is unavailable |
| Job sources | JobStreet priority, Jooble Malaysia, and Remotive |

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

### Local AI and job data

Install Ollama separately, start its local service, and download the selected model:

```cmd
ollama serve
ollama pull qwen3:8b
```

Set these values in `backend/.env`:

```env
OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen3:8b
OLLAMA_TIMEOUT=90
```

The recommendation and CV services use the local model when `OLLAMA_MODEL` is configured. If the model is unavailable, they use the verified profile and explainable rules already stored in the application. No unverified facts are added.

Refresh the cached job listings with:

```cmd
cd backend
php artisan jobs:sync --keywords="software developer" --location="Malaysia"
```

### Available API Endpoints

| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| GET | `/api/health` | No | Health check |
| GET | `/api/user` | Yes (Sanctum) | Get authenticated user |
| POST | `/api/auth/register` | No | Register a student account |
| POST | `/api/auth/login` | No | Sign in and create an API token |
| POST | `/api/auth/logout` | Yes (Sanctum) | Revoke the current API token |
| GET, PUT | `/api/student/profile` | Student | Read or update the student profile |
| GET, POST | `/api/student/skills` | Student | List or create skills |
| PUT, DELETE | `/api/student/skills/{skill}` | Student | Update or delete an owned skill |

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
