<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public User Registration | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-shared.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-public.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="registration-page public-registration-page">
    <section class="registration-card">
        <div class="registration-form-panel">
            <header class="registration-heading">
                <a class="back-link" href="<?= htmlspecialchars($basePath . '/register', ENT_QUOTES, 'UTF-8') ?>">← Choose account type</a>
                <h1>Create Your Profile</h1>
            </header>

            <form class="registration-form" action="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>" method="post">
                <div class="form-group">
                    <label for="first-name">First Name</label>
                    <input type="text" id="first-name" name="first_name" placeholder="your first name" autocomplete="given-name" required>
                </div>
                <div class="form-group">
                    <label for="last-name">Last Name</label>
                    <input type="text" id="last-name" name="last_name" placeholder="your last name" autocomplete="family-name" required>
                </div>
                <div class="form-group">
                    <label for="contact-number">Contact Number</label>
                    <input type="tel" id="contact-number" name="contact_number" placeholder="enter your contact number" autocomplete="tel" required>
                </div>
                <div class="form-group">
                    <label for="public-email">Email</label>
                    <input type="email" id="public-email" name="email" placeholder="you@example.com" autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="public-password">Password</label>
                    <input type="password" id="public-password" name="password" placeholder="create a password" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="postal-code">Postal Code</label>
                    <input type="text" id="postal-code" name="postal_code" placeholder="enter your postal code" autocomplete="postal-code" required>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" placeholder="enter your address" autocomplete="street-address" required>
                </div>
                <div class="form-submit-area">
                    <button type="submit" class="create-profile-btn">Create profile</button>
                    <p class="workflow-link">Registering a recycling company? <a href="<?= htmlspecialchars($basePath . '/register/recycler', ENT_QUOTES, 'UTF-8') ?>">Register here</a></p>
                    <p class="login-text">I have an account already, <a href="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>">login</a></p>
                </div>
            </form>
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
<script src="<?= htmlspecialchars($basePath . '/assets/js/auth/register-public.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>