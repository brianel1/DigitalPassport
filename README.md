# DigitalPassport

DigitalPassport is a web application project for university students to record and present their digital skills. The planned product includes student profiles, evidence-backed skills, assessments, administrator review, and a downloadable Digital Skills Passport.

The repository contains a working frontend and API scaffold. Product features and the database schema have not been implemented yet.

## Planned features

- Student and administrator accounts with role-based access.
- Student profiles, skills, projects, and certifications.
- Evidence uploads and administrator review with clear record statuses.
- Predefined assessments with automatic scoring and attempt history.
- An online passport summary and a downloadable PDF.

## Technology

| Area | Technology |
| --- | --- |
| Backend | PHP and a JSON REST API |
| Database | MySQL |
| Frontend | Vue, Tailwind CSS, and shadcn-vue |

## Repository structure

```text
backend/
  public/              API entry point and local server router
  src/Controllers/     Request handlers
  src/Database/        MySQL connection setup
  src/Http/            JSON response and routing helpers
  database/migrations/ Future database changes
frontend/
  src/components/ui/  Reusable shadcn-vue components
  src/lib/            API client and shared helpers
  src/pages/          Page components
  src/router/         Frontend routes
```

## Run locally

Requirements: PHP 8.2 or later with `pdo_mysql`, Composer, Node.js 22.12 or later, npm, and MySQL for future database-backed features.

From the repository root, start the API in one `cmd` terminal:

```cmd
cd backend
composer install
copy .env.example .env
composer run serve
```

Update `backend/.env` with your local MySQL settings. The default database name is `digitalpassport`. Create that database in MySQL before adding database-backed features. The current health endpoint does not require a database connection.

Start the frontend in a second `cmd` terminal:

```cmd
cd frontend
npm ci
npm run dev
```

Open `http://127.0.0.1:5173`. The frontend forwards `/api` requests to the PHP server at `http://127.0.0.1:8000`. `GET /api/health` is the only API endpoint in this scaffold.

## Git workflow

`master` is the shared integration branch. Each developer works on their own branch and pushes commits to that branch. The team does not use separate `dev` or `staging` branches.

1. Update your local `master` from the remote repository.
2. Switch to your branch and merge the updated `master` into it.
3. Make changes, commit them, and push your branch.
4. Open a pull request into `master` for review when practical. The team may merge directly when it agrees that review is not needed.
5. After a merge, all developers update from `master` before continuing work.

To update an existing developer branch after the remote repository is connected:

```cmd
git fetch origin
git switch master
git pull origin master
git switch YOUR_BRANCH
git merge master
```

Resolve any merge conflicts on your branch before pushing. Keep secrets, installed dependencies, and generated files out of commits; see `.gitignore`.
