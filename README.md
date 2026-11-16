# Skills & Career

Skills & Career is the temporary product descriptor while the team chooses a final name. This web application helps university students record and present their skills, combining verified records with job matching and CV preparation for early career planning.

## What works now

- Student and administrator accounts with role-based access.
- Student profiles, skills, projects, and certifications.
- Evidence uploads, owned previews, edit/resubmit, expiry and administrator review history with changed-field comparisons.
- Versioned assessments with server scoring, deadline recovery, explicit skill mappings and best results per assessment family.
- An online skills profile and downloadable PDF, plus optional selected-field sharing with an expiring/revocable link and QR.
- Evidence-based fit scoring from current verified skills, projects, certificates and mapped assessment results.
- One-action job search, automatically remembered filters, an Ollama shortlist, matched/missing skills and relevance feedback.
- Public-page job collection from Hiredly, Jobstore, Ricebowl and Maukerja, with source filters and duplicate removal.
- Create a CV from verified records, or upload PDF/DOCX/TXT and organise its extracted text with local AI.
- Edit and preview CVs, review content and evidence gaps, tailor separate versions, and download PDF or Word.
- Navi: an original animated career companion with CV-aware chat, interview practice, saved conversations and optional read-aloud.
- Data-driven onboarding and since-last-visit changes; admin review-age metrics and AI, OCR, provider, sync and export health.

The application uses saved user records, actual provider listings and local Ollama. Temporary QA accounts are removed after verification; the explicitly fictional demo student is retained for development testing. Setup instructions and practical limits are documented below. Personal planning notes and generated QA reports are kept locally and excluded from Git.

## Create / upload CV and Navi

Open **Create / upload CV** in the student workspace. Choose **Create a CV**, name the document and confirm to start from verified records, or **Upload your CV** and enter its name before importing. Opening the create dialog does not create a record. **Save name** renames the selected document without making another version; documents are ordered from v1 upward. Uploaded claims remain separate from verified skills.

1. Upload PDF, DOCX or TXT (up to 2 MB), or paste 50–20,000 characters.
2. Check **Source text**, correct extraction errors and select **Organise with AI**.
3. Use the section tabs to edit personal details, skills, experience, projects, education, certificates and additional information. Long sections show two entries per page. The live preview scrolls independently. Draft edits remain in the same browser tab across navigation and reloads; Save persists them to your account.
4. Select **Review with AI** for six document checks, comparison against current verified evidence, and writing advice. Suggested rewrites appear only when they pass conservative checks; review before applying, or undo them. Original facts and generation/import provenance are retained. This is not an ATS/hiring score.
5. Download your general CV as PDF or Word. Full name and a valid email are required for export; incomplete project descriptions can still be saved and reviewed.
6. For a specific role, go to **Job matches → Tailor CV**, choose a saved CV or verified records, then **Create & review version**. A separate version opens with role-specific review; the original is preserved.
7. **Ask Navi** starts with an English or Bahasa Melayu choice, then welcomes questions about CVs, skills, learning, portfolios, internships, applications and careers. Ask for interview practice within the same chat; there is no mode or context dropdown. The general CV editor has no interview shortcut or role dropdown.

**Extraction:** text PDFs, DOCX and TXT are supported; scanned PDF pages use local RapidOCR when installed (up to ten PDF pages and 20,000 extracted characters). The source view labels local OCR so the student can correct recognition errors before organising. Encrypted/unreadable PDFs receive an actionable error and the paste-text alternative. Complex layouts can reorder text. AI can omit details or give weak advice; inspect the extracted source and each suggestion. Review checks compare content and recognised evidence terms, not professional competence. If Ollama is unavailable, extraction, manual editing, exports and basic document checks still work; the interface labels the fallback.

Navi starts with an English / Bahasa Melayu choice and uses one current chat. The Conversation and Context selectors are removed. The current chat restores within the browser session; New chat clears it and returns to language selection. General career, learning and interview questions do not pass through a separate intent classifier. Real chat roles retain follow-ups, reviewed topic notes ground common facts, and invented percentages in CV/project wording are retried or replaced with a labelled writing-guide response. Clear feature requests keep safe clickable destinations. Personal records remain scoped to the signed-in user; a CV's Ask Navi action supplies its owned document implicitly. Safe Markdown, optional browser speech, reduced motion and desktop resizing up to 50vw remain available. This improves reliability but does not make the local model infallible.

### Workspace colour and progress update — 16 November 2026

- Navy navigation in both themes, including the mobile menu and collapsed sidebar. Overview places four neutral record cards with blue icon accents before the next step; a lone insight fills the available width.
- Neutral panels and blue icon/action accents distinguish Create/Upload CV and personal details. Inputs and the actual CV document remain white with dark text.
- `FileUpload.vue` is shared by project evidence, certifications and CV uploads: drag/drop or keyboard file picker, file size/name, remove/replace, and inline validation. Selecting a file does not upload it until submission.
- `TaskProgress.vue` uses an accessible modal with a blurred backdrop, trapped focus and blocked background interactions. CV extraction/organisation/review, job search and tailoring use a minimum 2.8-second presentation. After the response arrives, it explicitly shows a completed state while opening results. Saved job searches use a shorter restoration state without claiming a new AI search. No fabricated completion percentages.
- No database migration, SQL import or service restart is required for this update.

Clear navigation questions use an English/Malay product guide without a separate AI classifier. For example, **where do I upload my CV?** returns a prominent **Upload your CV** link that opens and scrolls to the upload panel. Create opens the naming dialog without creating a document; review preserves the requested owned CV. Other destinations cover skills, projects, certificates, personal details, skills profile, assessments, job matches and the appropriate admin pages. All links come from a role-specific server allowlist. **Context used** and the context selector are removed. A document's Ask Navi action supplies that document implicitly; personal CV questions otherwise use the most recently saved owned CV.

### Updating an existing installation

On this machine the migrations through `2026_11_16_110000_support_decimal_salary_preferences` are already applied, `qwen3:4b` (document tasks), `qwen3.5:4b` (Navi chat) and local OCR are configured. **Do not delete the database or reimport SQL.** The two configured models are already installed on this machine; no paid API key is required. For another existing checkout, install the backend/frontend dependencies and run migrations in CMD:

```cmd
cd backend
composer install
php artisan migrate
cd ..\frontend
npm ci
```

Keep using the services you already run. Reload the browser after frontend changes. The new dependency is [smalot/pdfparser](https://github.com/smalot/pdfparser) for PDF text extraction; Ollama supports the [structured JSON outputs](https://ollama.com/blog/structured-outputs) used by review and chat.

### Local OCR on another machine

From the repository root, use Python **3.11** for the pinned OCR environment (the installed Python 3.13 on this machine is not compatible with these pinned packages):

```cmd
py -3.11 -m venv .venv-ocr311
.venv-ocr311\Scripts\python.exe -m pip install -r scripts\ocr_requirements.txt
```

Laravel discovers `.venv-ocr311\Scripts\python.exe` automatically on Windows. Set `OCR_PYTHON` in `backend/.env` only if using another executable path; clear a cached Laravel config after changing configuration. The recogniser runs locally, with bundled model files; CV documents are not sent to an external OCR service. Dependencies: [RapidOCR](https://github.com/RapidAI/RapidOCR) and [pypdfium2](https://pypdfium2.readthedocs.io/en/stable/python_api.html). The admin health panel reports whether the configured OCR executable is present. A functional extraction check is available as `.venv-ocr311\Scripts\python.exe scripts\test_local_ocr.py`.

A terminal prefix such as `(.venv-ocr)` means that terminal has activated a Python virtual environment. It is not an error or a running service. Type `deactivate` in that terminal to leave it. The application calls the OCR executable directly, so activating an environment is unnecessary for Laravel or Vite. This machine also has an older `.venv-ocr` folder; the configured default is `.venv-ocr311`.

### Optional skills-profile sharing

In **Skills profile**, select the sections, preview them, choose a 1-, 7- or 30-day lifetime, then create the link/QR. Nothing is public before this action. Name is included; email, phone, student ID and private evidence files are excluded. The public view reads current eligible records and stops working after expiry, revocation or account suspension. Revoke a link from the same panel. A localhost URL only works on the same machine; sharing with another device requires the deployed/reachable site URL.

## Job Listing Strategy

The new Malaysia sources read public HTML already delivered by [Hiredly](https://my.hiredly.com/jobs), [Jobstore](https://www.jobstore.com/my/jobs/q-software.html), [Ricebowl](https://www.ricebowl.my/jobsearch/software-jobs) and [Maukerja](https://www.maukerja.my/en/jobsearch/software-jobs). They do not call those sites' private APIs or require API keys. A real sync on **16 November 2026** produced **1,000 catalogue results after eligibility and duplicate checks**. Counts change with availability and user filters; the interface reads the current count from the database.

**JobStreet:** the public-search adapter is present, but the server received HTTP 403 during verification and imported **zero** JobStreet listings. The app provides **Open JobStreet** for a direct search with the user's own account. A jobseeker login is not automatically permission or technical access for a server integration. The type of access available still needs to be established; the app does not import a logged-in session or bypass restrictions. Admin health displays the actual restricted status. The existing Remotive feed remains available for worldwide/Asia-eligible remote roles. Jooble is optional and requires `JOOBLE_API_KEY`.

Default collection covers software, data, internships, engineering, accounting and marketing over three search pages each. Hiredly uses nine recent catalogue pages because its public server-rendered catalogue does not apply those keyword parameters. Public pages are cached for 30 minutes; requests are bounded and pause between pages. A blocked provider stops collecting while successful providers and already fetched rows remain available. Full descriptions are fetched for up to 24 Ricebowl/Maukerja listings per source per sync; summary-only results remain **Not assessed**. Hiredly scoring uses published skill tags, not its AI-written summary. Jobstore platform salary estimates are excluded.

Listings not fetched within 14 days, past a known expiry or confirmed unavailable with HTTP 404/410 are excluded. Clearly foreign or country-restricted remote roles are excluded. Ricebowl/Maukerja shared IDs and repeated employer/title/location records are consolidated conservatively; cross-postings with different names or locations can still appear. Every result retains its source and original application link. A recently fetched listing is not a guarantee that the employer is still accepting applications.

Enter a role/skill query and optional filters, then select **Search jobs**. Filters are remembered by the search action; there is no separate Save preferences button. A query filters titles/recognised skills, and disabling remote excludes remote roles; location, work type, field and comparable salary preferences affect ranking. The model evaluates the top eight candidates and returns up to three known listing IDs plus a focus skill; displayed guidance is grounded in actual matched/missing requirements. It cannot invent numerical fit scores. Progress shows real request stages with a minimum 2.8-second readable transition, including when a response is fast. Cached results are identified as saved matches rather than a new AI run.

Scores recalculate against current evidence/preferences when matches load. A changed profile/preferences fingerprint hides an old AI shortlist until a new search. Users can mark results relevant/not relevant and choose a reason; feedback is stored for evaluation, not automatic model training. Cached provider skill tags were repaired to prevent Java/JavaScript, SQL/MySQL and Git/GitHub substring matches. Admin health shows provider/sync history, failures and a confirmed refresh action.

Search uses the collected catalogue; it does not scrape every source for each student request. Source filtering and ten results per page keep large searches manageable. At equal fit scores, Malaysian sources appear before the international remote feed. Ollama shortlists from the best eight candidates rather than processing every listing. Admin refresh or `jobs:sync` updates the catalogue; the scheduler uses the same broad collection daily. For a targeted refresh, run from `backend` in CMD:

```cmd
php artisan jobs:sync --source=jobstore --keywords=software,internship
```

Optional `PUBLIC_JOBS_PAGES` (1–8, default 3) and `PUBLIC_JOBS_DETAIL_LIMIT` (0–100, default 24 per source) control collection bounds. Higher values require more time and requests; public access can change. No schema migration, SQL import or new model is needed for these sources.

**Not assessed** means a listing has incomplete requirements or no skill terms recognised by the current extractor. Summary-only results remain discoverable, but receive no invented fit score. The extractor currently recognises a fixed vocabulary, so an unrecognised requirement does not necessarily mean the original advert lacks requirements. Open the full listing to review it. Missing verified skills on the student's side produce a low/zero score when requirements are available; that is a different state. Ollama generates the shortlist and advice, while the fit score is calculated from verified evidence and recognised listing requirements.

## Workspace revision — 15 November 2026

The workspace provides consistent confirmation dialogs, accessible selectors, section pagination, draft retention and responsive navigation. Run the backend tests and frontend build before sharing changes.

- White input surfaces and dark text in both themes; shared styled, keyboard-accessible dropdowns.
- Collapsible desktop sidebar, clickable breadcrumb parents, full-name greetings and coloured status/source badges.
- Pagination for student and admin lists, assessment history/questions, CV entries and long skills-profile sections.
- Shared confirmation dialogs for deletion, evidence submission, review decisions, publishing, assessment submission, discarding drafts and signing out. Routine saves, filters and navigation remain direct.
- Persistent drafts for profile, portfolio forms, career preferences, CV/source text, assessment answers and admin editing/feedback. These are scoped to the account and browser tab, cleared after successful submission or explicit discard/sign-out. Files remain in memory while navigating but must be selected again after a full reload. Drafts are not cross-device account saves.
- Assessments resume the existing active attempt and original server deadline. Leaving the page never pauses the timer.
- Visible buffering, elapsed time and genuine request stages for CV operations, job matching/tailoring and chat. No invented percentage or completion claim.

### Fit-score implementation (developer reference)

`score = (matched verified requirements / recognised listing requirements) × 100 × preference factor`

Current verified skill names, technologies in verified projects, current verified certificates and passed assessments with explicit skill mappings provide the evidence. Common aliases are normalised and duplicate evidence does not add points. Pending/rejected records, expired certificates, unverified CV claims and unrelated records do not increase the score. No requirements means **Not assessed**; no verified overlap means **0**.

When preferences can be compared, the factor is `0.8 + 0.2 × matched weight / compared weight`: location 8, work type 5, field/title 3, compatible MYR salary overlap 4. Unset preferences and missing/incompatible listing salary data are excluded. With no comparable preferences, the factor is 1. Preferences can reduce coverage fit; they cannot create points without verified overlap.

Display bands are red below 45, amber 45–74, green from 75. These are requirement-coverage bands, **not chances of getting hired**. Experience level, work authorisation and employer decisions are not assessed. The student interface shows the score, matched skills and skills to review; the formula/weight explanation is deliberately absent. Detailed metadata remains available to developers for testing and auditing.

The `2026_11_15_010000_add_job_score_breakdown` migration is applied locally. Both SQL snapshots include it. Keep the existing database and running services; reload the browser.

## Interface revision — 14 November 2026

The public pages and both workspaces share a dark ink, cobalt and aqua palette, with solid colours throughout. The workspace also supports a saved light-theme preference. Outfit headings and Work Sans body text are self-hosted with their font licenses.

The landing page uses an original illustrative student photograph, an interactive feature explorer and a **19-second recording of the actual product**. It follows a clearly labelled, isolated example student through evidence submission, admin verification, a real Ollama search and CV creation. Long waits are edited out. The video pauses off screen, exposes play/pause controls and uses its poster by default when reduced motion or data saving is enabled. Small-screen media is about 244–258 KB, the desktop recording about 558–563 KB. See the asset notes for provenance and capture scripts.

The requested [UI UX Pro Max skill](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) guided the revision. [Media provenance](frontend/public/media/ASSET_NOTES.md) is included with the assets; generated design and browser QA reports stay local.

## Technology

| Area | Technology |
| --- | --- |
| Backend | Laravel (PHP) REST API with Sanctum authentication |
| Database | MySQL |
| Frontend | Vue 3, Tailwind CSS, and shadcn-vue |
| AI service | Ollama with a Qwen model, with a verified profile fallback when the local model is unavailable |
| Job sources | Hiredly, Jobstore, Ricebowl and Maukerja public HTML; existing Remotive feed; optional Jooble API; JobStreet external search while server access is restricted |

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

**Requirements:** PHP 8.2+, Composer, Node.js 22.12+, npm, Python 3 for the setup helper, MySQL (via XAMPP or standalone), and Ollama for local AI.

### Already configured on this Windows machine

The local `backend/.env` points to **`digitalpassport_live`**. The original `digitalpassport` database remains untouched as a backup of the previous demo records. Migrations, two authored assessments with ten questions each, a local administrator account, `qwen2.5:3b`, and 16 live Remotive listings were initially set up on 14 November 2026. Current records and listing availability can change. Local administrator credentials are stored only in `backend/.env` as `ADMIN_EMAIL` and `ADMIN_PASSWORD`. Do not commit that file.

To start the app, keep XAMPP MySQL and Ollama running, then open two separate **cmd** windows:

```cmd
cd backend
php artisan serve --port=8000
```

```cmd
cd frontend
npm run dev
```

Open the URL Vite prints (usually `http://localhost:5173`). Log in as the local administrator using the credentials in `backend/.env`, or register a real student account. Add actual project/certificate evidence and link it to skills before asking the administrator to verify them. Generate job matches, select a listing, then select **Tailor CV**.

#### Login and session behaviour

Authentication now uses tab-scoped `sessionStorage`, not a persistent `localStorage` login. Old persistent login data is discarded once, so sign in again after this update. A reload within an active tab retains its session, but every protected navigation validates the token and current account with the backend before showing the workspace. Tokens expire two hours after issue, enforced by Sanctum. Unauthorized API responses, including downloads, clear the session and return to sign-in.

Opening **`/login` explicitly always shows the login form** and signs out/revokes the previous token when the server is reachable. Stopping development terminals is not a logout operation; a still-valid browser session is independent of process lifetime. Browser tab/session restoration can preserve tab storage, but it cannot bypass backend validation or token expiry. Explicit sign-out/sign-in clears local drafts. Expiry retains account-scoped local drafts for reauthentication in the same tab; saved database records are unaffected.

To refresh listings automatically while developing, run in a third cmd window:

```cmd
cd backend
php artisan schedule:work
```

The schedule syncs jobs daily at 08:00 and checks allowed provider links at 08:10. The scheduler must be running for these jobs to execute; the coding agent does not manage it. You can sync immediately with `php artisan jobs:sync` or the admin refresh action. Jooble requires your own API key in `backend/.env`; run `php artisan config:clear` after adding it.

### Changing the local administrator login

Login checks the `users` table. Editing `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `backend/.env` alone does not update an existing account. After editing them, run from the repository root in cmd:

```cmd
php scripts\sync_local_admin.php --apply
```

The helper updates the existing local administrator and hashes the password, preserving the account ID and records. It refuses ambiguous account selection or an email already owned by a student. Run without `--apply` to preview. A database import or server restart is not required. Credentials stay in the ignored `.env` file; the setup helper preserves custom administrator values.

For a quick local check from the repository root, run `python scripts\check_local_app.py`, `php scripts\check_ai.php`, or `php scripts\check_matching.php`. The matching check rolls back its temporary records. `C:\xampp\mysql\bin\mysql.exe -u root < scripts\verify_live.sql` shows the live database counts without changing data.

### Fresh machine setup

### Backend (Laravel API)

```cmd
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

Make sure MySQL is running and adjust the credentials in `backend/.env`. On XAMPP with a local root account, from the repository root:

```cmd
python scripts\setup_local.py
C:\xampp\mysql\bin\mysql.exe -u root < scripts\create_live_database.sql
cd backend
php artisan migrate
php artisan db:seed
php artisan jobs:sync
```

`setup_local.py` is an optional fresh-machine helper; it preserves an existing database/model/admin configuration. The current workspace uses `OLLAMA_MODEL=qwen3:4b` for structured document tasks and `OLLAMA_CHAT_MODEL=qwen3.5:4b` for Navi, selected after local comparisons. On a fresh machine install them with `ollama pull qwen3:4b` and `ollama pull qwen3.5:4b`, then set those variables. The models are already installed here. Requests disable thinking output and use 4096 tokens for smaller requests, increasing the window for longer input. First use/model switching can be slower on the laptop's 4 GB GPU. Configuration changes do not require a database import; if a manually managed server retains old environment settings, restart that terminal yourself. Reference: [Qwen3](https://ollama.com/library/qwen3:4b), [Qwen3.5](https://ollama.com/library/qwen3.5:4b), [Ollama chat roles and thinking](https://docs.ollama.com/api/chat).

### Frontend (Vue)

```cmd
cd frontend
npm ci
npm run dev
```

Open the URL printed by Vite (usually `http://localhost:5173`). The frontend proxies `/api` requests to the Laravel server at `http://127.0.0.1:8000`.

### SQL snapshots

SQL snapshots are versioned under `backend/database/sql/` so teammates receive them with the application:

- [database_clean.sql](backend/database/sql/database_clean.sql): schema plus migration metadata.
- [database_with_data.sql](backend/database/sql/database_with_data.sql): the same schema, two authored assessments (20 questions) and **one explicitly fictional demo student**, `demo@utem.edu.my` / `demo123`.

The demo includes a complete profile, 16 verified demo skills, three projects, two certificates, two completed assessments with answers, search preferences and a general CV. No job listings, matches, recommendation runs, tokens or real personal data are exported. Both snapshots cover 35 tables and 14 migrations. They contain `DROP TABLE` statements targeting `digitalpassport` and are for a fresh disposable database only. Import **one** snapshot, not both. Keep an existing database and run migrations instead. To add the demo without an import: `cd backend`, then `php artisan db:seed --class=DemoStudentSeeder`. Existing demo edits/passwords are preserved on rerun.

For a fresh XAMPP database, run from the project root in CMD:

```cmd
C:\xampp\mysql\bin\mysql.exe -u root < backend\database\sql\database_with_data.sql
```

Set `DB_DATABASE=digitalpassport` and your `ADMIN_EMAIL` / `ADMIN_PASSWORD` in `backend/.env`, then run `php artisan config:clear`, `php artisan migrate` and `php artisan db:seed` from `backend`. The seeder provisions the admin from those local credentials. SQL snapshots do not include uploaded files, installed dependencies or Ollama model files; follow the setup sections above for those.

`php scripts\export_schema_snapshot.php` regenerates both files in `backend/database/sql/` from the current schema, authored assessment bank and explicit demo fixture in one command, without exporting personal data. `php scripts\check_cv_schema.php` compares complete definitions, including types, defaults, indexes, constraints and migration coverage, with read-only database access. Adding `--import-check` also imports each file into its own newly created temporary database, validates the demo login, table counts and all seeded questions/answers/skill mappings, then removes only those temporary databases. Reports are generated locally under the ignored `design-system/` directory.

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

Local-only files include `AGENTS.md`, `plan_to_enhance.md`, `PROJECT_TRACKER.md`, the saved personal prompt and `design-system/` reports. They remain on the computer but are excluded from normal `git add` operations. Shared README files, asset provenance, application code and the two SQL snapshots remain eligible for version control. Avoid `git add -f` for ignored notes.

### CV import correction — 16 November 2026

The organiser normalises invisible Unicode characters and separates source sections before inference. AI identifies entry headers; descriptions are copied from the matching source block instead of asking the model to paraphrase them. Summary, skill category cleanup, activities, publications and languages use source boundaries. Grades/honours have an Education field and survive PDF/Word exports. If a section cannot be structured, its source is retained in Additional information for review.

For an already imported CV: open it → **Source text** → **Organise with AI** → review the sections. This replaces that draft's editable sections after confirmation; the original file remains available. Save/export any edits you want to keep first. No existing uploaded CV was silently overwritten by this update.

The demo's controlled fit test reaches 100 when all listed verified requirements and comparable preferences match; adding one missing requirement reduces it to 83.33. Read-only testing against the current catalogue reached 97 with the demo's stored preferences. A complete profile does not guarantee 100 on every job, or a job offer.
