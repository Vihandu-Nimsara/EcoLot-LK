<?php

/* =========================================================
   DEMO REPORT DATA
   Later these values can come from the controller/database.
========================================================= */


/* =========================
   System Overview
========================= */

$totalUsers = 18;
$totalRequests = 10;
$completedRequests = 8;
$pickupRecords = 9;
$verifiedPickups = 9;

$totalELots = 10;
$totalBids = 12;
$winningBids = 6;


/* =========================
   Request Status Summary
========================= */

$requestStatusSummary = [

    [
        'status' => 'COMPLETED',
        'total_requests' => 8
    ],

    [
        'status' => 'COLLECTED',
        'total_requests' => 1
    ],

    [
        'status' => 'ASSIGNED',
        'total_requests' => 1
    ]

];


/* =========================
   Monthly Request Trend
========================= */

$monthlyRequestTrend = [

    [
        'month' => '2026-07',
        'total_requests' => 10
    ]

];


/* =========================
   Verified Collected E-Waste
========================= */

$verifiedCollectedByCategory = [

    [
        'category' => 'DEMO-FIX Recycler Electronics',
        'pickup_item_records' => 4,
        'total_quantity' => 98,
        'total_weight' => 248.00
    ],

    [
        'category' => 'Demo Consumer Electronics',
        'pickup_item_records' => 4,
        'total_quantity' => 87,
        'total_weight' => 243.00
    ],

    [
        'category' => 'DEMO-FIX Recycler Batteries',
        'pickup_item_records' => 1,
        'total_quantity' => 18,
        'total_weight' => 36.00
    ],

    [
        'category' => 'Demo Battery and Circuit Boards',
        'pickup_item_records' => 1,
        'total_quantity' => 18,
        'total_weight' => 87.00
    ]

];


/* =========================
   E-Lot Status Summary
========================= */

$eLotStatusSummary = [

    [
        'status' => 'OPEN_FOR_BIDDING',
        'total' => 4
    ],

    [
        'status' => 'AWARDED',
        'total' => 2
    ],

    [
        'status' => 'PROCESSING',
        'total' => 2
    ],

    [
        'status' => 'COMPLETED',
        'total' => 2
    ]

];


/* =========================
   Recent E-Lot Bid Summary
========================= */

$recentELotBidSummary = [

    [
        'elot_code' => 'DEMO-FIX-LOT-COMPLETED-001',
        'title' => 'DEMO-FIX Completed Lot - Routers',
        'category' => 'DEMO-FIX Recycler Electronics',
        'status' => 'COMPLETED',
        'bids' => 1,
        'highest_bid' => 29500.00,
        'average_bid' => 29500.00
    ],

    [
        'elot_code' => 'DEMO-FIX-LOT-OPEN-001',
        'title' => 'DEMO-FIX Open Lot - Laptops and Monitors',
        'category' => 'DEMO-FIX Recycler Electronics',
        'status' => 'OPEN_FOR_BIDDING',
        'bids' => 1,
        'highest_bid' => 94500.00,
        'average_bid' => 94500.00
    ],

    [
        'elot_code' => 'DEMO-FIX-LOT-OPEN-002',
        'title' => 'DEMO-FIX Open Lot - Mobile Phones and Routers',
        'category' => 'DEMO-FIX Recycler Electronics',
        'status' => 'OPEN_FOR_BIDDING',
        'bids' => 1,
        'highest_bid' => 38200.00,
        'average_bid' => 38200.00
    ],

    [
        'elot_code' => 'DEMO-FIX-LOT-AWARDED-001',
        'title' => 'DEMO-FIX Awarded Lot - Printers and Circuit Boards',
        'category' => 'DEMO-FIX Recycler Electronics',
        'status' => 'AWARDED',
        'bids' => 1,
        'highest_bid' => 132000.00,
        'average_bid' => 132000.00
    ],

    [
        'elot_code' => 'DEMO-FIX-LOT-PROCESSING-001',
        'title' => 'DEMO-FIX Processing Lot - Lithium Batteries',
        'category' => 'DEMO-FIX Recycler Batteries',
        'status' => 'PROCESSING',
        'bids' => 1,
        'highest_bid' => 88000.00,
        'average_bid' => 88000.00
    ],

    [
        'elot_code' => 'DEMO-LOT-005',
        'title' => 'Demo Completed Lot - Keyboards and Routers',
        'category' => 'Demo Consumer Electronics',
        'status' => 'COMPLETED',
        'bids' => 1,
        'highest_bid' => 36500.00,
        'average_bid' => 36500.00
    ],

    [
        'elot_code' => 'DEMO-LOT-001',
        'title' => 'Demo Open Lot - Laptops from Nugegoda',
        'category' => 'Demo Consumer Electronics',
        'status' => 'OPEN_FOR_BIDDING',
        'bids' => 2,
        'highest_bid' => 97250.00,
        'average_bid' => 83875.00
    ],

    [
        'elot_code' => 'DEMO-LOT-002',
        'title' => 'Demo Open Lot - Phones and Routers',
        'category' => 'Demo Consumer Electronics',
        'status' => 'OPEN_FOR_BIDDING',
        'bids' => 1,
        'highest_bid' => 48200.00,
        'average_bid' => 48200.00
    ],

    [
        'elot_code' => 'DEMO-LOT-003',
        'title' => 'Demo Awarded Lot - Printers and Monitors',
        'category' => 'Demo Consumer Electronics',
        'status' => 'AWARDED',
        'bids' => 2,
        'highest_bid' => 128000.00,
        'average_bid' => 122250.00
    ],

    [
        'elot_code' => 'DEMO-LOT-004',
        'title' => 'Demo Processing Lot - Lithium Batteries',
        'category' => 'Demo Battery and Circuit Boards',
        'status' => 'PROCESSING',
        'bids' => 1,
        'highest_bid' => 99000.00,
        'average_bid' => 99000.00
    ]

];


/* =========================
   Recycler Verification
========================= */

$recyclerVerificationSummary = [

    [
        'status' => 'VERIFIED',
        'total_recyclers' => 4
    ],

    [
        'status' => 'PENDING',
        'total_recyclers' => 1
    ],

    [
        'status' => 'REJECTED',
        'total_recyclers' => 1
    ]

];


/* =========================
   Top Recyclers
========================= */

$topRecyclersByBids = [

    [
        'company' => 'GreenCycle Lanka Pvt Ltd',
        'verification' => 'VERIFIED',
        'total_bids' => 8,
        'winning_bids' => 5,
        'total_bid_amount' => 655400.00
    ],

    [
        'company' => 'E-Waste Recovery Colombo',
        'verification' => 'VERIFIED',
        'total_bids' => 3,
        'winning_bids' => 1,
        'total_bid_amount' => 268500.00
    ],

    [
        'company' => 'SafeDispose Electronics',
        'verification' => 'VERIFIED',
        'total_bids' => 1,
        'winning_bids' => 0,
        'total_bid_amount' => 67250.00
    ],

    [
        'company' => 'Eco Recyclers Pvt Ltd',
        'verification' => 'VERIFIED',
        'total_bids' => 0,
        'winning_bids' => 0,
        'total_bid_amount' => 0.00
    ],

    [
        'company' => 'Ceylon Tech Recyclers',
        'verification' => 'PENDING',
        'total_bids' => 0,
        'winning_bids' => 0,
        'total_bid_amount' => 0.00
    ],

    [
        'company' => 'Urban Eco Metals',
        'verification' => 'REJECTED',
        'total_bids' => 0,
        'winning_bids' => 0,
        'total_bid_amount' => 0.00
    ]

];


/* =========================
   Recent Audit Logs
========================= */

$recentAuditLogs = [

    [
        'log_id' => 3,
        'user' => 'Anjana Silva',
        'role' => 'AUTHORIZED_RECYCLER',
        'action' => 'DEMO_FIX_RECYCLER_DASHBOARD_SEED',
        'description' => 'DEMO-FIX inserted authorized recycler dashboard demo data for the EcoLot LK demonstration environment.',
        'created_at' => '2026-07-10 16:03:03'
    ],

    [
        'log_id' => 2,
        'user' => 'Demo Admin',
        'role' => 'ADMIN',
        'action' => 'DEMO_SEED_ADMIN_RECYCLER',
        'description' => 'DEMO-SEED inserted Admin and Authorized Recycler demo data.',
        'created_at' => '2026-07-10 15:37:55'
    ],

    [
        'log_id' => 1,
        'user' => 'System Admin',
        'role' => 'ADMIN',
        'action' => 'UPDATE_USER_STATUS',
        'description' => 'Updated User ID 6 status to ACTIVE.',
        'created_at' => '2026-07-08 14:08:23'
    ],

    [
        'log_id' => 4,
        'user' => 'Demo Admin',
        'role' => 'ADMIN',
        'action' => 'RECYCLER_VERIFICATION_DECISION',
        'description' => 'Recorded a frontend demo recycler verification decision.',
        'created_at' => '2026-07-11 09:15:00'
    ],

    [
        'log_id' => 5,
        'user' => 'Anjana Silva',
        'role' => 'AUTHORIZED_RECYCLER',
        'action' => 'BID_SUBMISSION',
        'description' => 'Submitted a frontend demo bid for DEMO-FIX-LOT-OPEN-001.',
        'created_at' => '2026-07-11 10:05:00'
    ],

    [
        'log_id' => 6,
        'user' => 'Demo Municipal Officer',
        'role' => 'MUNICIPAL_OFFICER',
        'action' => 'WINNING_BID_SELECTION',
        'description' => 'Selected a winning bid in the frontend demo workflow.',
        'created_at' => '2026-07-11 11:20:00'
    ]

];

?>


<section class="admin-reports-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="reports-page-header">

        <div class="reports-heading">

            <h1>
                Admin Reports & Analytics
            </h1>

            <p>
                Monitor system activity, collection outcomes, E-Lots, bids, and recycler performance.
            </p>

        </div>


        <div class="reports-header-actions">
            <button
                type="button"
                class="print-report-btn"
                onclick="window.print()"
            >
                Print Report
            </button>

        </div>

    </div>



    <!-- =====================================================
         SYSTEM OVERVIEW
    ====================================================== -->

    <section class="overview-section">

        <div class="section-title-block">

            <h2>
                System Overview
            </h2>

            <p>
                A quick summary of the current EcoLot LK system activity.
            </p>

        </div>


        <div class="overview-grid">

            <!-- Total Users -->
            <article class="overview-card">

                <div class="overview-icon overview-users" aria-hidden="true">
                    👥
                </div>

                <div class="overview-info">
                    <span>Total Users</span>
                    <strong><?= htmlspecialchars((string)$totalUsers) ?></strong>
                </div>

            </article>


            <!-- Total Requests -->
            <article class="overview-card">

                <div class="overview-icon overview-requests" aria-hidden="true">
                    📋
                </div>

                <div class="overview-info">
                    <span>Total Requests</span>
                    <strong><?= htmlspecialchars((string)$totalRequests) ?></strong>
                </div>

            </article>


            <!-- Completed Requests -->
            <article class="overview-card">

                <div class="overview-icon overview-completed" aria-hidden="true">
                    ✅
                </div>

                <div class="overview-info">
                    <span>Completed Requests</span>
                    <strong><?= htmlspecialchars((string)$completedRequests) ?></strong>
                </div>

            </article>


            <!-- Pickup Records -->
            <article class="overview-card">

                <div class="overview-icon overview-pickups" aria-hidden="true">
                    🚛
                </div>

                <div class="overview-info">
                    <span>Pickup Records</span>
                    <strong><?= htmlspecialchars((string)$pickupRecords) ?></strong>
                </div>

            </article>


            <!-- Verified Pickups -->
            <article class="overview-card">

                <div class="overview-icon overview-verified" aria-hidden="true">
                    🛡️
                </div>

                <div class="overview-info">
                    <span>Verified Pickups</span>
                    <strong><?= htmlspecialchars((string)$verifiedPickups) ?></strong>
                </div>

            </article>


            <!-- Total E-Lots -->
            <article class="overview-card">

                <div class="overview-icon overview-elots" aria-hidden="true">
                    📦
                </div>

                <div class="overview-info">
                    <span>Total E-Lots</span>
                    <strong><?= htmlspecialchars((string)$totalELots) ?></strong>
                </div>

            </article>


            <!-- Total Bids -->
            <article class="overview-card">

                <div class="overview-icon overview-bids" aria-hidden="true">
                    🏷️
                </div>

                <div class="overview-info">
                    <span>Total Bids</span>
                    <strong><?= htmlspecialchars((string)$totalBids) ?></strong>
                </div>

            </article>


            <!-- Winning Bids -->
            <article class="overview-card">

                <div class="overview-icon overview-winning" aria-hidden="true">
                    🏆
                </div>

                <div class="overview-info">
                    <span>Winning Bids</span>
                    <strong><?= htmlspecialchars((string)$winningBids) ?></strong>
                </div>

            </article>

        </div>

    </section>



    <!-- =====================================================
         REQUEST STATUS SUMMARY
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Request Status Summary
                </h2>

                <p>
                    Distribution of collection requests by current status.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table request-status-table">

                <thead>

                    <tr>
                        <th>Status</th>
                        <th>Total Requests</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($requestStatusSummary as $row): ?>

                        <?php
                        $statusLabel = ucwords(
                            strtolower(
                                str_replace(
                                    '_',
                                    ' ',
                                    $row['status']
                                )
                            )
                        );
                        ?>

                        <tr>

                            <td data-label="Status">

                                <span class="status-badge status-request">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>

                            </td>


                            <td data-label="Total Requests">
                                <?= htmlspecialchars((string)$row['total_requests']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         MONTHLY REQUEST TREND
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Monthly Request Trend
                </h2>

                <p>
                    Monthly collection request activity.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table monthly-table">

                <thead>

                    <tr>
                        <th>Month</th>
                        <th>Total Requests</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($monthlyRequestTrend as $row): ?>

                        <tr>

                            <td data-label="Month">
                                <?= htmlspecialchars($row['month']) ?>
                            </td>


                            <td data-label="Total Requests">
                                <?= htmlspecialchars((string)$row['total_requests']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         VERIFIED COLLECTED E-WASTE
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Verified Collected E-Waste by Category
                </h2>

                <p>
                    Verified pickup quantities and total weight grouped by category.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table collected-category-table">

                <thead>

                    <tr>
                        <th>Category</th>
                        <th>Pickup Item Records</th>
                        <th>Total Quantity</th>
                        <th>Total Weight</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($verifiedCollectedByCategory as $row): ?>

                        <tr>

                            <td data-label="Category">
                                <?= htmlspecialchars($row['category']) ?>
                            </td>


                            <td data-label="Pickup Item Records">
                                <?= htmlspecialchars((string)$row['pickup_item_records']) ?>
                            </td>


                            <td data-label="Total Quantity">
                                <?= htmlspecialchars((string)$row['total_quantity']) ?>
                            </td>


                            <td
                                data-label="Total Weight"
                                class="nowrap-cell"
                            >
                                <?= number_format((float)$row['total_weight'], 2) ?> kg
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         E-LOT STATUS SUMMARY
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    E-Lot Status Summary
                </h2>

                <p>
                    Current distribution of E-Lots across their lifecycle.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table elot-status-table">

                <thead>

                    <tr>
                        <th>E-Lot Status</th>
                        <th>Total</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($eLotStatusSummary as $row): ?>

                        <?php

                        $status = strtoupper($row['status']);

                        $label = ucwords(
                            strtolower(
                                str_replace('_', ' ', $status)
                            )
                        );


                        if ($status === 'AWARDED') {

                            $class = 'status-awarded';

                        } elseif ($status === 'PROCESSING') {

                            $class = 'status-processing';

                        } elseif ($status === 'COMPLETED') {

                            $class = 'status-completed';

                        } else {

                            $class = 'status-open';

                        }

                        ?>

                        <tr>

                            <td data-label="E-Lot Status">

                                <span class="status-badge <?= $class ?>">
                                    <?= htmlspecialchars($label) ?>
                                </span>

                            </td>


                            <td data-label="Total">
                                <?= htmlspecialchars((string)$row['total']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         RECENT E-LOT BID SUMMARY
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Recent E-Lot Bid Summary
                </h2>

                <p>
                    Recent E-Lots and bidding activity across the platform.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table recent-bids-table">

                <thead>

                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Bids</th>
                        <th>Highest Bid</th>
                        <th>Average Bid</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($recentELotBidSummary as $row): ?>

                        <?php

                        $status = strtoupper($row['status']);

                        $statusLabel = ucwords(
                            strtolower(
                                str_replace('_', ' ', $status)
                            )
                        );


                        if ($status === 'AWARDED') {

                            $statusClass = 'status-awarded';

                        } elseif ($status === 'PROCESSING') {

                            $statusClass = 'status-processing';

                        } elseif ($status === 'COMPLETED') {

                            $statusClass = 'status-completed';

                        } else {

                            $statusClass = 'status-open';

                        }

                        ?>


                        <tr>

                            <td data-label="E-Lot Code">
                                <?= htmlspecialchars($row['elot_code']) ?>
                            </td>


                            <td data-label="Title">
                                <?= htmlspecialchars($row['title']) ?>
                            </td>


                            <td data-label="Category">
                                <?= htmlspecialchars($row['category']) ?>
                            </td>


                            <td data-label="Status">

                                <span class="status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>

                            </td>


                            <td data-label="Bids">
                                <?= htmlspecialchars((string)$row['bids']) ?>
                            </td>


                            <td
                                data-label="Highest Bid"
                                class="nowrap-cell"
                            >
                                Rs. <?= number_format((float)$row['highest_bid'], 2) ?>
                            </td>


                            <td
                                data-label="Average Bid"
                                class="nowrap-cell"
                            >
                                Rs. <?= number_format((float)$row['average_bid'], 2) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         RECYCLER VERIFICATION SUMMARY
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Recycler Verification Summary
                </h2>

                <p>
                    Recycler registrations grouped by verification state.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table recycler-summary-table">

                <thead>

                    <tr>
                        <th>Verification Status</th>
                        <th>Total Recyclers</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($recyclerVerificationSummary as $row): ?>

                        <?php

                        $status = strtoupper($row['status']);

                        $label = ucfirst(strtolower($status));


                        if ($status === 'VERIFIED') {

                            $class = 'status-verified';

                        } elseif ($status === 'REJECTED') {

                            $class = 'status-rejected';

                        } else {

                            $class = 'status-pending';

                        }

                        ?>


                        <tr>

                            <td data-label="Verification Status">

                                <span class="status-badge <?= $class ?>">
                                    <?= htmlspecialchars($label) ?>
                                </span>

                            </td>


                            <td data-label="Total Recyclers">
                                <?= htmlspecialchars((string)$row['total_recyclers']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         TOP RECYCLERS
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Top Recyclers by Bids
                </h2>

                <p>
                    Recycler bidding performance across available E-Lots.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table top-recyclers-table">

                <thead>

                    <tr>
                        <th>Recycler Company</th>
                        <th>Verification</th>
                        <th>Total Bids</th>
                        <th>Winning Bids</th>
                        <th>Total Bid Amount</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($topRecyclersByBids as $row): ?>

                        <?php

                        $verification = strtoupper($row['verification']);

                        $verificationLabel = ucfirst(
                            strtolower($verification)
                        );


                        if ($verification === 'VERIFIED') {

                            $verificationClass = 'status-verified';

                        } elseif ($verification === 'REJECTED') {

                            $verificationClass = 'status-rejected';

                        } else {

                            $verificationClass = 'status-pending';

                        }

                        ?>


                        <tr>

                            <td data-label="Recycler Company">
                                <?= htmlspecialchars($row['company']) ?>
                            </td>


                            <td data-label="Verification">

                                <span class="status-badge <?= $verificationClass ?>">
                                    <?= htmlspecialchars($verificationLabel) ?>
                                </span>

                            </td>


                            <td data-label="Total Bids">
                                <?= htmlspecialchars((string)$row['total_bids']) ?>
                            </td>


                            <td data-label="Winning Bids">
                                <?= htmlspecialchars((string)$row['winning_bids']) ?>
                            </td>


                            <td
                                data-label="Total Bid Amount"
                                class="nowrap-cell"
                            >
                                Rs. <?= number_format((float)$row['total_bid_amount'], 2) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =====================================================
         RECENT AUDIT LOGS
    ====================================================== -->

    <section class="report-card">

        <div class="report-card-header">

            <div>

                <h2>
                    Recent Audit Logs
                </h2>

                <p>
                    Recent administrative and recycler activity recorded by the system.
                </p>

            </div>

        </div>


        <div class="report-table-wrapper">

            <table class="report-table audit-table">

                <thead>

                    <tr>
                        <th>Log ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Created At</th>
                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($recentAuditLogs as $row): ?>

                        <tr>

                            <td data-label="Log ID">
                                #<?= htmlspecialchars((string)$row['log_id']) ?>
                            </td>


                            <td data-label="User">
                                <?= htmlspecialchars($row['user']) ?>
                            </td>


                            <td data-label="Role">

                                <?=
                                htmlspecialchars(
                                    ucwords(
                                        strtolower(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $row['role']
                                            )
                                        )
                                    )
                                )
                                ?>

                            </td>


                            <td data-label="Action">
                                <?= htmlspecialchars($row['action']) ?>
                            </td>


                            <td data-label="Description">
                                <?= htmlspecialchars($row['description']) ?>
                            </td>


                            <td data-label="Created At">
                                <?= htmlspecialchars($row['created_at']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>


</section>
