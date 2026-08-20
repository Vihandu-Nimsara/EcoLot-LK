<?php
$csrfToken = isset($csrfToken) && is_string($csrfToken) ? $csrfToken : '';
$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
$districts = isset($districts) && is_array($districts) ? $districts : [];
$activities = isset($activities) && is_array($activities) ? $activities : [];
$categories = isset($categories) && is_array($categories) ? $categories : [];
$selectedActivities = is_array($old['activities'] ?? null) ? $old['activities'] : [];
$selectedCapabilities = is_array($old['requested_capabilities'] ?? null)
    ? array_map('strval', $old['requested_capabilities'])
    : [];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$error = static fn (string $field): ?string => isset($errors[$field][0])
    ? (string) $errors[$field][0]
    : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycling Company Registration | EcoLot LK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@600;700&amp;family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $escape($basePath . '/assets/css/auth/register-shared.css') ?>">
    <link rel="stylesheet" href="<?= $escape($basePath . '/assets/css/auth/register-recycler.css') ?>">
</head>
<body>
<main class="registration-page recycler-registration-page">
    <section class="registration-card recycler-registration-card">
        <div class="registration-form-panel">
            <header class="registration-heading">
                <a class="back-link" href="<?= $escape($basePath . '/register') ?>">← Choose account type</a>
                <h1>Register Your Company</h1>
                <p>Submit your company and existing CEA licence information for EcoLot verification.</p>
            </header>

            <?php if ($error('general') !== null): ?>
                <p class="registration-group-error" role="alert"><?= $escape($error('general')) ?></p>
            <?php endif; ?>

            <form class="registration-form" action="<?= $escape($basePath . '/register/recycler') ?>" method="post" enctype="multipart/form-data" data-recycler-registration novalidate>
                <input type="hidden" name="_csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

                <fieldset class="registration-section">
                    <legend><span>A</span> Company / Account Information</legend>
                    <div class="recycler-form-grid">
                        <label class="form-group"><span>Company Name</span><input type="text" name="company_name" autocomplete="organization" maxlength="180" value="<?= $escape($old['company_name'] ?? '') ?>" required><?php if ($error('company_name')): ?><small class="registration-group-error" role="alert"><?= $escape($error('company_name')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>Contact Person</span><input type="text" name="contact_person" autocomplete="name" maxlength="120" value="<?= $escape($old['contact_person'] ?? '') ?>" required><?php if ($error('contact_person')): ?><small class="registration-group-error" role="alert"><?= $escape($error('contact_person')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>Business Email</span><input type="email" name="company_email" autocomplete="email" maxlength="255" value="<?= $escape($old['company_email'] ?? '') ?>" required><?php if ($error('company_email')): ?><small class="registration-group-error" role="alert"><?= $escape($error('company_email')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>Phone Number</span><input type="tel" name="phone" autocomplete="tel" maxlength="20" value="<?= $escape($old['phone'] ?? '') ?>" required><?php if ($error('phone')): ?><small class="registration-group-error" role="alert"><?= $escape($error('phone')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>Password</span><input type="password" name="company_password" autocomplete="new-password" minlength="8" maxlength="128" required><?php if ($error('company_password')): ?><small class="registration-group-error" role="alert"><?= $escape($error('company_password')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>Confirm Password</span><input type="password" name="confirm_password" autocomplete="new-password" minlength="8" maxlength="128" required><?php if ($error('confirm_password')): ?><small class="registration-group-error" role="alert"><?= $escape($error('confirm_password')) ?></small><?php endif; ?></label>
                        <label class="form-group recycler-field-wide"><span>Business Address</span><textarea name="business_address" rows="3" autocomplete="street-address" maxlength="500" required><?= $escape($old['business_address'] ?? '') ?></textarea><?php if ($error('business_address')): ?><small class="registration-group-error" role="alert"><?= $escape($error('business_address')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>District</span><select name="district" required><option value="">Select district</option><?php foreach ($districts as $district): ?><option value="<?= $escape($district) ?>" <?= ($old['district'] ?? '') === $district ? 'selected' : '' ?>><?= $escape($district) ?></option><?php endforeach; ?></select><?php if ($error('district')): ?><small class="registration-group-error" role="alert"><?= $escape($error('district')) ?></small><?php endif; ?></label>
                    </div>
                </fieldset>

                <fieldset class="registration-section">
                    <legend><span>B</span> CEA Licence Information</legend>
                    <p class="section-helper">EcoLot records and verifies information from your existing CEA-issued licence; it does not issue the licence.</p>
                    <div class="recycler-form-grid">
                        <label class="form-group recycler-field-wide"><span>Licence Type</span><input value="Scheduled Waste Management Licence (SWML)" readonly aria-readonly="true"></label>
                        <label class="form-group"><span>SWML Number</span><input type="text" name="swml_number" maxlength="100" value="<?= $escape($old['swml_number'] ?? '') ?>" required><?php if ($error('swml_number')): ?><small class="registration-group-error" role="alert"><?= $escape($error('swml_number')) ?></small><?php endif; ?></label>
                        <label class="form-group"><span>Expiry Date</span><input type="date" name="licence_expiry" value="<?= $escape($old['licence_expiry'] ?? '') ?>" required><?php if ($error('licence_expiry')): ?><small class="registration-group-error" role="alert"><?= $escape($error('licence_expiry')) ?></small><?php endif; ?></label>
                        <label class="form-group recycler-field-wide"><span>Upload CEA SWML Copy</span><input class="file-input" type="file" name="licence_copy" accept="application/pdf,.pdf" required><small>Upload a PDF copy of the existing CEA-issued Scheduled Waste Management Licence. Maximum 5 MB.</small><?php if ($error('licence_copy')): ?><small class="registration-group-error" role="alert"><?= $escape($error('licence_copy')) ?></small><?php endif; ?></label>
                    </div>
                </fieldset>

                <fieldset class="registration-section">
                    <legend><span>C</span> Authorized Activities Claimed</legend>
                    <p class="section-helper">Select the activities shown on your CEA licence. These claims remain subject to Administrator verification.</p>
                    <div class="option-grid">
                        <?php foreach ($activities as $activityValue => $activityLabel): ?>
                            <label class="check-option"><input type="checkbox" name="activities[]" value="<?= $escape($activityValue) ?>" <?= in_array($activityValue, $selectedActivities, true) ? 'checked' : '' ?>><span><?= $escape($activityLabel) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <p class="registration-group-error" data-activities-error role="alert" <?= $error('activities') === null ? 'hidden' : '' ?>><?= $escape($error('activities') ?? 'Select at least one licence activity.') ?></p>
                </fieldset>

                <fieldset class="registration-section">
                    <legend><span>D</span> Requested Waste-Handling Capabilities</legend>
                    <p class="section-helper">Selected capabilities will be reviewed by the Administrator against your submitted licence information.</p>
                    <div class="option-grid capability-options">
                        <?php foreach ($categories as $category): ?>
                            <?php $categoryId = (string) $category['category_id']; ?>
                            <label class="check-option"><input type="checkbox" name="requested_capabilities[]" value="<?= $escape($categoryId) ?>" <?= in_array($categoryId, $selectedCapabilities, true) ? 'checked' : '' ?>><span><?= $escape($category['category_name']) ?></span></label>
                        <?php endforeach; ?>
                    </div>
                    <p class="registration-group-error" data-capabilities-error role="alert" <?= $error('requested_capabilities') === null ? 'hidden' : '' ?>><?= $escape($error('requested_capabilities') ?? 'Select at least one requested capability.') ?></p>
                </fieldset>

                <div class="form-submit-area">
                    <button type="submit" class="create-profile-btn">Submit for Verification</button>
                    <p class="workflow-link">Registering as a public user? <a href="<?= $escape($basePath . '/register/public') ?>">Register here</a></p>
                    <p class="login-text">Already have an account? <a href="<?= $escape($basePath . '/login') ?>">Log in</a></p>
                </div>
            </form>
        </div>

        <div class="leaf-artwork" aria-hidden="true"><img src="<?= $escape($basePath . '/assets/images/registration-leaf.svg') ?>" alt="" class="registration-leaf-image" draggable="false"></div>
        <aside class="registration-brand-panel"><div class="brand-content"><div class="brand-logo-row"><img src="<?= $escape($basePath . '/assets/images/ecolot-logo.png') ?>" alt="EcoLot LK" class="brand-logo"></div><h2>Welcome To<br><span class="brand-name">EcoLotLK</span></h2><p>Join EcoLot LK as a verified recycling company and help build a responsible E-Waste network.</p></div></aside>
    </section>
</main>
<script src="<?= $escape($basePath . '/assets/js/auth/register-recycler.js') ?>"></script>
</body>
</html>
