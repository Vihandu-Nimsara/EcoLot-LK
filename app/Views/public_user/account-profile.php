<?php
$fullName = trim((string) ($user['full_name'] ?? ''));
$initials = '';
foreach (array_slice(preg_split('/\s+/', $fullName) ?: [], 0, 2) as $part) {
    $initials .= mb_strtoupper(mb_substr($part, 0, 1));
}
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<section class="account-profile-page">
    <div class="page-toolbar">
        <div>
            <h1>Account Profile</h1>
            <p>Review your personal, contact, and security information.</p>
        </div>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="form-alert form-alert-error" role="alert"><?= $e($errorMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="form-alert form-alert-success" role="status"><?= $e($successMessage) ?></div>
    <?php endif; ?>

    <div class="account-profile-grid">
        <div class="profile-column">
            <section class="surface-card">
                <div class="card-heading">
                    <div>
                        <h2>Personal Information</h2>
                        <p>Your registered account identity.</p>
                    </div>
                </div>
                <div class="profile-avatar"><?= $e($initials) ?></div>
                <div class="form-field">
                    <label for="full-name">Full Name</label>
                    <input id="full-name" type="text" value="<?= $e($fullName) ?>" readonly>
                </div>
            </section>

            <section class="surface-card">
                <div class="card-heading">
                    <div>
                        <h2>Security &amp; Privacy</h2>
                        <p>Manage password and account access.</p>
                    </div>
                </div>
                <div class="security-actions">
                    <button type="button" class="secondary-btn" data-profile-toggle="password">Change Password</button>
                    <button type="button" class="danger-outline-btn" data-profile-toggle="delete">Delete Account</button>
                </div>

                <form class="security-form" method="post" action="<?= $basePath ?>/user/profile/password"
                    data-profile-panel="password" <?= $openForm === 'password' ? '' : 'hidden' ?>>
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <div class="form-field">
                        <label for="current-password">Current Password</label>
                        <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="form-field">
                        <label for="new-password">New Password</label>
                        <input id="new-password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        <p class="field-hint">At least 8 characters with an uppercase letter, a lowercase letter, and a number.</p>
                    </div>
                    <div class="form-field">
                        <label for="confirm-password">Confirm New Password</label>
                        <input id="confirm-password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="secondary-btn" data-profile-cancel>Cancel</button>
                        <button type="submit" class="primary-btn">Update Password</button>
                    </div>
                </form>

                <form class="security-form" method="post" action="<?= $basePath ?>/user/profile/delete"
                    data-profile-panel="delete" <?= $openForm === 'delete' ? '' : 'hidden' ?>>
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrfToken) ?>">
                    <p class="delete-warning">
                        Your account will be disabled and you will be logged out. You can only do this when you
                        have no active pickup requests. Your request history is kept.
                    </p>
                    <div class="form-field">
                        <label for="delete-password">Enter your password to confirm</label>
                        <input id="delete-password" name="delete_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="secondary-btn" data-profile-cancel>Keep Account</button>
                        <button type="submit" class="danger-btn">Delete My Account</button>
                    </div>
                </form>
            </section>
        </div>

        <section class="surface-card">
            <div class="card-heading">
                <div>
                    <h2>Contact Information</h2>
                    <p>Contact support if these registered details need to change.</p>
                </div>
            </div>
            <div class="form-field">
                <label for="profile-email">Email</label>
                <input id="profile-email" type="text" value="<?= $e($user['email'] ?? '') ?>" readonly>
            </div>
            <div class="form-field">
                <label for="profile-phone">Phone</label>
                <input id="profile-phone" type="tel" value="<?= $e($user['mobile_number'] ?? '') ?>" readonly>
            </div>
            <div class="form-field">
                <label for="profile-area">Postal Area</label>
                <input id="profile-area" type="text" value="<?= $e($postalLabel) ?>" readonly>
            </div>
            <div class="form-field">
                <label for="profile-address">Address</label>
                <textarea id="profile-address" rows="3" readonly><?= $e($profile['address'] ?? '') ?></textarea>
            </div>
            <p class="locked-details-note">These details were provided during registration and cannot be edited here.</p>
        </section>
    </div>
</section>