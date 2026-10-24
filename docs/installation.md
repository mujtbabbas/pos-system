# Installation

This guide pulls together the install paths documented in [INSTALL.md](../INSTALL.md) and
[BUILD.md](../BUILD.md): a local install from a release, building from source, and the
Docker options.

## Server requirements

- **PHP 8.1 to 8.4** (PHP 7.4 and below are NOT supported), with these extensions installed
  and enabled: `php-json`, `php-gd`, `php-bcmath`, `php-intl`, `php-openssl`, `php-mbstring`,
  `php-curl`, `php-xml`
- **MySQL 5.7**, or **MariaDB 10.x** as a drop-in replacement
- **Apache 2.4** (Nginx also works; see the LEMP deployment notes linked from INSTALL.md)

## Option 1: Local install from a release (recommended)

A bare clone of this repository will not run — it has to be built first (see Option 2).
For a normal install, use an official release instead:

1. Download the latest stable release (or a pre-release for a specific branch) from GitHub.
2. Create a new MySQL database for the POS.
3. Execute `app/Database/database.sql` against that database to create the tables.
4. Upload the release files to your web server.
5. Open the `.env` file and set your database credentials if needed.
6. Point your browser at the install's `public` directory.
7. Log in with the default credentials:
   - Username: `admin`
   - Password: `pointofsale`
8. Once everything works, set `CI_ENVIRONMENT` to `production` in the `.env` file.
9. Change the default admin password.

If you see a `system folder missing` message, or cannot find `database.sql`, you are
looking at an unbuilt clone rather than a release — build it (Option 2) or download a release.

## Option 2: Build from source

For developers who want to modify the code. Prerequisites are NPM (tested with 9.4.2) and
Composer (tested with 2.5.1). From the repository root, run in sequence:

```bash
composer install
npm install
npm run build
```

The build uses npm and gulp to assemble a runnable instance. If PHP complains that a
package "requires ext-intl", the intl extension is not enabled in your `php.ini`.

With the build done, drop in a configured `.env` file and an existing (upgraded) database,
or continue with the database steps from Option 1. Note the build output is a developer
version — it should not be deployed to production; use official releases for that.

## Option 3: Docker

The repository ships several compose files (`docker-compose.yml`, plus `dev`, `nginx`,
`phpmyadmin`, and `test` variants). For a development setup with your working folder
mounted into the container:

```bash
export USERID=$(id -u)
export GROUPID=$(id -g)
docker-compose -f docker-compose.dev.yml up
```

The basic compose setup also starts with `docker-compose up`. Docker runs natively on Mac
and Linux; Windows requires WSL2. This setup is **not suited for production** — change the
default passwords in the compose file before exposing the containers publicly.

### Nginx with Let's Encrypt (Docker)

Since version 3.3.0 there is a Docker install with an Nginx reverse proxy and automatic
Let's Encrypt TLS (renewals are handled automatically):

1. Edit `docker/.env`: set proper MySQL/MariaDB and phpMyAdmin passwords (not the defaults),
   your host domain name, and your Let's Encrypt email address. Keep `STAGING` at its test
   value until the configuration is right, then set `STAGING=0` so Let's Encrypt issues a
   final certificate.
2. Follow the local install steps, but run the installer script instead:

   ```bash
   docker/install-nginx.sh
   ```

To tear the Nginx setup down completely, `docker/uninstall.sh` removes it — do **not** run
it on a live deployment unless you mean it: all disk content is wiped.

## After installing

- Set `CI_ENVIRONMENT=production` in `.env` for a live site.
- Change the default `admin` password (`pointofsale`).
- For day-to-day operation, see [features.md](features.md); for problems, see
  [troubleshooting.md](troubleshooting.md).
