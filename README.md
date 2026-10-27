# DigitalPassport

Student Digital Skills Passport for Workshop 2.

## Stack

- Backend: PHP with REST API
- Database: MySQL
- Frontend: Vue, Tailwind CSS, and shadcn-vue

## Git workflow

The shared branch is `master`. Each developer works on their own branch. There are no `dev` or `staging` branches.

1. Get the latest `master` before starting work.
2. Switch to your own branch and bring the latest `master` into it.
3. Code, commit, and push your branch.
4. Open a pull request into `master` for review when possible.
5. Merge approved work into `master`. Everyone then updates from `master` before continuing.

For an existing developer branch, the update commands are:

```bash
git fetch origin
git switch master
git pull origin master
git switch YOUR_BRANCH
git merge master
```

Review is recommended, but the team may merge directly into `master` when agreed. Keep `master` working so other developers can pull it safely.
