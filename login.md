# EcoLot-LK Authentication and Mobile OTP Verification Work Plan

## 1. Objective

Implement secure authentication for EcoLot-LK using:

- Native PHP sessions
- Mobile number and password login
- Notify.lk SMS API
- OTP-based mobile number verification
- Role-based access control
- No external frameworks or authentication libraries

The Notify.lk API will only deliver SMS messages. OTP generation, storage, expiry, verification, retry limits, account activation, and session management will be handled by the EcoLot-LK application.

---

## 2. User Types

| Role | Account creation | Mobile OTP | Account activation |
|---|---|---:|---|
| Public User | Self-registration | Required | Automatically activated after OTP verification |
| Recycler | Self-registration | Required | Mobile verified first; Admin approval required afterward |
| Admin | Created through database/system administration | Optional | Activated manually |
| Municipal Officer | Created by Admin | Optional | Activated by Admin |
| Collector | Created by Admin | Optional | Activated by Admin |

---

## 3. Main Authentication Decisions

### 3.1 Login method

Users will log in using:

- Mobile number
- Password

### 3.2 Password handling

Passwords must be processed using native PHP functions:

```php
password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $storedHash);
```

Plain-text passwords must never be stored or logged.

### 3.3 Session handling

A PHP session will maintain the authenticated user state.

The session should contain only essential information:

```text
user_id
full_name
role
account_status
```

The full password hash, OTP, API credentials, or other sensitive data must never be stored in the session.

### 3.4 OTP responsibility

| Responsibility | System |
|---|---|
| Generate OTP | EcoLot-LK |
| Store OTP hash | EcoLot-LK database |
| Send OTP by SMS | Notify.lk |
| Verify submitted OTP | EcoLot-LK |
| Apply expiry and attempt limits | EcoLot-LK |
| Activate verified account | EcoLot-LK |
| Maintain login state | PHP session |

---

## 4. Registration Workflows

## 4.1 Public User Registration

```text
Public registration form
        ↓
Validate submitted information
        ↓
Normalize mobile number
        ↓
Check mobile number and email uniqueness
        ↓
Create users record with PENDING status
        ↓
Create public_profiles record
        ↓
Generate and store OTP
        ↓
Send OTP through Notify.lk
        ↓
Display OTP verification page
        ↓
Verify submitted OTP
        ↓
Set mobile_verified_at
        ↓
Change account status to ACTIVE
        ↓
Create authenticated PHP session
        ↓
Redirect to Public User dashboard
```

### Public User status changes

```text
PENDING → ACTIVE
```

The status changes to `ACTIVE` only after successful mobile verification.

---

## 4.2 Recycler Registration

```text
Recycler registration form
        ↓
Validate personal and company information
        ↓
Normalize mobile number
        ↓
Check mobile number, email, and company uniqueness
        ↓
Create users record with PENDING status
        ↓
Create authorized_recyclers record with PENDING status
        ↓
Generate and send OTP
        ↓
Verify submitted OTP
        ↓
Set mobile_verified_at
        ↓
Keep account status as PENDING
        ↓
Wait for Admin verification
        ↓
Admin approves Recycler
        ↓
Change Recycler verification status to VERIFIED
        ↓
Change user account status to ACTIVE
```

Recycler OTP verification and Admin verification are separate processes:

- OTP confirms ownership of the mobile number.
- Admin verification confirms the Recycler’s business eligibility.

A mobile-verified Recycler must not receive full Recycler access until Admin approval is completed.

---

## 5. Notify.lk Integration Design

## 5.1 API endpoint

```text
POST https://app.notify.lk/api/v1/send
```

Required request values:

```text
user_id
api_key
sender_id
to
message
```

Example conceptual request:

```text
user_id   = NOTIFY_USER_ID
api_key   = NOTIFY_API_KEY
sender_id = EcoLotLK
to        = 94771234567
message   = Your EcoLot-LK verification code is 482913.
            It expires in 5 minutes. Do not share this code.
```

Expected success response:

```json
{
  "status": "success",
  "data": "Sent"
}
```

Official documentation: [Notify.lk API endpoints](https://developer.notify.lk/api-endpoints/)

## 5.2 API communication rules

- Use HTTPS only.
- Use a POST request.
- Call the API from the PHP backend, never from browser JavaScript.
- Do not expose the API key in HTML or JavaScript.
- Apply connection and response timeouts.
- Parse and validate the JSON response.
- Treat only a confirmed success response as a successful send request.
- Do not display internal Notify.lk errors to users.
- Log a safe error reference for failed requests.
- Do not automatically retry multiple times because duplicate SMS messages may be sent.

## 5.3 Sender ID

An approved Notify.lk Sender ID must be obtained for EcoLot-LK.

The `NotifyDEMO` sender must not be used for OTP messages. Notify.lk warns that using the demo sender for OTP content may result in account suspension.

Recommended sender:

```text
EcoLotLK
```

Recommended message:

```text
Your EcoLot-LK verification code is 482913.
It expires in 5 minutes. Do not share this code.
```

## 5.4 API credentials

The following values must be stored outside public files:

```text
NOTIFY_USER_ID
NOTIFY_API_KEY
NOTIFY_SENDER_ID
```

Requirements:

- Do not commit real API credentials to Git.
- Do not place them inside controllers or views.
- Do not expose them through frontend JavaScript.
- Do not include them in screenshots, reports, or error messages.
- Use server environment configuration or a private ignored configuration file.

---

## 6. Mobile Number Rules

All Sri Lankan mobile numbers should be converted into a single database format.

### Accepted user inputs

```text
0771234567
+94771234567
94771234567
077 123 4567
```

### Stored and API format

```text
94771234567
```

### Validation requirements

- Remove spaces and permitted formatting characters.
- Replace the initial `0` with `94`.
- Remove an optional leading `+`.
- Confirm that the final number matches an accepted Sri Lankan mobile format.
- Reject invalid, incomplete, or unsupported numbers.
- Apply the same normalization during registration and login.
- Enforce the database unique constraint after normalization.

---

## 7. Database Changes

The existing `users` table already provides:

```text
mobile_number
password_hash
role
account_status
mobile_verified_at
must_change_password
```

A separate table should be added for OTP challenges.

### Proposed table

```sql
CREATE TABLE mobile_verification_otps (
    otp_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    purpose ENUM('REGISTRATION', 'PASSWORD_RESET') NOT NULL
        DEFAULT 'REGISTRATION',
    otp_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    invalidated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (otp_id),

    KEY idx_otp_user_purpose (
        user_id,
        purpose,
        verified_at,
        invalidated_at
    ),

    CONSTRAINT fk_mobile_otps_user
        FOREIGN KEY (user_id)
        REFERENCES users (user_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
```

`PASSWORD_RESET` may remain unused during the first implementation. It allows the table to support password recovery later without redesigning the database.

### OTP database rules

- Store only a hash of the OTP.
- Never store the plain OTP.
- Each new OTP must invalidate previous active OTPs for the same user and purpose.
- A verified OTP cannot be reused.
- An expired OTP cannot be verified.
- A locked OTP cannot be verified.
- All OTP checks must be performed on the server.

---

## 8. OTP Rules

| Rule | Recommended value |
|---|---:|
| OTP length | 6 digits |
| OTP generation | `random_int(100000, 999999)` |
| Validity period | 5 minutes |
| Maximum attempts | 5 |
| Resend cooldown | 60 seconds |
| Maximum sends per hour | 5 per mobile number |
| Maximum sends per day | 10 per mobile number |
| Successful OTP reuse | Not allowed |
| Previous OTP after resend | Invalidated |

### OTP generation

Use:

```php
random_int(100000, 999999);
```

Do not use:

```php
rand();
mt_rand();
```

### OTP storage

Before storage:

```php
password_hash($otp, PASSWORD_DEFAULT);
```

During verification:

```php
password_verify($submittedOtp, $storedOtpHash);
```

---

## 9. Proposed Routes

### Authentication routes

```text
GET   /login
POST  /login
POST  /logout
```

### Public registration routes

```text
GET   /register/public
POST  /register/public
```

### Recycler registration routes

```text
GET   /register/recycler
POST  /register/recycler
```

### Mobile verification routes

```text
GET   /verify-mobile
POST  /verify-mobile
POST  /verify-mobile/resend
```

### Route responsibilities

| Route | Responsibility |
|---|---|
| `POST /register/public` | Validate and create pending Public User |
| `POST /register/recycler` | Validate and create pending Recycler |
| `GET /verify-mobile` | Display OTP form |
| `POST /verify-mobile` | Validate OTP and update verification state |
| `POST /verify-mobile/resend` | Apply cooldown and send a new OTP |
| `POST /login` | Authenticate active user |
| `POST /logout` | Destroy authenticated session |

State-changing actions must not use GET requests.

---

## 10. Proposed Application Components

### Existing files to extend

```text
app/Controllers/AuthController.php
app/Core/Auth.php
app/Core/Session.php
app/Core/Validator.php
app/Models/User.php
routes/web.php
public/index.php
config/app.php
database/schema.sql
app/Views/auth/login.php
app/Views/auth/register-public.php
app/Views/auth/register-recycler.php
```

### Proposed new files

```text
app/Models/MobileVerificationOtp.php
app/Services/NotifySmsService.php
app/Services/OtpService.php
app/Views/auth/verify-mobile.php
public/assets/css/auth/verify-mobile.css
public/assets/js/auth/verify-mobile.js
config/notify.php
```

Because the current autoloader loads only Core, Controllers, and Models, the proposed `app/Services/` directory must also be added to the autoloader.

### Component responsibilities

#### `NotifySmsService`

- Build Notify.lk request
- Execute native PHP cURL request
- Apply timeouts
- Parse JSON response
- Return a controlled success/failure result
- Hide API-specific details from controllers

#### `OtpService`

- Generate OTP
- Hash OTP
- Set expiry
- Invalidate previous OTP
- Enforce resend limits
- Verify submitted OTP
- Increment failed attempt count
- Mark OTP as verified

#### `MobileVerificationOtp` model

- Store OTP challenge
- Find latest active challenge
- Invalidate previous challenges
- Record attempts
- Record successful verification

#### `AuthController`

- Process registration
- Display verification page
- Process OTP submission
- Process OTP resend
- Process login
- Process logout
- Redirect users according to role

---

## 11. Registration Data Handling

## 11.1 Validation

Public User registration should validate:

```text
first_name
last_name
contact_number
email
password
password_confirmation
postal_code
address
```

Recycler registration should validate:

```text
responsible_person_name
contact_number
email
password
password_confirmation
company_name
business_address
district
required licence/business details
```

### Password requirements

Recommended minimum rules:

- At least 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- Maximum reasonable length, such as 72–128 characters
- Password confirmation must match

Client-side validation may improve usability, but every rule must also be enforced by PHP.

## 11.2 Database transaction

Account and profile creation should be atomic:

```text
Begin transaction
    Create users record
    Create role-specific profile
Commit transaction
```

If profile creation fails:

```text
Rollback transaction
```

The external Notify.lk API call should be made after the database transaction is committed. A slow external API request must not keep a database transaction open.

If SMS sending fails:

- Keep the user as `PENDING`.
- Display a safe failure message.
- Allow a controlled resend.
- Do not create a duplicate account during the next attempt.

---

## 12. OTP Verification Session

During mobile verification, the session may contain:

```text
pending_verification_user_id
pending_verification_role
otp_last_sent_at
```

It must not contain:

```text
plain OTP
OTP hash
password
Notify.lk API key
```

If the verification session is lost, the application may allow the user to restart verification using the registered mobile number after applying the required rate limits.

---

## 13. Login Workflow

```text
Login form submitted
        ↓
Validate CSRF token
        ↓
Normalize mobile number
        ↓
Find user by normalized mobile number
        ↓
Verify password
        ↓
Confirm mobile_verified_at is not null
        ↓
Confirm account status is ACTIVE
        ↓
Regenerate session ID
        ↓
Store minimal user information in session
        ↓
Redirect according to role
```

### Role redirects

| Role | Redirect |
|---|---|
| `ADMIN` | `/admin/dashboard` |
| `PUBLIC_USER` | `/user/dashboard` |
| `MUNICIPAL_OFFICER` | `/officer/dashboard` |
| `COLLECTOR` | `/collector/dashboard` |
| `RECYCLER` | `/recycler/dashboard` |

### Login restrictions

| Condition | Result |
|---|---|
| Incorrect mobile or password | Reject login |
| Mobile not verified | Redirect to verification process |
| Account `PENDING` | Reject full login and explain pending approval |
| Account `SUSPENDED` | Reject login |
| Account `DISABLED` | Reject login |
| Recycler not Admin-verified | Reject Recycler dashboard access |

The public error message for invalid credentials should be generic:

```text
The mobile number or password is incorrect.
```

This prevents attackers from discovering registered mobile numbers.

---

## 14. Logout Workflow

Logout must:

1. Remove authentication data from the session.
2. Clear the session cookie.
3. Destroy the session.
4. Regenerate or invalidate the session ID.
5. Redirect to the login page.

Logout must use:

```text
POST /logout
```

The logout request must include a CSRF token.

---

## 15. Role-Based Access Control

Every protected page must confirm:

1. The user is authenticated.
2. The account is active.
3. The user has the required role.
4. Recycler-specific verification is complete when applicable.

Examples:

```text
/admin/*      → ADMIN only
/officer/*    → MUNICIPAL_OFFICER only
/collector/*  → COLLECTOR only
/recycler/*   → RECYCLER only
/user/*       → PUBLIC_USER only
```

Unauthorized requests should produce:

- Redirect to login when the user is not authenticated.
- `403 Forbidden` when an authenticated user has the wrong role.

The current routes are not protected by middleware. Therefore, a custom route guard mechanism or controller-level authorization checks must be introduced.

---

## 16. CSRF Protection

All POST forms must include a CSRF token:

```text
Registration
OTP verification
OTP resend
Login
Logout
Admin account activation
Recycler approval
```

### CSRF flow

```text
Generate random token
        ↓
Store token in PHP session
        ↓
Place token in hidden form field
        ↓
Compare submitted and stored tokens using hash_equals()
        ↓
Reject request if missing or invalid
```

Generate tokens using:

```php
bin2hex(random_bytes(32));
```

Compare tokens using:

```php
hash_equals($storedToken, $submittedToken);
```

---

## 17. Session Security

Before starting the session, configure:

```text
HttpOnly = true
SameSite = Lax
Secure = true in HTTPS production
Use only cookies for the session ID
Strict session mode = enabled
```

Additional requirements:

- Regenerate the session ID after login.
- Regenerate the session ID after privilege changes.
- Apply an inactivity timeout.
- Apply an absolute session lifetime.
- Never pass the session ID through a URL.
- Do not expose session contents to frontend JavaScript.

Suggested timeouts:

| Session type | Suggested lifetime |
|---|---:|
| Normal inactivity timeout | 30 minutes |
| Absolute authenticated lifetime | 8 hours |
| Pending OTP session | 10–15 minutes |

---

## 18. Rate Limiting

Rate limiting must apply to:

- Registration attempts
- OTP send requests
- OTP resend requests
- OTP verification attempts
- Login attempts

Track limits using combinations such as:

```text
Mobile number
IP address
Session ID
Time window
```

Recommended initial limits:

| Action | Limit |
|---|---:|
| OTP verification | 5 attempts per OTP |
| OTP resend | Once every 60 seconds |
| OTP send | 5 per hour per mobile |
| OTP send | 10 per day per mobile |
| Login | 5 failures per 15 minutes |
| Registration | Limited per IP and mobile |

Avoid permanent account locking based only on failed login attempts because an attacker could deliberately lock another user’s account.

---

## 19. Error Handling

### User-facing messages

Use clear but non-sensitive messages:

```text
We could not send the verification code. Please try again.
The verification code is incorrect or has expired.
Too many attempts. Request a new verification code.
Please wait before requesting another code.
Your mobile number has been verified.
Your Recycler account is awaiting Admin approval.
The mobile number or password is incorrect.
```

### Internal logging

Safe logs may include:

```text
Timestamp
Event type
User ID
Masked mobile number
Notify.lk response status
Internal error category
Request correlation ID
```

Logs must not include:

```text
Plain OTP
Password
Password hash
Notify.lk API key
Full session ID
CSRF token
```

Example masked mobile number:

```text
9477*****67
```

---

## 20. Notify.lk Failure Scenarios

| Scenario | Application behaviour |
|---|---|
| Network timeout | Keep account pending and allow controlled resend |
| Invalid API credentials | Log configuration error and show generic message |
| Invalid Sender ID | Log provider response and stop repeated retries |
| Insufficient balance | Log operational error and notify system administrator |
| Invalid mobile format | Reject before calling Notify.lk |
| API returns invalid JSON | Treat as failed send |
| Duplicate submit | Prevent duplicate OTP/account creation |
| Notify.lk accepts SMS but delivery is delayed | Keep OTP valid until normal expiry |
| SMS arrives after OTP expiry | User requests a new OTP |

Notify.lk account balance can optionally be monitored using:

```text
https://app.notify.lk/api/v1/status
```

This is an operational feature and is not required for the first authentication release.

---

## 21. Frontend Requirements

## 21.1 Registration pages

Required UI states:

- Normal form
- Field validation errors
- Duplicate mobile/email error
- Registration processing state
- SMS sending failure
- Redirect to OTP page

## 21.2 OTP page

The OTP page should contain:

- Six-digit OTP input
- Verify button
- Masked mobile number
- Expiry guidance
- Resend countdown
- Resend button
- Change/restart registration option
- Accessible error and success messages

Example display:

```text
We sent a verification code to 077 *** **67.
The code expires in 5 minutes.
```

The countdown improves usability, but server time remains authoritative.

## 21.3 JavaScript role

JavaScript may be used for:

- Input formatting
- OTP input movement
- Resend countdown display
- Preventing accidental double-submit
- Client-side validation feedback

JavaScript must not be responsible for:

- OTP verification
- Account activation
- Security limits
- Permission checks
- Session authentication

---

## 22. “Remember Me” Decision

The current login interface contains a “Remember me” option.

A normal PHP session should not pretend to support persistent login. Two safe options are available:

### Phase-one recommendation

Hide or disable “Remember me” until persistent authentication is implemented securely.

### Future implementation

If required later, create a separate persistent-login token table using:

- Random selector
- Random validator
- Hashed validator in the database
- Short expiry
- Token rotation
- Device-level revocation

Do not store the password or a permanent user ID directly inside a long-lived cookie.

---

## 23. Testing Plan

## 23.1 Registration tests

- Valid Public User registration
- Valid Recycler registration
- Missing required fields
- Invalid mobile number
- Duplicate mobile number
- Duplicate email
- Invalid postal area
- Weak password
- Password confirmation mismatch
- Profile insertion failure
- Repeated form submission

## 23.2 OTP tests

- Correct OTP
- Incorrect OTP
- Expired OTP
- Reused OTP
- OTP after maximum attempts
- Resend before cooldown
- Resend after cooldown
- Old OTP after resend
- Notify.lk timeout
- Notify.lk error response
- Invalid JSON response
- SMS send failure after user creation
- Verification without a pending session

## 23.3 Login tests

- Correct mobile and password
- Incorrect password
- Unknown mobile number
- Unverified mobile number
- Pending Recycler
- Active Recycler
- Suspended account
- Disabled account
- Role-based dashboard redirect
- Session ID regeneration
- Generic invalid credential error

## 23.4 Authorization tests

- Guest accesses protected route
- Public User accesses Admin route
- Recycler accesses Officer route
- Suspended logged-in user accesses dashboard
- Valid role accesses correct dashboard
- Direct URL navigation without login

## 23.5 Security tests

- SQL injection strings
- HTML/script injection
- Missing CSRF token
- Invalid CSRF token
- OTP brute-force attempts
- Login brute-force attempts
- Session fixation attempt
- Duplicate OTP request
- API key exposure check
- Sensitive log data check
- Cookie security attribute check

---

## 24. Development Phases

## Phase 1 — Foundation

- Confirm registration and login business rules.
- Finalize mobile number format.
- Define account status transitions.
- Define Recycler approval behaviour.
- Add private Notify.lk configuration.
- Add Services directory to the custom autoloader.
- Add the OTP database table.

**Deliverable:** Database and configuration foundation.

## Phase 2 — Security Utilities

- Add CSRF token generation and validation.
- Add mobile normalization and validation.
- Add password validation.
- Configure secure session cookies.
- Add authentication and role guards.
- Define safe logging helpers.

**Deliverable:** Reusable native PHP security layer.

## Phase 3 — Notify.lk and OTP Services

- Create Notify.lk SMS service using native PHP cURL.
- Add timeout and response validation.
- Create OTP generation service.
- Add OTP hashing and expiry.
- Add attempt tracking.
- Add resend cooldown.
- Add previous-OTP invalidation.

**Deliverable:** Tested OTP delivery and verification services.

## Phase 4 — Public User Registration

- Process Public User form submission.
- Create pending user and public profile.
- Send OTP.
- Build OTP verification page.
- Activate Public User after successful verification.
- Create authenticated session.
- Redirect to Public User dashboard.

**Deliverable:** Complete Public User self-registration.

## Phase 5 — Recycler Registration

- Process Recycler form submission.
- Create pending user and Recycler profile.
- Send and verify mobile OTP.
- Keep account pending after mobile verification.
- Connect Admin verification to account activation.
- Prevent unapproved Recycler dashboard access.

**Deliverable:** Complete two-stage Recycler onboarding.

## Phase 6 — Login and Logout

- Process mobile/password login.
- Apply mobile normalization.
- Verify password and account state.
- Regenerate session ID.
- Redirect based on role.
- Implement secure logout.
- Handle unverified and pending accounts.

**Deliverable:** Complete session-based authentication.

## Phase 7 — Route Protection

- Protect all Admin routes.
- Protect all Officer routes.
- Protect all Collector routes.
- Protect all Recycler routes.
- Protect all Public User routes.
- Return appropriate redirects and HTTP status codes.

**Deliverable:** Role-based access control across the application.

## Phase 8 — Rate Limiting and Error Handling

- Add login attempt limits.
- Add OTP request limits.
- Add resend limits.
- Add safe operational logs.
- Add Notify.lk failure handling.
- Add user-friendly messages.

**Deliverable:** Abuse-resistant authentication workflow.

## Phase 9 — Testing and Review

- Run all functional tests.
- Run security test cases.
- Test using an approved Notify.lk Sender ID.
- Test on multiple Sri Lankan mobile networks if possible.
- Confirm secrets are not committed.
- Confirm OTPs and passwords do not appear in logs.
- Review database status transitions.
- Document test evidence.

**Deliverable:** Verified authentication release.

---

## 25. Recommended Team Task Allocation

| Task | Suggested owner |
|---|---|
| Database OTP table and queries | Backend/Database member |
| Notify.lk API service | Backend member |
| OTP generation and verification | Backend member |
| Session and CSRF security | Backend/Security member |
| Registration processing | Backend member |
| Login, logout, and role guards | Backend member |
| OTP page and validation UI | Frontend member |
| Registration/login UI integration | Frontend member |
| Functional and security testing | QA/member not owning the feature |
| Documentation and diagrams | Documentation member |

All team members should understand the full authentication flow even if implementation tasks are divided.

---

## 26. Definition of Done

The authentication feature is complete only when:

- Public Users can self-register.
- Recycler accounts can self-register.
- Mobile numbers are normalized consistently.
- OTP messages are sent using Notify.lk.
- OTPs expire after the configured period.
- Plain OTPs are not stored.
- Failed OTP attempts are limited.
- OTP resend is rate-limited.
- Previous OTPs become invalid after resend.
- Public Users become active only after OTP verification.
- Recyclers require both OTP verification and Admin approval.
- Active users can log in using mobile number and password.
- Passwords are securely hashed.
- Session IDs regenerate after login.
- Users can securely log out.
- Protected routes require authentication.
- Role permissions are enforced.
- CSRF protection covers all POST actions.
- API credentials are not committed or exposed.
- Notify.lk failures are handled safely.
- All functional and security test cases pass.
- The implementation uses no external framework or authentication library.

---

## 27. Out of Scope for the Initial Release

The following features should be treated as future enhancements:

- OTP-only passwordless login
- Password reset using OTP
- Persistent “Remember me” login
- Multiple trusted devices
- Email verification
- Two-factor authentication on every login
- International phone number support
- Alternative SMS provider failover
- Delivery-report webhooks
- Biometric authentication

---

## 28. Final Architecture Summary

```text
Browser
   │
   │ Registration/Login/OTP forms
   ▼
EcoLot-LK PHP Application
   │
   ├── Validation
   ├── CSRF protection
   ├── Password hashing
   ├── OTP generation and verification
   ├── Account status management
   ├── Role authorization
   └── PHP session management
   │
   ├──────────────► MySQL/MariaDB
   │                Users, profiles, OTP hashes and statuses
   │
   └──────────────► Notify.lk API
                    SMS delivery only
```

The final recommended solution is:

> Native PHP session authentication with mobile-number/password login, EcoLot-LK-managed OTP verification, Notify.lk SMS delivery, and role-based access control.