# Troubleshooting

Common installation problems and their fixes, collected from [INSTALL.md](../INSTALL.md) and [BUILD.md](../BUILD.md).

## "System folder missing" after launching the browser

You are most likely running a raw repository clone that has not been built yet. A clone will not run on its own; build the project first as described in [BUILD.md](../BUILD.md):

```bash
composer install
npm install
npm run build
```

The same applies if `database.sql` cannot be found: the tables live at `app/Database/database.sql` in a built copy.

## Build fails with a missing PHP extension (for example `ext-intl`)

Messages such as "codeigniter4/framework requires ext-intl" mean a required PHP extension is not enabled in your `php.ini`. The installation requires the `json`, `gd`, `bcmath`, `intl`, `openssl`, `mbstring`, `curl`, and `xml` extensions; enable the missing one and run the build again.

## Blank pages or hidden errors while developing

PHP `display_errors` is disabled by default. For a development environment, create an `app/Config/.env` file from the `.env.example` to enable it. For a working installation, set the `CI_ENVIRONMENT` variable to `production` in the `.env` file.

## First login does not work

After a fresh install, log in with the default credentials from [INSTALL.md](../INSTALL.md) (username `admin`, password `pointofsale`), then change the password. If login still fails, confirm the database was created from `app/Database/database.sql` and that the database credentials in `.env` are correct.

## Docker notes

- The Docker setup is for development and local use, not production; change the default passwords in the compose file before exposing the containers publicly.
- The Nginx variant manages Let's Encrypt certificates automatically; keep the `STAGING` variable at `0` only when your domain and email configuration in `docker/.env` are final.
- Do not run `docker/uninstall.sh` on a live deployment unless you intend to wipe all disk content.

## Still stuck

Check the existing open and closed GitHub issues first; most setup topics are already covered there.
