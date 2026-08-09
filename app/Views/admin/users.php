<section class="users-page">

    <div class="users-title-row">
        <div>
            <h2>User Management</h2>
            <p>Create staff accounts and review current system access.</p>
        </div>
        <button class="create-user-btn" type="button" data-admin-dialog="create-staff">Create Staff</button>
    </div>
    
    <div class="users-card users-hero">

        <nav class="filter-tabs" aria-label="Filter users">
            <button class="filter-tab active" type="button" aria-pressed="true">All</button>
            <button class="filter-tab" type="button" aria-pressed="false">Administrators</button>
            <button class="filter-tab" type="button" aria-pressed="false">Officers</button>
            <button class="filter-tab" type="button" aria-pressed="false">Collectors</button>
        </nav>

        <p class="current-filter">Current filter: ALL</p>

        <p class="staff-guidance">Public users and recyclers self-register. Recycler access is reviewed separately under Recycler Verification.</p>
    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>

    <div class="users-card users-list-card">
        <h3>Users</h3>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th scope="col">User ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col">Created At</th>
                        <th scope="col">Update Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td data-label="User ID">#4</td>
                        <td data-label="Name">Municipal Officer</td>
                        <td data-label="Email">officer@ecolot.lk</td>
                        <td data-label="Phone">0771111111</td>
                        <td data-label="Role">MUNICIPAL_OFFICER</td>
                        <td data-label="Status"><span class="user-status">ACTIVE</span></td>
                        <td data-label="Created At"><time datetime="2026-07-09 13:23:02">2026-07-09 13:23:02</time></td>
                        <td data-label="Update Status">
                            <div class="status-form">
                                <select aria-label="Status for Municipal Officer">
                                    <option selected>ACTIVE</option>
                                    <option>INACTIVE</option>
                                </select>
                                <button type="button" data-admin-dialog="account-status" data-action="Deactivate" data-name="Municipal Officer">Update</button>
                            </div>
                        </td>
                        <td data-label="Actions"><button class="table-action-btn" type="button" data-admin-dialog="edit-staff" data-name="Municipal Officer" data-email="officer@ecolot.lk">View / Edit</button></td>
                    </tr>
                    <tr>
                        <td data-label="User ID">#1</td>
                        <td data-label="Name">System Admin</td>
                        <td data-label="Email">admin@ecolot.lk</td>
                        <td data-label="Phone">0770000000</td>
                        <td data-label="Role">ADMIN</td>
                        <td data-label="Status"><span class="user-status">ACTIVE</span></td>
                        <td data-label="Created At"><time datetime="2026-07-09 12:33:59">2026-07-09 12:33:59</time></td>
                        <td data-label="Update Status"><span class="current-admin">Current admin</span></td>
                        <td data-label="Actions"><button class="table-action-btn" type="button" data-admin-dialog="edit-staff" data-name="System Admin" data-email="admin@ecolot.lk">View / Edit</button></td>
                    </tr>
                    <tr>
                        <td data-label="User ID">#2</td>
                        <td data-label="Name">Collector One</td>
                        <td data-label="Email">collector@ecolot.lk</td>
                        <td data-label="Phone">0772222222</td>
                        <td data-label="Role">COLLECTOR</td>
                        <td data-label="Status"><span class="user-status">ACTIVE</span></td>
                        <td data-label="Created At"><time datetime="2026-07-09 12:33:59">2026-07-09 12:33:59</time></td>
                        <td data-label="Update Status">
                            <div class="status-form">
                                <select aria-label="Status for Collector One">
                                    <option selected>ACTIVE</option>
                                    <option>INACTIVE</option>
                                </select>
                                <button type="button" data-admin-dialog="account-status" data-action="Deactivate" data-name="Collector One">Update</button>
                            </div>
                        </td>
                        <td data-label="Actions"><button class="table-action-btn" type="button" data-admin-dialog="edit-staff" data-name="Collector One" data-email="collector@ecolot.lk">View / Edit</button></td>
                    </tr>
                    <tr>
                        <td data-label="User ID">#3</td>
                        <td data-label="Name">Demo Recycler</td>
                        <td data-label="Email">recycler@ecolot.lk</td>
                        <td data-label="Phone">0773333333</td>
                        <td data-label="Role">AUTHORIZED_RECYCLER</td>
                        <td data-label="Status"><span class="user-status">ACTIVE</span></td>
                        <td data-label="Created At"><time datetime="2026-07-09 12:33:59">2026-07-09 12:33:59</time></td>
                        <td data-label="Update Status">
                            <div class="status-form">
                                <select aria-label="Status for Demo Recycler">
                                    <option selected>ACTIVE</option>
                                    <option>INACTIVE</option>
                                </select>
                                <button type="button" data-demo-message="Recycler accounts are managed through self-registration and Recycler Verification.">Update</button>
                            </div>
                        </td>
                        <td data-label="Actions"><a class="table-action-btn" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/3">View Recycler</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
