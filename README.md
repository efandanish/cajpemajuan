# Sistem Akaun Caj Pemajuan

Web application for Projek Pembangunan, Caj Pemajuan, installment payments and receipts, department notifications, the Audit Log and yearly reports.

## Requirements

- XAMPP with **PHP 8.2** (verified 8.2.4; PHP 7.4–8.2 supported, 8.3+ refused at startup) and **MariaDB 10.4** (verified 10.4.27).
- PHP extensions: pdo_mysql, mbstring, gd, openssl, fileinfo (all enabled in XAMPP).
- Apache with `mod_rewrite` and `AllowOverride All`.
- Browser access to the Bootstrap, Bootstrap Icons and SweetAlert CDNs.

PHP libraries are already included in `vendor/` (installed from `composer.lock`). Composer is only needed to reinstall them: `composer install --no-dev`.

### PHP 8.2 in XAMPP (required environment fix)

Some XAMPP builds ship a `php.ini` containing `track_errors=On`. PHP 8 removed this directive, so PHP 8.2 refuses to start (*"Directive 'track_errors' is no longer available in PHP"*), both on the command line and in Apache. Before using PHP 8.2:

1. Stop Apache and back up `php.ini` (`xamppfiles/etc/php.ini` on macOS, `xampp\php\php.ini` on Windows).
2. Comment out the line: `track_errors=On` → `; track_errors=On`. Nothing else changes; PHP 7.4 also starts with this edit.
3. Make sure Apache loads the PHP 8.2 module. On XAMPP for macOS builds that include both versions, `etc/extra/httpd-xampp.conf` must load `LoadModule php_module modules/libphp.so` instead of `php7_module modules/libphp7.so`; Windows XAMPP 8.2 installers load PHP 8.2 already.
4. Start Apache and confirm with `php -v` and `php scripts/check-setup.php` (the first line reports the running version).

## Installation (new server)

Run the commands from the application folder, using the same PHP that Apache uses (XAMPP: `xampp/php/php` on Windows, `/Applications/XAMPP/xamppfiles/bin/php` on macOS).

1. **Copy** this folder into XAMPP `htdocs`, e.g. `htdocs/cajpemajuan`.

2. **Configuration.** Generate the private configuration (security keys are created automatically and never shown):

   ```sh
   php scripts/init-config.php
   ```

   Then edit `app/config/local.php` and set `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS`. Keep this file private. On an existing installation, do not run this step: keep the existing keys.

3. **Database.** Create a new, empty database (utf8mb4) and import `database/caj_pemajuan_clean.sql` once, using phpMyAdmin (Import tab) or:

   ```sh
   mysql -u DB_USER -p -e "CREATE DATABASE caj_pemajuan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
   mysql --default-character-set=utf8mb4 -u DB_USER -p caj_pemajuan < database/caj_pemajuan_clean.sql
   ```

   The file contains the complete structure (10 tables) and no data. It already includes every migration in `database/migrations/`: **do not run the migrations on a new installation.** It stops with an error if the tables already exist, so it cannot overwrite an existing database.

4. **First administrator.** There are no default accounts. Create the first (global) administrator:

   ```sh
   php scripts/create-admin.php admin@your-domain.gov.my "Nama Pentadbir"
   ```

   A random password is generated and shown once. Store it securely, log in and change it under Pengguna. A global administrator has no Jabatan and sees every department, all Caj Pemajuan activity and the full Audit Log. Create other users and assign their Jabatan in the application.

5. **Writable folders.** The web server must be able to write `app/logs`, `app/uploads` and `public/uploads/receipts`. Set PHP `upload_max_filesize` ≥ 5M and `post_max_size` ≥ 8M.

6. **Check** the installation, then open `http://localhost/cajpemajuan/`:

   ```sh
   php scripts/check-setup.php
   ```

## Upgrading an existing database

Never import the clean SQL over a client database. Back up the database and `public/uploads/receipts`, then follow `database/migrations/README.md` to apply only the missing migrations.

## Troubleshooting

- *"Directive 'track_errors' is no longer available"*: see "PHP 8.2 in XAMPP" above.
- Login fails: check the database settings in `app/config/local.php` and that an administrator exists.
- Page not found on every link: enable `mod_rewrite` and `AllowOverride All`; both `.htaccess` files (root and `public/`) are required.
- Blank or error page: check `app/logs/log.txt` and the Apache/PHP error log.
- Receipt upload fails: check upload limits and that `public/uploads/receipts` is writable.

Built on miniPHP (see `LICENSE`). Third-party libraries in `vendor/` keep their own licenses.
