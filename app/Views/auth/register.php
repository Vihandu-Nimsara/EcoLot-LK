<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Registration Type | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-shared.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="registration-page registration-choice-page">
    <section class="registration-card registration-choice-card">
        <div class="registration-form-panel">
            <header class="registration-heading">
                <h1>Create Your Profile</h1>
                <p class="registration-intro">Choose the profile that best describes you.</p>
            </header>

            <div class="registration-choices" aria-label="Registration types">
                <a class="registration-choice" href="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="choice-title">Public User</span>
                    <span class="choice-description">Request responsible e-waste collection and track your pickups.</span>
                    <span class="choice-action">Continue as a public user <span aria-hidden="true">→</span></span>
                </a>

                <a class="registration-choice" href="<?= htmlspecialchars($basePath . '/register/recycler', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="choice-title">Recycling Company</span>
                    <span class="choice-description">Register your company to participate in EcoLot recycling workflows.</span>
                    <span class="choice-action">Continue as a company <span aria-hidden="true">→</span></span>
                </a>
            </div>

            <p class="login-text">I have an account already, <a href="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>">login</a></p>
        </div>

        <div class="leaf-artwork" aria-hidden="true">
            <img src="<?= htmlspecialchars($basePath . '/assets/images/registration-leaf.svg', ENT_QUOTES, 'UTF-8') ?>" alt="" class="registration-leaf-image" draggable="false">
        </div>

        <aside class="registration-brand-panel">
            <div class="brand-content">
                <div class="brand-logo-row">
                    <img src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="EcoLot LK" class="brand-logo">
                    <!-- <span class="brand-name">EcoLotLK</span> -->
                </div>
                <h2>Welcome To<br><span class="brand-name">EcoLotLK</span></h2>
                <p>Be a part of EcoLot LK! Create your account to start your journey with us...</p>
            </div>
        </aside>
    </section>
</main>
</body>
</html>
