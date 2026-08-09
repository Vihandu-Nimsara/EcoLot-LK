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
                <h1>Register Your Company</h1>
                <p>Submit your company and existing CEA licence information for EcoLot verification.</p>
            </header>

            <form class="registration-form" action="<?= htmlspecialchars($basePath . '/register/recycler', ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data" data-recycler-registration>
                <fieldset class="registration-section">
                    <legend><span>A</span> Company / Account Information</legend>
                    <div class="recycler-form-grid">
                        <label class="form-group"><span>Company Name</span><input type="text" name="company_name" autocomplete="organization" required></label>
                        <label class="form-group"><span>Contact Person</span><input type="text" name="contact_person" autocomplete="name" required></label>
                        <label class="form-group"><span>Business Email</span><input type="email" name="company_email" autocomplete="email" required></label>
                        <label class="form-group"><span>Phone Number</span><input type="tel" name="phone" autocomplete="tel" required></label>
                        <label class="form-group"><span>Password</span><input type="password" name="company_password" autocomplete="new-password" minlength="8" required></label>
                        <label class="form-group"><span>Confirm Password</span><input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></label>
                        <label class="form-group recycler-field-wide"><span>Business Address</span><textarea name="business_address" rows="3" autocomplete="street-address" required></textarea></label>
                        <label class="form-group"><span>District</span><select name="district" required><option value="">Select district</option><option>Colombo</option><option>Gampaha</option><option>Kalutara</option><option>Kandy</option><option>Galle</option></select></label>
                    </div>
                </fieldset>

                <fieldset class="registration-section">
                    <legend><span>B</span> CEA Licence Information</legend>
                    <p class="section-helper">EcoLot records and verifies information from your existing CEA-issued licence; it does not issue the licence.</p>
                    <div class="recycler-form-grid">
                        <label class="form-group recycler-field-wide"><span>Licence Type</span><input value="Scheduled Waste Management Licence (SWML)" readonly aria-readonly="true"></label>
                        <label class="form-group"><span>SWML Number</span><input type="text" name="swml_number" required></label>
                        <label class="form-group"><span>Expiry Date</span><input type="date" name="licence_expiry" required></label>
                        <label class="form-group recycler-field-wide"><span>Upload CEA SWML Copy</span><input class="file-input" type="file" name="licence_copy" accept="application/pdf,.pdf" required><small>Upload a PDF copy of the existing CEA-issued Scheduled Waste Management Licence.</small></label>
                    </div>
                </fieldset>

                <fieldset class="registration-section">
                    <legend><span>C</span> Authorized Activities Claimed</legend>
                    <p class="section-helper">Select the activities shown on your CEA licence. These claims remain subject to Administrator verification.</p>
                    <div class="option-grid">
                        <?php foreach (['Collection', 'Transportation', 'Storage', 'Recovery', 'Recycling', 'Disposal'] as $activity): ?>
                            <label class="check-option"><input type="checkbox" name="activities[]" value="<?= htmlspecialchars($activity, ENT_QUOTES, 'UTF-8') ?>"><span><?= htmlspecialchars($activity, ENT_QUOTES, 'UTF-8') ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <fieldset class="registration-section">
                    <legend><span>D</span> Requested Waste-Handling Capabilities</legend>
                    <p class="section-helper">Selected capabilities will be reviewed by the Administrator against your submitted licence information.</p>
                    <div class="option-grid capability-options">
                        <?php foreach (['Automobile E-Waste', 'Demo Battery and Circuit Boards', 'Demo Consumer Electronics', 'Domestic E-Waste', 'Industrial E-Waste', 'Medical E-Waste', 'Office E-Waste'] as $category): ?>
                            <label class="check-option"><input type="checkbox" name="requested_capabilities[]" value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"><span><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="form-submit-area">
                    <button type="submit" class="create-profile-btn">Submit for Verification</button>
                    <p class="registration-preview-notice" role="status" tabindex="-1" data-registration-notice hidden></p>
                    <p class="workflow-link">Registering as a public user? <a href="<?= htmlspecialchars($basePath . '/register/public', ENT_QUOTES, 'UTF-8') ?>">Register here</a></p>
                    <p class="login-text">Already have an account? <a href="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>">Log in</a></p>
                </div>
            </form>
        </div>

        <div class="leaf-artwork" aria-hidden="true"><img src="<?= htmlspecialchars($basePath . '/assets/images/registration-leaf.svg', ENT_QUOTES, 'UTF-8') ?>" alt="" class="registration-leaf-image" draggable="false"></div>
        <aside class="registration-brand-panel"><div class="brand-content"><div class="brand-logo-row"><img src="<?= htmlspecialchars($basePath . '/assets/images/ecolot-logo.png', ENT_QUOTES, 'UTF-8') ?>" alt="EcoLot LK" class="brand-logo"></div><h2>Welcome To<br><span class="brand-name">EcoLotLK</span></h2><p>Join EcoLot LK as a verified recycling company and help build a responsible E-Waste network.</p></div></aside>
    </section>
</main>
<script src="<?= htmlspecialchars($basePath . '/assets/js/auth/register-recycler.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
