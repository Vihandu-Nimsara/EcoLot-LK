<?php
$error = isset($error) && is_string($error) ? $error : '';
$success = isset($success) && is_string($success) ? $success : '';
$maskedMobile = isset($maskedMobile) && is_string($maskedMobile)
    ? $maskedMobile
    : '';
$csrfToken = isset($csrfToken) && is_string($csrfToken)
    ? $csrfToken
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Mobile Number | EcoLot LK</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-shared.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/verify-mobile.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="verification-page">
    <section class="verification-card" aria-labelledby="verification-title">
        <img
            class="verification-logo"
            src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>"
            alt="EcoLot LK"
        >

        <h1 id="verification-title">Verify Your Mobile Number</h1>
        <p>
            Enter the six-digit code sent to
            <strong><?= htmlspecialchars($maskedMobile, ENT_QUOTES, 'UTF-8') ?></strong>.
            The code expires in five minutes.
        </p>

        <?php if ($error !== ''): ?>
            <p class="verification-alert verification-alert-error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <p class="verification-alert verification-alert-success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form
            class="verification-form"
            action="<?= htmlspecialchars($basePath . '/verify-mobile', ENT_QUOTES, 'UTF-8') ?>"
            method="post"
        >
            <input
                type="hidden"
                name="_csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="otp">Verification code</label>
            <input
                id="otp"
                name="otp"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                pattern="[0-9]{6}"
                minlength="6"
                maxlength="6"
                placeholder="000000"
                required
                autofocus
            >

            <button type="submit">Verify Mobile Number</button>
        </form>

        <form
            class="resend-form"
            action="<?= htmlspecialchars($basePath . '/verify-mobile/resend', ENT_QUOTES, 'UTF-8') ?>"
            method="post"
        >
            <input
                type="hidden"
                name="_csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
            >
            <button type="submit">Resend verification code</button>
        </form>

        <a class="restart-link" href="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>">
            Restart registration
        </a>
    </section>
</main>
</body>
</html>
