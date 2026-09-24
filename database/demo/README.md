# Recycler bid development fixture

Use only a separate local database named `ecolot_demo_<name>`. Never import this into production. No existing seed or schema is changed by the fixture.

1. Create the development database. Import a **copy** of `database/schema.sql` with its `CREATE DATABASE` and `USE` database names changed to that development name; retain every table, constraint and trigger.
2. Set `DB_DATABASE` to that database, your normal `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD`, `ECOLOT_DEMO_FIXTURE=1`, and `ECOLOT_DEMO_PASSWORD` to a local demo password.
3. Run `php database/demo/recycler-bids-fixture.php`. The output gives the new recycler login mobile number and numeric E-Lot ID. Set the same DB configuration for the web application, then log in with that mobile and password.

The fixture creates isolated, uniquely named accounts, verification audit records, licences, capabilities, a matching HIGH-risk lot and a complete request → assigned collection → officer-verified collected item provenance chain. A second recycler submits Rs. 1,000. The primary recycler has no bid, allowing create → revise below Rs. 1,000 → withdraw demonstrations. Dates are relative to the database clock. Each invocation creates new records in one transaction; it never removes or overwrites existing bids. Demo scheduling is synthetic and intended only to satisfy the current schema and triggers.

`php tests/recycler-bids.php` runs security/validation checks plus the existing regression runner, whose Recycler dashboard now requires a database with the current schema. Use the isolated test database for this runner and `tests/otp-locking.php` too. Use a PHP runtime with `mbstring` and `pdo_mysql` (the local XAMPP runtime is `/opt/lampp/bin/php`). The integration suite uses an explicitly supplied isolated database named `ecolot_test_*` and the same schema import procedure:

```
ECOLOT_BID_TEST=1 DB_DATABASE=ecolot_test_bids php tests/recycler-bids-integration.php
```

Integration tests commit fixture rows because the service owns transactions. Use a disposable database; the script does not delete it or run against the application database. Tests freeze the database session clock for exact deadline boundaries.
