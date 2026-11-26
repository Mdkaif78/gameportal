# Game Portal

A PHP 8.1+, MySQL/MariaDB and Twig game catalogue with read, create, edit, delete, multi-criteria search, and Ajax filtering.

## Run locally

1. On Windows, the simplest route is XAMPP with PHP 8.1+, Apache, and MySQL. Ensure the PHP extensions `mysqli` and `mbstring` are enabled. Install Composer separately if it is not bundled.
2. Create a database and user, for example:

   ```sql
   CREATE DATABASE game_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'game_user'@'localhost' IDENTIFIED BY 'choose-a-local-password';
   GRANT ALL PRIVILEGES ON game_portal.* TO 'game_user'@'localhost';
   ```

3. Import `schema.sql` into that database:

   ```sh
   mysql -u game_user -p game_portal < schema.sql
   ```

4. Copy `.env.example` to `.env`, then set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` for the database you created. Keep `.env` private; it is excluded by `.gitignore`.
5. Install Twig and start PHP's development server:

   ```sh
   composer install
   php -S 127.0.0.1:8000
   ```

6. Open `http://localhost/Assignment/` if you placed this folder under XAMPP's `htdocs`, or open <http://127.0.0.1:8000> if you used PHP's development server. Create an account, log in, and manage records. Registration is enabled in `.env.example` for initial local setup; set `ALLOW_REGISTRATION=0` after creating accounts. The arithmetic CAPTCHA is enabled by default.

## Deploy to the University of Wolverhampton mi-linux server

The project brief gives the student site pattern `https://mi-linux.wlv.ac.uk/~YOUR_STUDENT_ID/`. Confirm the current hostname, database name, PHP version, and transfer method with your module instructions or IT if any of these checks differ.

1. Connect to the student server using your university account. In Windows Terminal/PowerShell (or a terminal on macOS/Linux):

   ```sh
   ssh YOUR_STUDENT_ID@mi-linux.wlv.ac.uk
   php -v
   composer --version
   ```

   The app requires PHP 8.1+, `mysqli`, `mbstring`, and `mysqlnd`. If the server's PHP is older or lacks an extension, ask your tutor/IT which supported PHP handler to use; do not upload until compatible.

2. On the server, check your home directory and make a private config file outside the web directory. The supplied archive used a `public_html` web directory; verify it exists for your account:

   ```sh
   mkdir -p ~/public_html
   chmod 711 ~
   chmod 755 ~/public_html
   nano ~/.game-portal.env
   ```

   Add your own assigned database values (never copy credentials into PHP):

   ```text
   DB_HOST=localhost
   DB_NAME=YOUR_ASSIGNED_DATABASE
   DB_USER=YOUR_STUDENT_ID
   DB_PASS=YOUR_ASSIGNED_DATABASE_PASSWORD
   CAPTCHA_ENABLED=1
   ALLOW_REGISTRATION=1
   ```

   Save the file and run `chmod 640 ~/.game-portal.env`. The app checks the home folder for this file when installed in `~/public_html/game-portal` (and also supports a direct `public_html` install). PHP must be able to read it. If the web PHP account is not in the file's group, use a supported ACL or ask IT for a private per-user configuration method; avoid making database passwords world-readable. Verify the host/name/user against the module's MySQL setup instructions rather than assuming every account has the same database name.

3. Install under `~/public_html/game-portal/` so existing coursework in your web directory remains untouched. For example, copy the supplied ZIP to your home directory with SFTP/SCP, then extract it into that new folder. Include `.htaccess`, `.env.example`, PHP files, `templates/`, `assets/`, and `composer.json`. Do not upload your real `.env` file.

4. Install Twig in the project directory:

   ```sh
   cd ~/public_html/game-portal
   composer install --no-dev --optimize-autoloader
   ```

   If Composer is unavailable on mi-linux, run Composer on your computer using a PHP version compatible with the server, then upload the generated `vendor/` folder too. Do not continue until `vendor/autoload.php` exists.

5. Import `schema.sql` into the database assigned by the university. If shell access to MySQL is enabled, upload `schema.sql` and run `mysql -u YOUR_STUDENT_ID -p YOUR_ASSIGNED_DATABASE < schema.sql`; otherwise use the university-supported database tool or follow the SQL import instructions from your module. Enter the password at the prompt; do not put it in the command itself.

6. Open `https://mi-linux.wlv.ac.uk/~YOUR_STUDENT_ID/game-portal/`. Register the account you will use, then edit `~/.game-portal.env` and set `ALLOW_REGISTRATION=0`. Check the homepage, login, adding, editing, deleting, and combined name/year/rating filters. HTTPS is needed for Secure session cookies.

The `.htaccess` blocks requests for configuration and setup files and disables directory listings. Keep the `.game-portal.env` file outside `public_html` as described above. The project brief's sample URL uses `mi-linux.wlv.ac.uk`; a search of public University pages did not locate a current official student deployment guide, so follow your module's own server instructions if they specify different SSH/SFTP or database details.

The supplied archive contained a hardcoded database username/password in `db.php`. Those credentials have been removed from the project. If they were real and the archive or files were shared, rotate that password with the university.

## Security and verification notes

- SQL statements use MySQLi prepared statements; validation is performed on IDs, dates, ratings, usernames, and search filters.
- Twig auto-escapes HTML output, and Ajax results are inserted with explicit client-side escaping.
- `config.php` sends a restrictive Content Security Policy, anti-framing, MIME-sniffing, referrer, and HTTPS Strict Transport Security headers on PHP-generated responses; Bootstrap CDN assets use version-pinned Subresource Integrity hashes. Static assets and Apache's Basic authentication challenge are served outside PHP and may need University Apache configuration for the same headers.
- Catalogue changes require a logged-in session and a CSRF token; deletes are POST requests.
- Passwords are hashed with `password_hash()` and verified with `password_verify()`. Account registration requires a username, email, and password.
- Login uses a simple arithmetic CAPTCHA. It is a coursework demonstration control and is not a substitute for a robust third-party CAPTCHA service.
- A ZAP 2.17.0 passive scan was run on the deployed catalogue on 26 November 2026. Submit `game-portal-zap-passive-final-2026-11-26.pdf` separately with this source archive. It covers the catalogue response, the initial Basic authentication challenge, and associated requests/assets; authenticated CRUD routes were not checked, and no active scan or data-changing form submissions were run. The successful PHP catalogue response included the configured security headers, while ZAP still reported missing headers on server-level/static responses. The report counts 1 High, 1 Medium, 4 Low, and 1 Informational finding. Ask University IT to verify Apache patch status because Ubuntu may backport fixes without changing the version string.

## Files

- `index.php`: catalogue and Twig-rendered homepage.
- `search.php` and `assets/search.js`: Ajax search by game name, release year, and minimum rating simultaneously.
- `game-form.php`, `save-game.php`, `game.php`, `delete-game.php`: authenticated CRUD flows.
- `login.php`, `register.php`, `logout.php`: session authentication.
- `schema.sql`: database schema.
