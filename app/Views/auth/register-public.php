<?php
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
$csrfToken = isset($csrfToken) && is_string($csrfToken)
    ? $csrfToken
    : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Public User Registration | EcoLot LK</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            $basePath . '/assets/css/auth/register-shared.css',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <link
        rel="stylesheet"
        href="<?= htmlspecialchars(
            $basePath . '/assets/css/auth/register-public.css',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >
</head>

<body>
<main class="registration-page public-registration-page">
    <section class="registration-card">
        <div class="registration-form-panel">
            <header class="registration-heading">
                <a
                    class="back-link"
                    href="<?= htmlspecialchars(
                        $basePath . '/register',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
                    ← Choose account type
                </a>

                <h1>Create Your Profile</h1>
            </header>

            <?php if (!empty($errors['general'])): ?>
                <p class="registration-group-error" role="alert">
                    <?= htmlspecialchars(
                        $errors['general'][0],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>
            <?php endif; ?>

            <form
                class="registration-form"
                action="<?= htmlspecialchars(
                    $basePath . '/register/public',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                method="post"
            >
                <input
                    type="hidden"
                    name="_csrf_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <div class="form-group">
                    <label for="first-name">First Name</label>

                    <input
                        type="text"
                        id="first-name"
                        name="first_name"
                        placeholder="Your first name"
                        autocomplete="given-name"
                        maxlength="60"
                        value="<?= htmlspecialchars(
                            $old['first_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                    <?php if (!empty($errors['first_name'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['first_name'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last-name">Last Name</label>

                    <input
                        type="text"
                        id="last-name"
                        name="last_name"
                        placeholder="Your last name"
                        autocomplete="family-name"
                        maxlength="60"
                        value="<?= htmlspecialchars(
                            $old['last_name'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                    <?php if (!empty($errors['last_name'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['last_name'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="contact-number">Contact Number</label>

                    <input
                        type="tel"
                        id="contact-number"
                        name="contact_number"
                        placeholder="077 123 4567"
                        autocomplete="tel"
                        inputmode="tel"
                        maxlength="20"
                        value="<?= htmlspecialchars(
                            $old['contact_number'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                    <?php if (!empty($errors['contact_number'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['contact_number'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="public-email">Email</label>

                    <input
                        type="email"
                        id="public-email"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="email"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $old['email'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <?php if (!empty($errors['email'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['email'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="public-password">Password</label>

                    <input
                        type="password"
                        id="public-password"
                        name="password"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="128"
                        required
                    >

                    <?php if (!empty($errors['password'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['password'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="public-password-confirmation">
                        Confirm Password
                    </label>

                    <input
                        type="password"
                        id="public-password-confirmation"
                        name="password_confirmation"
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        minlength="8"
                        maxlength="128"
                        required
                    >

                    <?php if (!empty($errors['password_confirmation'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['password_confirmation'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="postal-code">Postal Code</label>

                    <select
                        id="postal-code"
                        name="postal_code"
                        required
                    >
                        <option
                            value=""
                            disabled
                            <?= empty($old['postal_code']) ? 'selected' : '' ?>
                        >
                            Select your area
                        </option>

                        <?php
                        $postalAreas = [
                            'Wellawatte' => '11100',
                            'Rajagiriya' => '10800',
                            'Narahenpita' => '10600',
                            'Kollupitiya' => '10500',
                            'Borella' => '00800',
                            'Cinnamon Gardens' => '00700',
                        ];
                        ?>

                        <?php foreach ($postalAreas as $areaName => $areaCode): ?>
                            <option
                                value="<?= htmlspecialchars(
                                    $areaCode,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                <?= ($old['postal_code'] ?? '') === $areaCode
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $areaName . ' — ' . $areaCode,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (!empty($errors['postal_code'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['postal_code'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>

                    <input
                        type="text"
                        id="address"
                        name="address"
                        placeholder="Enter your address"
                        autocomplete="street-address"
                        maxlength="500"
                        value="<?= htmlspecialchars(
                            $old['address'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                    <?php if (!empty($errors['address'])): ?>
                        <p class="registration-group-error" role="alert">
                            <?= htmlspecialchars(
                                $errors['address'][0],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="form-submit-area">
                    <button
                        type="submit"
                        class="create-profile-btn"
                    >
                        Create Profile
                    </button>

                    <p class="workflow-link">
                        Registering a recycling company?

                        <a
                            href="<?= htmlspecialchars(
                                $basePath . '/register/recycler',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            Register here
                        </a>
                    </p>

                    <p class="login-text">
                        I already have an account,

                        <a
                            href="<?= htmlspecialchars(
                                $basePath . '/login',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            log in
                        </a>
                    </p>
                </div>
            </form>
        </div>

        <div class="leaf-artwork" aria-hidden="true">
            <img
                src="<?= htmlspecialchars(
                    $basePath . '/assets/images/registration-leaf.svg',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                alt=""
                class="registration-leaf-image"
                draggable="false"
            >
        </div>

        <aside class="registration-brand-panel">
            <div class="brand-content">
                <div class="brand-logo-row">
                    <img
                        src="<?= htmlspecialchars(
                            $basePath . '/assets/images/ecolot-logo.png',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="EcoLot LK"
                        class="brand-logo"
                    >
                </div>

                <h2>
                    Welcome To<br>
                    <span class="brand-name">EcoLotLK</span>
                </h2>

                <p>
                    Be a part of EcoLot LK! Create your account
                    to start your journey with us...
                </p>
            </div>
        </aside>
    </section>
</main>

<script
    src="<?= htmlspecialchars(
        $basePath . '/assets/js/auth/register-public.js',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
></script>
</body>
</html>