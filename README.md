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
