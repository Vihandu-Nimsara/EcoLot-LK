<?php

/* =========================================================
   CURRENT FILTER
========================================================= */

$currentFilter = strtoupper($_GET['status'] ?? 'ALL');

$allowedFilters = [
    'PENDING',
    'VERIFIED',
    'REJECTED',
    'ALL'
];

if (!in_array($currentFilter, $allowedFilters, true)) {
    $currentFilter = 'ALL';
}


/* =========================================================
   CONTROLLER / DATABASE DATA

   Controller එකෙන් $recyclerProfiles හෝ $recyclers
   ලැබුණොත් ඒ data use වෙනවා.
========================================================= */

$allRecyclerProfiles = $recyclerProfiles ?? $recyclers ?? [];


/* =========================================================
   FILTER PROFILE LIST
========================================================= */

$filteredRecyclerProfiles = $allRecyclerProfiles;


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
                Review recycler registrations, licenses, and verification status.
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

        <form class="light-filter" data-client-filter data-rows="[data-recycler-row]" data-empty="[data-recycler-filter-empty]" data-result="[data-recycler-filter-result]">
            <div class="quick-filters" role="group" aria-label="Filter recyclers by verification status"><button class="quick-filter" type="button" aria-pressed="<?= $currentFilter === 'ALL' ? 'true' : 'false' ?>" data-filter-name="status" data-filter-value="">All (<?= $totalCount ?>)</button><button class="quick-filter" type="button" aria-pressed="<?= $currentFilter === 'PENDING' ? 'true' : 'false' ?>" data-filter-name="status" data-filter-value="PENDING">Pending (<?= $pendingCount ?>)</button><button class="quick-filter" type="button" aria-pressed="<?= $currentFilter === 'VERIFIED' ? 'true' : 'false' ?>" data-filter-name="status" data-filter-value="VERIFIED">Verified (<?= $verifiedCount ?>)</button><button class="quick-filter" type="button" aria-pressed="<?= $currentFilter === 'REJECTED' ? 'true' : 'false' ?>" data-filter-name="status" data-filter-value="REJECTED">Rejected (<?= $rejectedCount ?>)</button></div>
            <div class="light-filter-controls"><div class="filter-field"><label for="recycler-search">Search recyclers</label><input id="recycler-search" name="search" type="search" placeholder="Company, contact or SWML number"></div></div>
        </form>
        <p class="filter-result" data-recycler-filter-result role="status" aria-live="polite"></p>



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
                            <th scope="col">license</th>
                            <th scope="col">District</th>
                            <th scope="col">Submitted</th>
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
                    if ($status === 'PENDING') {
                        $statusLabel = 'Pending Verification';
                    }

                    ?>


                    <?php $licenseFilter = $licenseExpiry < '2026-08-09' ? 'EXPIRED' : ($status === 'PENDING' ? 'PENDING' : 'VALID'); ?>
                    <tr data-recycler-row data-search="<?= htmlspecialchars($companyName.' '.$contactPerson.' '.$licenseNo) ?>" data-status="<?= htmlspecialchars($status) ?>" data-license="<?= $licenseFilter ?>">

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

                            </div>
                        </td>

                    </tr>


                <?php endforeach; ?>


                    </tbody>


                </table>


            </div>
            <div class="filtered-empty-state" data-recycler-filter-empty hidden>No recyclers match the current search and verification filters.</div>


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
