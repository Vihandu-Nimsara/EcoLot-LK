# EcoLot LK — Plain PHP MVC Initial Structure

This starter follows the university requirement of **no frameworks and no external libraries**.

## Allowed stack
- Plain PHP
- MySQL
- HTML
- CSS
- Vanilla JavaScript
- Custom MVC structure
- PDO

## Not included
- Laravel
- CodeIgniter
- React
- Vue
- Angular
- Bootstrap
- Tailwind
- jQuery
- Composer packages

## Notify.lk OTP configuration

Public-user registration sends its six-digit mobile verification code through
Notify.lk. Configure these environment variables in the PHP/Apache runtime:

```text
NOTIFY_LK_USER_ID=your_notify_user_id
NOTIFY_LK_API_KEY=your_notify_api_key
NOTIFY_LK_SENDER_ID=your_approved_sender_id
```

For XAMPP Apache, these can be added as `SetEnv` entries in the virtual-host or
Apache configuration, followed by an Apache restart. Do not commit live values.
Notify.lk's `NotifyDEMO` sender must not be used for OTP messages; use an
approved Sender ID.

The application stores only the OTP hash, expires codes after five minutes,
limits verification attempts and resends, and activates a public account only
after successful mobile verification.

## Suggested local location
`xampp/htdocs/ecolot-lk/`

## Entry point
`public/index.php`

## Running and configuration

Requires PHP 8.1+ with PDO MySQL, mbstring, fileinfo and cURL, plus MySQL.
Use `database/schema.sql` and `database/seed.sql` only when initializing a local
database; do not import them over existing data.

XAMPP subdirectory URL: `http://localhost/EcoLot-LK/public/`.
Prefer a virtual host with its document root set to `public/`; set
`APP_BASE_PATH` to an empty string for that setup. Apache must allow the supplied
`.htaccess` directives (`mod_rewrite`, `AllowOverride All` in the development
virtual host). The project-root rules deny browser access outside `public/`.
Do not serve the repository root with PHP's development server.

Environment variables (set in your PHP/Apache process; `.env` is not auto-loaded):

- `APP_ENV`: defaults to `local`; `APP_DEBUG`: defaults to `false`.
- `APP_BASE_PATH`: defaults to `/EcoLot-LK/public`.
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`:
  defaults match local XAMPP. Use a dedicated database user in deployment.
- `SESSION_COOKIE_SECURE=true`: enforce secure cookies when HTTPS terminates
  at a trusted reverse proxy. Direct HTTPS requests enable this automatically.

PHP must be able to write to `storage/`. Login throttling uses a locked local file
shared by workers: up to 10 attempts per mobile and 20 per IP per 15-minute window,
including successful attempts. It works on one application host; multiple hosts
need a shared rate-limit backend. Configure a trusted proxy's real client address
at the web server; the app deliberately does not trust arbitrary forwarded headers.

## Checks

```sh
php tests/run.php
php tests/otp-locking.php
node tests/escaping.js
```

Tests avoid modifying the application database or sending SMS. OTP lock-order
checks use a PDO double; real MySQL concurrency and SMS delivery need integration
testing with a disposable database and configured gateway.

Role dashboards contain frontend demos and localStorage workflows. These are not
authoritative server-side records. Authentication and registration are backed by
the database; converting demo actions into production workflows remains separate
feature work. See [structure](docs/PROJECT_STRUCTURE.md) and
[security review](docs/SECURITY_REVIEW.md).

### XAMPP on macOS: login runtime-file permissions

Apache runs as `daemon`, while a checkout may belong to your macOS user. Before
using login on a fresh checkout, create the limiter file without overwriting any
existing counters and grant Apache access to that file only:

```sh
touch storage/login-attempts.json
chmod +a 'daemon allow read,write' storage/login-attempts.json
```

Run these commands from the project root. The file is Git-ignored, so this setup
must be repeated for a new checkout or if the file is deleted. Do not make the
whole project world-writable. Other server environments should provision this
file for their actual PHP worker user. A `Permission denied` warning for this
file in XAMPP's `logs/php_error_log` causes all login submissions to fail before
password verification.
