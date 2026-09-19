# Cleanup and security review

Implemented:

- Logout requires POST and a valid CSRF token; all role sidebars use a shared form.
  Logout destroys pending verification and other session state as well as identity.
- Session IDs use strict mode and cookie-only transport; cookies use HttpOnly,
  SameSite=Lax, and Secure on HTTPS (or with SESSION_COOKIE_SECURE).
- Login attempts are throttled across sessions using locked, hashed per-IP and
  per-mobile counters. Unknown users also run password verification against the
  dummy hash rather than skipping the expensive check.
- OTP issue and verification lock the user row inside the transaction before
  checking limits or the active challenge, serializing competing sessions.
- Registration shares password policy checks, rejects null bytes and passwords
  beyond bcrypt's byte limit; scalar validation rejects arrays before casting.
- Pickup form/history HTML helpers escape attribute quotes as well as markup.
- Private source, SQL, docs and uploaded licenses are denied by the project-root
  Apache rules. Directory listings and public dotfile access are disabled.
- Debug output defaults off; uncaught exceptions return a generic response.
  Database and deployment settings can be supplied through environment variables.

Verification: PHP syntax checks; database-free session/CSRF/routing/dashboard,
password and rate-limit tests; JavaScript escaping regression; PDO-double OTP
lock-order and rollback checks. Local PHP HTTP checks verified GET logout=405,
invalid POST logout=403 and response security headers. Apache was unavailable,
so its access rules were not verified against a running Apache instance.

Deployment limitations:

- This is a targeted source review, not a penetration test or proof that every
  vulnerability is eliminated. Database/SMS integration and concurrent MySQL
  requests still need testing. The development PHP server ignores .htaccess.
- Dashboard demos/localStorage are not a security or persistence boundary. Any
  future write APIs must enforce ownership, roles, validation and CSRF server-side.
- Authentication currently caches role/account state in the session; immediate
  revocation after administrative changes needs a database-backed session check.
- Login limits are local to one host and fixed-window. Distributed attacks and
  registration/SMS abuse across many newly created accounts need deployment-level
  controls. A shared proxy IP also shares the IP allowance.
- Set production secrets, HTTPS and filesystem permissions on deployment. Never
  enable APP_DEBUG publicly. Uploaded PDF validation does not replace malware
  scanning when document download/review is implemented.

References: [PHP session security](https://www.php.net/manual/en/session.security.ini.php),
[Apache access control](https://httpd.apache.org/docs/2.4/howto/access.html).
