<?php

/* =========================================================
   CURRENT FILTER
========================================================= */

$currentFilter = strtoupper($_GET['status'] ?? 'PENDING');

$allowedFilters = [
    'PENDING',
    'VERIFIED',
    'REJECTED',
    'ALL'
];

if (!in_array($currentFilter, $allowedFilters, true)) {
    $currentFilter = 'PENDING';
}


/* =========================================================
   CONTROLLER / DATABASE DATA

   Controller එකෙන් $recyclerProfiles හෝ $recyclers
   ලැබුණොත් ඒ data use වෙනවා.
========================================================= */

$allRecyclerProfiles = $recyclerProfiles ?? $recyclers ?? [];


/* =========================================================
   TEMPORARY DEMO DATA

   Database/controller data නැත්නම් විතරක් මේ sample data
   page එකේ පෙන්වනවා.

   Later backend connect කළාම මේ block එක remove කරන්න පුළුවන්.
========================================================= */

if (empty($allRecyclerProfiles)) {

    $allRecyclerProfiles = [

        [
            'recycler_id' => 1,
            'company_name' => 'GreenCycle Lanka Pvt Ltd',
            'contact_person' => 'Anjana Silva',
            'email' => 'anjana@greencycle.lk',
            'phone' => '077 234 5678',
            'district' => 'Colombo',
            'license_no' => 'CEA-RC-2026-001',
            'license_expiry' => '2027-06-30',
            'submitted_at' => '2026-07-02 09:30:00',
            'status' => 'VERIFIED'
        ],

        [
            'recycler_id' => 2,
            'company_name' => 'E-Waste Recovery Colombo',
            'contact_person' => 'Nimal Perera',
            'email' => 'nimal@ewasterecovery.lk',
            'phone' => '071 456 7890',
            'district' => 'Colombo',
            'license_no' => 'CEA-RC-2026-002',
            'license_expiry' => '2027-04-18',
            'submitted_at' => '2026-07-03 11:15:00',
            'status' => 'VERIFIED'
        ],

        [
            'recycler_id' => 3,
            'company_name' => 'SafeDispose Electronics',
            'contact_person' => 'Kavindi Fernando',
            'email' => 'kavindi@safedispose.lk',
            'phone' => '076 876 5432',
            'district' => 'Gampaha',
            'license_no' => 'CEA-RC-2026-003',
            'license_expiry' => '2027-03-22',
            'submitted_at' => '2026-07-04 14:20:00',
            'status' => 'VERIFIED'
        ],

        [
            'recycler_id' => 4,
            'company_name' => 'Eco Recyclers Pvt Ltd',
            'contact_person' => 'Tharindu Jayasinghe',
            'email' => 'tharindu@ecorecyclers.lk',
            'phone' => '075 345 9087',
            'district' => 'Kalutara',
            'license_no' => 'CEA-RC-2026-004',
            'license_expiry' => '2027-08-15',
            'submitted_at' => '2026-07-05 10:45:00',
            'status' => 'VERIFIED'
        ],

        [
            'recycler_id' => 5,
            'company_name' => 'Ceylon Tech Recyclers',
            'contact_person' => 'Isuru Bandara',
            'email' => 'isuru@ceylontechrecyclers.lk',
            'phone' => '078 112 3344',
            'district' => 'Kandy',
            'license_no' => 'CEA-RC-2026-005',
            'license_expiry' => '2027-02-10',
            'submitted_at' => '2026-07-09 08:55:00',
            'status' => 'PENDING'
        ],

        [
            'recycler_id' => 6,
            'company_name' => 'Urban Eco Metals',
            'contact_person' => 'Malith Gunawardena',
            'email' => 'malith@urbaneco.lk',
            'phone' => '070 998 7766',
            'district' => 'Galle',
            'license_no' => 'CEA-RC-2026-006',
            'license_expiry' => '2026-01-12',
            'submitted_at' => '2026-07-06 16:10:00',
            'status' => 'REJECTED'
        ]

    ];
}


/* =========================================================
   FILTER PROFILE LIST
========================================================= */

$filteredRecyclerProfiles = $allRecyclerProfiles;

if ($currentFilter !== 'ALL') {

    $filteredRecyclerProfiles = array_filter(
        $allRecyclerProfiles,
        function ($profile) use ($currentFilter) {

            $profileStatus = strtoupper(
                $profile['status'] ?? 'PENDING'
            );

            return $profileStatus === $currentFilter;
        }
    );
}


/* =========================================================
   FILTER COUNTS
========================================================= */

$pendingCount = 0;
$verifiedCount = 0;
$rejectedCount = 0;

foreach ($allRecyclerProfiles as $profile) {

    $profileStatus = strtoupper(
        $profile['status'] ?? 'PENDING'
    );

    if ($profileStatus === 'VERIFIED') {
        $verifiedCount++;
    } elseif ($profileStatus === 'REJECTED') {
        $rejectedCount++;
    } else {
        $pendingCount++;
    }
}

$totalCount = count($allRecyclerProfiles);

?>


<section class="recycler-verification-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="verification-header">

        <div>

            <h1>
                Recycler Verification
            </h1>

            <p>
                Review recycler registrations, licences, and verification status.
            </p>

        </div>

    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden></p>



    <!-- =====================================================
         MAIN VERIFICATION CARD
    ====================================================== -->

    <section class="verification-card">


        <!-- =================================================
             FILTER TABS
        ================================================== -->

        <div class="verification-filters">

            <a
                href="/EcoLot-LK/public/admin/recycler-verification?status=PENDING"
                class="filter-tab <?= $currentFilter === 'PENDING' ? 'active' : '' ?>"
            >
                Pending (<?= $pendingCount ?>)
            </a>


            <a
                href="/EcoLot-LK/public/admin/recycler-verification?status=VERIFIED"
                class="filter-tab <?= $currentFilter === 'VERIFIED' ? 'active' : '' ?>"
            >
                Verified (<?= $verifiedCount ?>)
            </a>


            <a
                href="/EcoLot-LK/public/admin/recycler-verification?status=REJECTED"
                class="filter-tab <?= $currentFilter === 'REJECTED' ? 'active' : '' ?>"
            >
                Rejected (<?= $rejectedCount ?>)
            </a>


            <a
                href="/EcoLot-LK/public/admin/recycler-verification?status=ALL"
                class="filter-tab <?= $currentFilter === 'ALL' ? 'active' : '' ?>"
            >
                All (<?= $totalCount ?>)
            </a>

        </div>


        <div class="current-filter-text">

            Current filter:

            <span>
                <?= htmlspecialchars(
                    ucwords(
                        strtolower($currentFilter)
                    )
                ) ?>
            </span>

        </div>



        <!-- =================================================
             RECYCLER VERIFICATION TABLE
        ================================================== -->

        <?php if (!empty($filteredRecyclerProfiles)): ?>


            <div class="report-table-wrapper recycler-verification-table-wrapper">


                <table class="report-table recycler-verification-table">


                    <thead>

                        <tr>
                            <th scope="col">Company</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Licence</th>
                            <th scope="col">District</th>
                            <th scope="col">Submitted At</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>

                    </thead>


                    <tbody>


                <?php foreach ($filteredRecyclerProfiles as $profile): ?>


                    <?php

                    $recyclerId =
                        $profile['recycler_id']
                        ?? $profile['id']
                        ?? '';


                    $companyName =
                        $profile['company_name']
                        ?? $profile['organization_name']
                        ?? 'Unknown Company';


                    $contactPerson =
                        $profile['contact_person']
                        ?? $profile['owner_name']
                        ?? $profile['full_name']
                        ?? 'Not Provided';


                    $email =
                        $profile['email']
                        ?? 'Not Provided';


                    $phone =
                        $profile['phone']
                        ?? $profile['contact_number']
                        ?? 'Not Provided';


                    $district =
                        $profile['district']
                        ?? 'Not Provided';


                    $licenseNo =
                        $profile['license_no']
                        ?? $profile['license_number']
                        ?? 'Not Provided';


                    $licenseExpiry =
                        $profile['license_expiry']
                        ?? $profile['license_expiry_date']
                        ?? 'Not Provided';


                    $submittedAt =
                        $profile['submitted_at']
                        ?? $profile['created_at']
                        ?? 'Not Available';


                    $status = strtoupper(
                        $profile['status']
                        ?? 'PENDING'
                    );


                    if ($status === 'VERIFIED') {

                        $statusClass = 'status-verified';

                    } elseif ($status === 'REJECTED') {

                        $statusClass = 'status-rejected';

                    } else {

                        $statusClass = 'status-pending';

                    }


                    $statusLabel = ucwords(
                        strtolower(
                            str_replace('_', ' ', $status)
                        )
                    );

                    ?>


                    <tr>

                        <td>
                            <strong class="table-primary-text">
                                <?= htmlspecialchars($companyName) ?>
                            </strong>
                            <span class="table-secondary-text">
                                Recycler ID: #<?= htmlspecialchars((string)$recyclerId) ?>
                            </span>
                        </td>

                        <td>
                            <span class="table-contact-name">
                                <?= htmlspecialchars($contactPerson) ?>
                            </span>
                            <span class="table-secondary-text">
                                <?= htmlspecialchars($email) ?>
                            </span>
                            <span class="table-secondary-text">
                                <?= htmlspecialchars($phone) ?>
                            </span>
                        </td>

                        <td>
                            <span class="table-primary-text">
                                <?= htmlspecialchars($licenseNo) ?>
                            </span>
                            <span class="table-secondary-text">
                                Expires: <?= htmlspecialchars($licenseExpiry) ?>
                            </span>
                        </td>

                        <td><?= htmlspecialchars($district) ?></td>

                        <td><?= htmlspecialchars($submittedAt) ?></td>

                        <td>
                            <span class="status-badge <?= $statusClass ?>">
                                <?= htmlspecialchars($statusLabel) ?>
                            </span>
                        </td>

                        <td>
                            <div class="table-actions">

                            <a
                                class="action-btn secondary-btn-style"
                                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/recycler-verification/<?= rawurlencode((string) $recyclerId) ?>"
                            >
                                View Details
                            </a>


                            <?php if ($status === 'PENDING'): ?>

                                <button
                                    type="button"
                                    class="action-btn approve-btn"
                                    data-admin-dialog="approve-recycler"
                                    data-name="<?= htmlspecialchars($companyName) ?>"
                                >
                                    Approve
                                </button>


                                <button
                                    type="button"
                                    class="action-btn reject-btn"
                                    data-admin-dialog="reject-recycler"
                                    data-name="<?= htmlspecialchars($companyName) ?>"
                                >
                                    Reject
                                </button>

                            <?php endif; ?>


                            <?php if ($status === 'REJECTED'): ?>

                                <button
                                    type="button"
                                    class="action-btn approve-btn"
                                    data-admin-dialog="reconsider-recycler"
                                    data-name="<?= htmlspecialchars($companyName) ?>"
                                >
                                    Reconsider
                                </button>

                            <?php endif; ?>

                            </div>
                        </td>

                    </tr>


                <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <div class="empty-state-card">

                No recycler profiles found under the

                <strong>
                    <?= htmlspecialchars(
                        ucwords(
                            strtolower($currentFilter)
                        )
                    ) ?>
                </strong>

                filter.

            </div>


        <?php endif; ?>


    </section>


</section>
