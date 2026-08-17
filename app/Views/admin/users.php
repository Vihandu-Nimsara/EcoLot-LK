<?php
$users = [
    [
        'id' => 1,
        'name' => 'System Admin',
        'email' => 'admin@ecolot.lk',
        'phone' => '0770000000',
        'role' => 'ADMIN',
        'role_label' => 'Administrator',
        'identifier' => 'System provisioned',
        'status' => 'ACTIVE',
        'kind' => 'administrator',
        'protected' => true,
    ],
    [
        'id' => 2,
        'name' => 'Collector One',
        'email' => 'collector@ecolot.lk',
        'phone' => '0772222222',
        'role' => 'COLLECTOR',
        'role_label' => 'Collector',
        'identifier' => 'Collector ID: COL-002',
        'status' => 'DISABLED',
        'kind' => 'staff',
    ],
    [
        'id' => 3,
        'name' => 'GreenCycle Lanka Pvt Ltd',
        'email' => 'anjana@greencycle.lk',
        'phone' => '077 234 5678',
        'role' => 'RECYCLER',
        'role_label' => 'Authorized Recycler',
        'identifier' => 'SWML/2026/001',
        'status' => 'ACTIVE',
        'kind' => 'recycler',
        'recycler_id' => 1,
    ],
    [
        'id' => 4,
        'name' => 'Municipal Officer',
        'email' => 'officer@ecolot.lk',
        'phone' => '0771111111',
        'role' => 'MUNICIPAL_OFFICER',
        'role_label' => 'Municipal Officer',
        'identifier' => 'Officer ID: MO-004',
        'status' => 'ACTIVE',
        'kind' => 'staff',
    ],
    [
        'id' => 5,
        'name' => 'Public User',
        'email' => 'public@ecolot.lk',
        'phone' => '0774444444',
        'role' => 'PUBLIC_USER',
        'role_label' => 'Public User',
        'identifier' => 'Resident account',
        'status' => 'ACTIVE',
        'kind' => 'public',
    ],
];
?>
<section class="users-page">
    <div class="users-title-row">
        <div>
            <h1>System Users</h1>
            <p>Review system access and manage staff accounts across EcoLot LK.</p>
        </div>
        <button class="create-user-btn" type="button" data-admin-dialog="create-staff">Create Staff</button>
    </div>

    <p class="filter-result" data-user-filter-result role="status" aria-live="polite"></p>
    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>
    <section class="users-card users-list-card">
        <div class="users-list-heading">
            <h2>Users</h2>
        </div>

        <form class="light-filter" data-client-filter data-rows="[data-user-row]" data-empty="[data-user-filter-empty]" data-result="[data-user-filter-result]">
        <div class="quick-filters" role="group" aria-label="Filter users by role">
            <button class="quick-filter" type="button" aria-pressed="true" data-filter-name="role" data-filter-value="">All</button>
            <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="PUBLIC_USER">Public Users</button>
            <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="MUNICIPAL_OFFICER">Municipal Officers</button>
            <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="COLLECTOR">Collectors</button>
            <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="RECYCLER">Recyclers</button>
            <button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="ADMIN">Administrators</button>
        </div>
        <div class="light-filter-controls">
            <div class="filter-field">
                <label for="user-search">Search users</label>
                <input id="user-search" name="search" type="search" placeholder="Name, email or identifier">
            </div>
        </div>
    </form>

        <?php if ($users === []): ?>
            <div class="empty-state">No system users are available.</div>
        <?php else: ?>
            <div class="users-table-wrap">
                <table class="users-table">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Contact</th><th>Identifier</th><th>Account Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr
                            data-user-row
                            data-search="#<?= $user['id'] ?> <?= htmlspecialchars($user['name'].' '.$user['email'].' '.$user['identifier']) ?>"
                            data-role="<?= $user['role'] ?>"
                        >
        <td data-label="User"><strong class="table-primary-text"><?= htmlspecialchars($user['name']) ?></strong><span class="table-secondary-text">User #<?= $user['id'] ?></span></td>
        <td data-label="Role"><?= htmlspecialchars($user['role_label']) ?></td>
        <td data-label="Contact"><span class="table-primary-text"><?= htmlspecialchars($user['email']) ?></span><span class="table-secondary-text"><?= htmlspecialchars($user['phone']) ?></span></td>
        <td data-label="Identifier"><?= htmlspecialchars($user['identifier']) ?></td>
        <td data-label="Account Status"><span class="user-status <?= $user['status']==='DISABLED'?'status-inactive':'' ?>"><?= ucfirst(strtolower($user['status'])) ?></span></td>
                            <td data-label="Actions">
                                <div class="row-actions">
                                    <?php if ($user['kind']==='recycler'): ?>
                                        <a class="table-action-btn" href="<?= htmlspecialchars($basePath) ?>/admin/recycler-verification/<?= $user['recycler_id'] ?>">View Recycler</a>
                                    <?php elseif (!empty($user['protected'])): ?>
                                        <button class="table-action-btn" type="button" data-admin-dialog="view-user" data-name="<?= htmlspecialchars($user['name']) ?>" data-email="<?= htmlspecialchars($user['email']) ?>" data-phone="<?= htmlspecialchars($user['phone']) ?>" data-role="<?= htmlspecialchars($user['role_label']) ?>" data-identifier="<?= htmlspecialchars($user['identifier']) ?>">View Account</button>
                                    <?php elseif ($user['kind']==='staff'): ?>
                                        <button class="table-action-btn" type="button" data-admin-dialog="edit-staff" data-name="<?= htmlspecialchars($user['name']) ?>" data-email="<?= htmlspecialchars($user['email']) ?>" data-phone="<?= htmlspecialchars($user['phone']) ?>" data-role="<?= htmlspecialchars($user['role_label']) ?>">View / Edit</button>
                                    <?php else: ?>
                                        <button class="table-action-btn" type="button" data-admin-dialog="view-user" data-name="<?= htmlspecialchars($user['name']) ?>" data-email="<?= htmlspecialchars($user['email']) ?>" data-phone="<?= htmlspecialchars($user['phone']) ?>" data-role="<?= htmlspecialchars($user['role_label']) ?>" data-identifier="<?= htmlspecialchars($user['identifier']) ?>">View Account</button>
                                    <?php endif; ?>

                                    <?php if (empty($user['protected'])): ?>
                                        <div class="status-form">
                                            <select aria-label="Account status for <?= htmlspecialchars($user['name']) ?>"><option value="PENDING"<?= $user['status']==='PENDING'?' selected':'' ?>>Pending</option><option value="ACTIVE"<?= $user['status']==='ACTIVE'?' selected':'' ?>>Active</option><option value="SUSPENDED"<?= $user['status']==='SUSPENDED'?' selected':'' ?>>Suspended</option><option value="DISABLED"<?= $user['status']==='DISABLED'?' selected':'' ?>>Disabled</option></select>
                                            <button type="button" data-status-dialog data-admin-dialog="account-status" data-name="<?= htmlspecialchars($user['name']) ?>">Update</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="filtered-empty-state" data-user-filter-empty hidden>No users match the selected role and search.</div>
        <?php endif; ?>
    </section>
</section>
