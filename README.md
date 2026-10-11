# Sistem Akaun Caj Pemajuan

A PHP MVC application for projects, development charges, payment schedules, receipts, notifications, reports, and administrator/user accounts.

## Requirements

- PHP **7.4.x or 8.0.x**, with dependencies resolved for **7.4.33**. PHP 8.1 or newer is outside this project's supported range. Composer resolves dependencies for PHP 7.4.33, and the application checks the running PHP version before loading dependencies.
- Extensions: PDO, pdo_mysql, mbstring, GD (with JPEG/PNG/FreeType), OpenSSL, fileinfo, and sessions.
- MariaDB 10.4 (the supplied schema's original version), or a compatible MySQL database.
- Composer 2. Run Composer with this project's PHP 7.4 or 8.0 binary so its platform checks match the web server.
- Apache with mod_rewrite and AllowOverride All, or the PHP development server below.
- Internet access in the browser for Bootstrap, Bootstrap Icons, and SweetAlert assets loaded from CDNs.

## Install a fresh clone

1. Clone this repository and open a terminal in its directory.
2. Confirm the PHP binary you use: `php -v`. On XAMPP, use its bundled PHP binary if your shell points at another installation. Apache's PHP version must also match your intended runtime.
3. Install the locked dependencies:

   ```sh
   composer install --no-dev --prefer-dist
   composer check-platform-reqs --no-dev
   ```

   Do not use `--ignore-platform-reqs`. Composer's simulated platform controls dependency resolution; it does not change the PHP installed on your computer.

4. Copy `app/config/local.example.php` to `app/config/local.php` and set your database host, name, username, and password. The local file is ignored by Git. It can override any key from `app/config/config.php`, including SMTP settings.
5. Create an empty database and import **database/schema.sql** using phpMyAdmin, or:

   ```sh
   mysql -u root -p -e "CREATE DATABASE caj_pemajuan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
   mysql -u root -p caj_pemajuan < database/schema.sql
   ```

   The clean schema contains no accounts or business records. `database/caj_pemajuan.sql` is the existing sample snapshot; use it only when you specifically want the sample records. Do not import both files. `_installation/` belongs to the original framework demo and is not the schema for this application.

6. Create the first administrator:

   ```sh
   php scripts/create-admin.php admin@example.com "Administrator"
   ```

   Save the generated password printed by the command. Existing email addresses are rejected without changing their accounts. Administrators can manage additional users in the dashboard.
7. Give the web server write access to `app/logs`, `app/uploads`, `app/backups`, and `public/uploads/receipts`. Keep uploads enabled in php.ini; set `upload_max_filesize` and `post_max_size` sufficiently high for receipt uploads (the application accepts receipts up to 5 MB).
8. Verify configuration:

   ```sh
   php scripts/check-setup.php
   ```

9. Run the system with one of these options:

   - **XAMPP:** place the clone under `htdocs`, enable mod_rewrite and AllowOverride All, start Apache and MySQL, and visit `http://localhost/<folder-name>/`. The folder name can differ from `cajpemajuan`.
   - **Apache virtual host:** point DocumentRoot at the repository's `public` directory and enable its .htaccess rules.
   - **Local development:** run `php -S 127.0.0.1:8080 -t public scripts/router.php`, then visit `http://127.0.0.1:8080/`.

## Verify the main features

Log in using the new administrator, add a user, project, and development charge, generate payment schedules, record and cancel payments, upload/view a receipt, check notifications, and open the project/charge reports. Repeat with a normal user to confirm role restrictions. Log out and confirm the session ends. These browser checks require a configured database and writable upload directories.

`php scripts/check-setup.php` checks required extensions, writable directories, database tables, and the first administrator. It exits with status 1 when a required check fails. `php tests/compatibility-smoke.php` checks URL portability, PDO parameter types, dependency loading, and error pages without changing business records. `php tests/startup-smoke.php` checks missing-file diagnostics and fallback error handling.

## Blank page or missing login

The home route loads `AuthController`; `ErrorsController` renders error pages. Both files are required. In the original code, the home route referenced a deleted `LoginController`, while error rendering relied on a missing `ErrorsController`. Hidden errors could therefore leave the browser blank. The updated startup checks display an explanation when required controllers or Composer dependencies are missing, or PHP is outside 7.4.x–8.0.x. Error logging failures also no longer prevent an error page from appearing.

Make sure the updated files have been committed and pushed before your friend clones or pulls the repository. Include new files such as `app/core/Startup.php`, `app/controllers/ErrorsController.php`, `database/schema.sql`, and `scripts/`; copying only modified files is insufficient. Never copy `app/config/local.php` between machines unless its database settings are appropriate for the recipient.

For a failing clone:

1. Confirm Apache/the web server uses PHP 7.4.x or 8.0.x, and check `php -v` for the terminal too.
2. Run `composer install` in the repository directory.
3. Run `php scripts/check-setup.php` and fix the reported setup problems.
4. Open the application root, such as `http://localhost/<folder-name>/`, or use the documented development server.
5. Check `app/logs/log.txt` and the Apache/PHP error log if an application error page appears. If even the standalone setup page is absent, check the server's document root, permissions, PHP execution, and .htaccess errors in the Apache log.

A database is required to log in, but the login page itself should render before connecting to the database.

Use a PHP 8.0-compatible php.ini. An old PHP 7 configuration containing `track_errors=On` causes PHP 8 to fail before any project code can run, so the application cannot display its setup page. Remove that retired setting from the PHP 8.0 configuration. `php --ini` shows the CLI configuration path; Apache may use a different php.ini. This setting was found in the local XAMPP configuration during verification; it was not changed. See the [PHP 8 migration notes](https://www.php.net/manual/en/migration80.incompatible.php).

## Troubleshooting

- **Login page works but login fails:** check local database settings and create an administrator after importing the clean schema.
- **404 on every route:** enable Apache mod_rewrite and AllowOverride All, or use the supplied development router.
- **Composer rejects PHP:** check the PHP executable used by your terminal. This project accepts PHP 7.4.x and 8.0.x. A newer system PHP does not mean Apache uses that same version.
- **Receipts fail:** check directory ownership, upload limits, and PHP upload error messages.
- **Missing styling or dialogs:** check access to the CDN assets.
- **Email:** SMTP credentials must be configured before sending email. SMTP delivery requires a real server and is not validated by the setup checker.

The original miniPHP framework documentation is preserved in [docs/framework.md](docs/framework.md). Some demo controllers and tables described there are not part of this application.

## Compatibility verification

The GitHub Actions workflow in `.github/workflows/php80.yml` installs dependencies from scratch and runs syntax, setup, compatibility, and database checks on PHP 8.0 with MariaDB 10.4. It runs after push or pull request, or manually through GitHub Actions; adding the workflow locally does not mean a CI run has passed.

For database smoke checks, configure a separate database whose name ends in `_test`, import `database/schema.sql`, and run `php tests/database-smoke.php`. Test records are rolled back, but auto-increment counters can advance, so use only a disposable test database.

The repository targets PHP 7.4.33 and also supports PHP 8.0 and retains the existing application features. The `.php-version` file records the project version for compatible version managers. If you have several PHP versions installed, use the absolute path to the PHP 8.0 binary for the setup commands and development server, for example `/path/to/php80 -S 127.0.0.1:8080 -t public scripts/router.php`. It does not install PHP or change global XAMPP/Apache settings. Use a PHP 7.4 or 8.0 binary or configure a matching runtime for this project. See [verification results](docs/verification.md).

Built on miniPHP; see [LICENSE](LICENSE) for the original license.
