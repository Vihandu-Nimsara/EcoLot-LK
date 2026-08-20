<?php
$maskedMobile = htmlspecialchars(
    (string) ($summary['masked_mobile'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycler Application Pending | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-shared.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-recycler.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="registration-page recycler-registration-page">
    <section class="registration-card recycler-registration-card">
        <div class="registration-form-panel">
            <section class="registration-result" tabindex="-1">
                <h1>Mobile Number Verified</h1>
                <p>Your recycler application is under administrator review.</p>
                <h2>Application status</h2>
                <ol>
                    <li>Mobile Number <?= $maskedMobile ?> — Verified</li>
                    <li>Company Verification — Pending</li>
                    <li>License Verification — Pending</li>
                    <li>Capabilities — Pending</li>
                    <li>Authorized Activities — Pending</li>
                </ol>
                <p>You cannot use recycler bidding or processing functions until the application is approved.</p>
                <a class="create-profile-btn" href="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>">Back to Login</a>
            </section>
        </div>
        <div class="leaf-artwork" aria-hidden="true"><img src="<?= htmlspecialchars($basePath . '/assets/images/registration-leaf.svg', ENT_QUOTES, 'UTF-8') ?>" alt="" class="registration-leaf-image" draggable="false"></div>
        <aside class="registration-brand-panel"><div class="brand-content"><div class="brand-logo-row"><img src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="EcoLot LK" class="brand-logo"></div><h2>Welcome To<br><span class="brand-name">EcoLotLK</span></h2><p>Thank you for helping build a responsible E-Waste network.</p></div></aside>
    </section>
</main>
</body>
</html>
