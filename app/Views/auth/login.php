<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/register-shared.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/login.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="auth-page">
    <section class="login-card" aria-labelledby="login-title">
        <div class="login-form-panel">
            <div class="mobile-brand">
                <img
                    src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>"
                    alt="EcoLot LK"
                >
            </div>

            <header class="login-heading">
                <h1 id="login-title">Sign In</h1>
            </header>

            <div
                class="auth-alert"
                data-auth-alert
                role="alert"
                <?= empty($error) ? 'hidden' : '' ?>
            >
                <?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?>
            </div>

            <?php if (!empty($success)): ?>
                <div class="auth-alert auth-success" role="status">
                    <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form
                class="login-form"
                action="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>"
                method="post"
                novalidate
                data-login-form
            >
                <input
                    type="hidden"
                    name="_csrf_token"
                    value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="form-group">
                    <label for="mobile-number">Mobile Number</label>
                    <input
                        id="mobile-number"
                        name="mobile_number"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="07X XXX XXXX"
                        value="<?= htmlspecialchars($oldMobile ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        aria-describedby="mobile-number-error"
                        required
                    >
                    <p class="field-error" id="mobile-number-error" data-error-for="mobile-number" hidden>Please enter your mobile number.</p>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-control">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Password"
                            aria-describedby="password-error"
                            required
                        >
                        <button
                            class="password-toggle"
                            type="button"
                            aria-label="Show password"
                            aria-pressed="false"
                            data-password-toggle
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                <circle cx="12" cy="12" r="2.75"></circle>
                            </svg>
                        </button>
                    </div>
                    <p class="field-error" id="password-error" data-error-for="password" hidden>Please enter your password.</p>
                </div>

                <label class="remember-control" for="remember-me">
                    <input id="remember-me" name="remember_me" type="checkbox" value="1">
                    <span>Remember me</span>
                </label>

                <button class="sign-in-button" type="submit">Sign In</button>
            </form>

            <p class="create-account-prompt">
                Don't have an account?
                <button type="button" data-account-chooser-open>Create an account</button>
            </p>
        </div>

        <div class="login-leaf-artwork" aria-hidden="true">
            <img
                class="login-leaf-image"
                src="<?= htmlspecialchars($basePath . '/assets/images/registration-leaf.svg', ENT_QUOTES, 'UTF-8') ?>"
                alt=""
                draggable="false"
            >
        </div>

        <aside class="login-brand-panel">
            <div class="brand-content">
                <img
                    class="brand-logo"
                    src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>"
                    alt="EcoLot LK"
                >
                <h2>Welcome Back</h2>
                <p>Sign in to your EcoLot LK account to continue.</p>
            </div>
        </aside>
    </section>
</main>

<div class="account-dialog-backdrop" data-account-dialog hidden>
    <section
        class="account-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="account-dialog-title"
        aria-describedby="account-dialog-description"
    >
        <button class="dialog-close" type="button" aria-label="Close account chooser" data-account-dialog-close>
            <span aria-hidden="true">&times;</span>
        </button>

        <header class="dialog-heading">
            <h2 id="account-dialog-title">Create an Account</h2>
            <p id="account-dialog-description">Choose the type of account you want to create.</p>
        </header>

        <div class="account-options">
            <article class="account-option">
                <div>
                    <h3>Public User</h3>
                    <p>For residents using EcoLot LK e-waste collection services.</p>
                </div>
                <a href="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>">Continue</a>
            </article>

            <article class="account-option">
                <div>
                    <h3>Authorized Recycler</h3>
                    <p>For licensed e-waste recyclers applying to use EcoLot LK.</p>
                </div>
                <a href="<?= htmlspecialchars($basePath . '/register/recycler', ENT_QUOTES, 'UTF-8') ?>">Continue</a>
            </article>
        </div>
    </section>
</div>

<script src="<?= htmlspecialchars($basePath . '/assets/js/auth/login.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
