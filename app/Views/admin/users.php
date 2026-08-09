<?php
$staff = [
    ['id' => 4, 'name' => 'Municipal Officer', 'email' => 'officer@ecolot.lk', 'phone' => '0771111111', 'role' => 'MUNICIPAL_OFFICER', 'role_label' => 'Municipal Officer', 'status' => 'ACTIVE', 'created' => '2026-07-09 13:23:02'],
    ['id' => 1, 'name' => 'System Admin', 'email' => 'admin@ecolot.lk', 'phone' => '0770000000', 'role' => 'ADMIN', 'role_label' => 'Administrator', 'status' => 'ACTIVE', 'created' => '2026-07-09 12:33:59', 'current' => true],
    ['id' => 2, 'name' => 'Collector One', 'email' => 'collector@ecolot.lk', 'phone' => '0772222222', 'role' => 'COLLECTOR', 'role_label' => 'Collector', 'status' => 'INACTIVE', 'created' => '2026-07-09 12:33:59'],
];
?>
<section class="users-page">
    <div class="users-title-row">
        <div>
            <h2>Staff Management</h2>
            <p>Create staff accounts and review current system access.</p>
        </div>
            <button 
            class="create-user-btn" 
            type="button" 
            data-admin-dialog="create-staff">
            Create Staff
        </button>
        </div>
        <form class="light-filter" 
        data-client-filter data-rows="[data-staff-row]" 
        data-empty="[data-staff-filter-empty]" 
        data-result="[data-staff-filter-result]">
            <div class="quick-filters" 
            role="group" 
            aria-label="Filter staff by role"><button class="quick-filter" type="button" aria-pressed="true" data-filter-name="role" data-filter-value="">All</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="MUNICIPAL_OFFICER">Municipal Officers</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="COLLECTOR">Collectors</button><button class="quick-filter" type="button" aria-pressed="false" data-filter-name="role" data-filter-value="ADMIN">Administrators</button></div>
        <div class="light-filter-controls"><div class="filter-field"><label for="staff-search">Search staff</label><input id="staff-search" name="search" type="search" placeholder="Name, email or staff ID"></div></div>
    </form>
    <p class="filter-result" data-staff-filter-result role="status" aria-live="polite"></p>
    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>
    <div class="users-card users-list-card"><h3>Staff</h3><div class="users-table-wrap"><table class="users-table"><thead><tr><th>User ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Created At</th><th>Update Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($staff as $person): ?>
        <tr data-staff-row data-search="#<?= $person['id'] ?> <?= htmlspecialchars($person['name']) ?> <?= htmlspecialchars($person['email']) ?>" data-role="<?= $person['role'] ?>" data-status="<?= $person['status'] ?>">
            <td data-label="User ID">#<?= $person['id'] ?></td><td data-label="Name"><?= htmlspecialchars($person['name']) ?></td><td data-label="Email"><?= htmlspecialchars($person['email']) ?></td><td data-label="Phone"><?= htmlspecialchars($person['phone']) ?></td><td data-label="Role"><?= htmlspecialchars($person['role_label']) ?></td><td data-label="Status"><span class="user-status <?= $person['status'] === 'INACTIVE' ? 'status-inactive' : '' ?>"><?= htmlspecialchars($person['status']) ?></span></td><td data-label="Created At"><time><?= htmlspecialchars($person['created']) ?></time></td>
            <td data-label="Update Status"><?php if (!empty($person['current'])): ?><span class="current-admin">Current admin</span><?php else: ?><div class="status-form"><select aria-label="Account status for <?= htmlspecialchars($person['name']) ?>"><option value="ACTIVE"<?= $person['status'] === 'ACTIVE' ? ' selected' : '' ?>>ACTIVE</option><option value="INACTIVE"<?= $person['status'] === 'INACTIVE' ? ' selected' : '' ?>>INACTIVE</option></select><button type="button" data-status-dialog data-admin-dialog="account-status" data-name="<?= htmlspecialchars($person['name']) ?>">Update</button></div><?php endif; ?></td>
            <td data-label="Actions"><button class="table-action-btn" type="button" data-admin-dialog="edit-staff" data-name="<?= htmlspecialchars($person['name']) ?>" data-email="<?= htmlspecialchars($person['email']) ?>" data-phone="<?= htmlspecialchars($person['phone']) ?>" data-role="<?= htmlspecialchars($person['role_label']) ?>">View / Edit</button></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div><div class="filtered-empty-state" data-staff-filter-empty hidden>No staff match the current search, role and account status.</div></div>
</section>
