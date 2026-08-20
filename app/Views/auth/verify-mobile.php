<?php
$csrfToken = isset($csrfToken) && is_string($csrfToken) ? $csrfToken : '';
$maskedMobile = isset($maskedMobile) && is_string($maskedMobile) ? $maskedMobile : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Mobile | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/verify-mobile.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="verification-page">
    <section class="verification-card">
        <img src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="EcoLot LK" class="verification-logo">
        <p class="verification-eyebrow">Mobile verification</p>
        <h1>Enter your code</h1>
        <p class="verification-copy">
            Enter the six-digit code sent to
            <strong><?= htmlspecialchars($maskedMobile, ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>

        <?php if (!empty($notice)): ?>
            <p class="verification-message success" role="status">
                <?= htmlspecialchars((string) $notice, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="verification-message error" role="alert">
                <?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($basePath . '/verify-mobile', ENT_QUOTES, 'UTF-8') ?>" method="post" class="verification-form">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <label for="otp">Verification code</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" placeholder="000000" required autofocus>
            <button type="submit">Verify mobile number</button>
        </form>

        <form action="<?= htmlspecialchars($basePath . '/verify-mobile/resend', ENT_QUOTES, 'UTF-8') ?>" method="post" class="resend-form">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <span>Didn't receive the code?</span>
            <button type="submit">Resend Code</button>
        </form>

        <a class="restart-link" href="<?= htmlspecialchars(
            $basePath . (($isRecycler ?? false) ? '/register/recycler' : '/register/public'),
            ENT_QUOTES,
            'UTF-8'
        ) ?>">Use a different number</a>
    </section>
</main>
</body>
</html>
