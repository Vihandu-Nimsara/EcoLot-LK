<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Company Registration | EcoLot LK</title>
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
            <header class="registration-heading">
                <a class="back-link" href="<?= htmlspecialchars($basePath . '/register', ENT_QUOTES, 'UTF-8') ?>">← Choose account type</a>
                <h1>Create Your Profile</h1>
            </header>

            <form class="registration-form" action="<?= htmlspecialchars($basePath . '/register/recycler', ENT_QUOTES, 'UTF-8') ?>" method="post">
                <div class="form-group">
                    <label for="company-name">Company Name</label>
                    <input type="text" id="company-name" name="company_name" placeholder="your company name" autocomplete="organization" required>
                </div>
                <div class="form-group">
                    <label for="contact-person">Contact Person</label>
                    <input type="text" id="contact-person" name="contact_person" placeholder="contact person's name" autocomplete="name" required>
                </div>
                <div class="form-group">
                    <label for="company-email">Business Email</label>
                    <input type="email" id="company-email" name="company_email" placeholder="company@example.com" autocomplete="email" required>
                </div>
                <div class="form-group">
                    <label for="company-password">Password</label>
                    <input type="password" id="company-password" name="company_password" placeholder="create a password" autocomplete="new-password" required>
                </div>
                <div class="form-group">
                    <label for="license-number">Licence Number</label>
                    <input type="text" id="license-number" name="license_number" placeholder="enter licence number" required>
                </div>
                <div class="form-group">
                    <label for="company-district">District</label>
                    <select id="company-district" name="district" required>
                        <option value="">Select district</option>
                        <option value="Colombo">Colombo</option>
                        <option value="Gampaha">Gampaha</option>
                        <option value="Kalutara">Kalutara</option>
                        <option value="Kandy">Kandy</option>
                        <option value="Galle">Galle</option>
                    </select>
                </div>
                <div class="form-submit-area">
                    <button type="submit" class="create-profile-btn">Create profile</button>
                    <p class="workflow-link">Registering as a public user? <a href="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>">Register here</a></p>
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

    <section class="desktop-required-message" aria-labelledby="desktop-required-title">
        <img src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="EcoLot LK" class="desktop-message-logo">
        <h1 id="desktop-required-title">Desktop registration required</h1>
        <p>Recycling company registration is currently available on desktop and laptop devices only.</p>
        <div class="desktop-message-links">
            <a class="message-primary-link" href="<?= htmlspecialchars($basePath . '/register', ENT_QUOTES, 'UTF-8') ?>">Choose another account type</a>
            <a href="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>">Register as a public user</a>
        </div>
    </section>
</main>
<script src="<?= htmlspecialchars($basePath . '/assets/js/auth/register-recycler.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
