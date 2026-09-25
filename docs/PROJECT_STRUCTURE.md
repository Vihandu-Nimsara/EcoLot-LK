# Project Structure

- `app/Core` — custom MVC core classes
- `app/Controllers` — request handling
- `app/Models` — database access and business rules
- `app/Views` — role-based UI files
- `config` — application and database settings
- `database` — schema and seed SQL
- `public` — browser entry point and assets
- `routes` — route definitions
- `storage` — logs and uploaded files
- `tests` — standalone PHP, JavaScript and browser regressions
- `app/Services` — registration, OTP, SMS workflows and pickup presentation
- `app/Views/components` — shared workspace body, assets and CSRF logout form
- `docs/ui-reference` — retained design reference, separate from runtime assets
- `tools` — ERD maintenance scripts (not browser endpoints)

Only `public/` should be served by the web server. `storage/` holds private
runtime state and uploads; generated state is excluded from Git. Historical
implementation notes live in `docs/authentication-plan.md` and may describe planned
work; they are not the source of truth for current security controls.

## Shared code conventions

`app/autoload.php` owns the class loader used by both the web entry point and
tests. Keep database operations and domain rules in models, request handling in
controllers, and display transformations in presenters such as
`PickupRequestPresenter`. The controller adapter remains available for existing
callers.

Role layouts select their assets and navigation options, then include
`workspace-body.php`. Keep role-specific dialogs and scripts in the role layout.
`Asset::version()` supplies cache versions for local assets.

Pickup mutations share transaction commit/rollback handling within
`EWasteRequest`; its `create()` method can still participate in an existing
transaction. Preserve isolation level and schedule lock ordering when editing
this code: they protect capacity during simultaneous requests.

Tests share application bootstrapping and schema/actor fixtures under
`tests/support`. Each database integration test retains explicit ownership of
its temporary database and cleanup.

## Regression checks

Run the PHP/database regressions and JavaScript escaping checks:

```sh
php tests/run-all.php
```

The local MySQL/MariaDB server must be running and configured test credentials
must be allowed to create and drop disposable databases. Include the browser
regression when Playwright and Chrome are available:

```sh
php tests/run-all.php --browser
```

If Playwright is installed outside this project, set `PLAYWRIGHT_MODULE` to its
module directory. Browser checks exercise the public pickup and officer schedule
flows against a temporary database. These checks cover implemented flows; they
do not establish that every prototype screen is a completed feature.
