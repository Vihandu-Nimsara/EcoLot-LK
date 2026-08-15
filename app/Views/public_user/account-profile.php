<section class="account-profile-page">
    <div class="page-toolbar">
        <div>
            <h1>Account Profile</h1>
            <p>Review your personal, contact, and security information.</p>
        </div>
    </div>

    <div class="account-profile-grid">
        <div class="profile-column">
            <section class="surface-card">
                <div class="card-heading">
                    <div>
                        <h2>Personal Information</h2>
                        <p>Your registered account identity.</p>
                    </div>
                </div>
                <div class="profile-avatar">PP</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="first-name">First Name</label>
                        <input id="first-name" type="text" value="Pawani" readonly>
                    </div>
                    <div class="form-field">
                        <label for="last-name">Last Name</label>
                        <input id="last-name" type="text" value="Perera" readonly>
                    </div>
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
                    <button type="button" class="secondary-btn">Change Password</button>
                    <button type="button" class="danger-outline-btn">Delete Account</button>
                </div>
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
                <input id="profile-email" type="email" value="pawani@example.com" readonly>
            </div>
            <div class="form-field">
                <label for="profile-phone">Phone</label>
                <input id="profile-phone" type="tel" value="071 234 5678" readonly>
            </div>
            <div class="form-field">
                <label for="profile-address">Address</label>
                <textarea id="profile-address" rows="3" readonly>No. 45, Galle Road, Colombo 06</textarea>
            </div>
            <p class="locked-details-note">These details were provided during registration and cannot be edited here.</p>
        </section>
    </div>
</section>
