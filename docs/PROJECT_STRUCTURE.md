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
- `tests` — manual/plain PHP test files
- `app/Services` — registration, OTP and SMS workflows
- `app/Views/components` — shared workspace assets and CSRF logout form
- `docs/ui-reference` — retained design reference, separate from runtime assets
- `tools` — ERD maintenance scripts (not browser endpoints)

Only `public/` should be served by the web server. `storage/` holds private
runtime state and uploads; generated state is excluded from Git. Historical
implementation notes live in `docs/authentication-plan.md` and may describe planned
work; they are not the source of truth for current security controls.
