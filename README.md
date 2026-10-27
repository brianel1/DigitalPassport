# DigitalPassport

DigitalPassport is a planned web application for university students to record and present their digital skills. Students will be able to maintain a profile, link skills to project and certificate evidence, complete assessments, and view a Digital Skills Passport. Administrators will review submitted evidence and manage assessment content.

This repository currently contains the project setup only. Application code and local run instructions will be added as development begins.

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

## Git workflow

`master` is the shared integration branch. Each developer works on their own branch and pushes commits to that branch. The team does not use separate `dev` or `staging` branches.

1. Update your local `master` from the remote repository.
2. Switch to your branch and merge the updated `master` into it.
3. Make changes, commit them, and push your branch.
4. Open a pull request into `master` for review when practical. The team may merge directly when it agrees that review is not needed.
5. After a merge, all developers update from `master` before continuing work.

To update an existing developer branch after the remote repository is connected:

```bash
git fetch origin
git switch master
git pull origin master
git switch YOUR_BRANCH
git merge master
```

Resolve any merge conflicts on your branch before pushing. Keep secrets, installed dependencies, and generated files out of commits; see `.gitignore`.
