<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/auth/login.css?v=20260806c', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
<main class="login-page">
    <section class="login-card" aria-labelledby="login-title">
        <div class="login-form-panel">

            <header class="login-heading">
                <h1 id="login-title">Sign In</h1>
            </header>


            <form class="login-form" action="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>" method="post">
                <div class="form-group">
                    <label for="phone-number">Enter Your Number</label>
                    <input
                        type="tel"
                        id="phone-number"
                        name="phone_number"
                        placeholder="Enter your number"
                        autocomplete="tel"
                        inputmode="tel"
                        pattern="\+?[0-9][0-9\(\) \-]{8,13}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Enter Your Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <label class="remember-option" for="remember-me">
                    <input type="checkbox" id="remember-me" name="remember_me" value="1">
                    <span>Remember me</span>
                </label>

                <div class="login-submit-area">
                    <button type="submit" class="login-button">Login</button>
                    <p class="signup-text">Don’t have an account? <a href="<?= htmlspecialchars($basePath . '/register', ENT_QUOTES, 'UTF-8') ?>">Sign up</a></p>
                </div>
            </form>
        </div>

        <div class="leaf-artwork" aria-hidden="true">
            <img src="<?= htmlspecialchars($basePath . '/assets/images/registration-leaf.svg', ENT_QUOTES, 'UTF-8') ?>" alt="" class="login-leaf-image" draggable="false">

        </div>

        <aside class="login-brand-panel">
            <div class="brand-content">

                <img src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="EcoLot LK" class="brand-logo">
                <h2>Welcome Back</h2>
                <p>To stay connected with us, please sign in with your personal details...</p>

            </div>
        </aside>
    </section>
</main>
<script src="<?= htmlspecialchars($basePath . '/assets/js/auth/login.js?v=20260806', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
