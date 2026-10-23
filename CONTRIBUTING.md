# Contributing

Thanks for your interest in improving this POS system. This guide covers how to get a working development copy and how to submit changes.

## Development setup

Requirements (see [INSTALL.md](INSTALL.md) for the full list):

- PHP 8.1 to 8.4 with the `json`, `gd`, `bcmath`, `intl`, `openssl`, `mbstring`, `curl` and `xml` extensions enabled
- MySQL 5.7 or MariaDB 10.x
- Composer and npm (for building from source)

Build the project from source (see [BUILD.md](BUILD.md)):

```bash
composer install
npm install
npm run build
```

Then follow [INSTALL.md](INSTALL.md) to create the database, import `app/Database/database.sql`, and configure your `.env`.

### Docker alternative

For a containerized development setup, mount the application folder into the dev container as described in [BUILD.md](BUILD.md):

```bash
export USERID=$(id -u)
export GROUPID=$(id -g)
docker-compose -f docker-compose.dev.yml up
```

A plain local Docker install is also covered in [INSTALL.md](INSTALL.md) (`docker-compose up`). The Docker setup is for development, not production.

## Submitting changes

1. Fork the repository and create a branch for your change.
2. Keep each pull request focused on one change (one feature, one fix).
3. Describe what you changed and why in the pull request body.
4. If your change affects installation or setup, update [INSTALL.md](INSTALL.md) or [BUILD.md](BUILD.md) in the same pull request.
5. Make sure the application still builds (`npm run build`) and a fresh install still completes before submitting.

## Reporting issues

Open an issue with your PHP version, database version, the steps to reproduce the problem, and any error output. Check the existing open and closed issues first; many setup problems are already answered there.
