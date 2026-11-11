<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| EcoLot-LK — Collector E-Lots View
|--------------------------------------------------------------------------
| Features:
| - Verified Items Pool
| - Available / Selected / Allocated statuses
| - E-Lot summary cards
| - My E-Lots management
| - Create and edit E-Lot modal
| - Save Draft, Submit, and Delete actions
|--------------------------------------------------------------------------
*/

$lots = $lots ?? [];
$pool = $pool ?? [];
$categories = $categories ?? [];

$notice = $notice ?? '';
$error = $error ?? '';
$csrfToken = $csrfToken ?? '';
$nextLotCode = $nextLotCode ?? '';

$jsonFlags =
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_INVALID_UTF8_SUBSTITUTE;

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<!-- ========================================================= -->
<!-- DATA FROM PHP TO JAVASCRIPT -->
<!-- ========================================================= -->

<script>
    window.COLLECTOR_LOTS =
        <?= json_encode($lots, $jsonFlags) ?: '[]' ?>;

    window.COLLECTOR_POOL =
        <?= json_encode($pool, $jsonFlags) ?: '[]' ?>;

    window.COLLECTOR_CATEGORIES =
        <?= json_encode($categories, $jsonFlags) ?: '[]' ?>;

    window.COLLECTOR_NEXT_LOT_CODE =
        <?= json_encode($nextLotCode, $jsonFlags) ?: '""' ?>;
</script>

<!-- ========================================================= -->
<!-- CSRF TOKEN -->
<!-- ========================================================= -->

<div
    data-elot-csrf="<?= $escape($csrfToken) ?>"
    hidden>
</div>

<!-- ========================================================= -->
<!-- COLLECTOR E-LOTS PAGE -->
<!-- ========================================================= -->

<section
    class="collector-elots"
    data-collector-page="elots">

    <!-- ===================================================== -->
    <!-- PAGE HEADER -->
    <!-- ===================================================== -->

    <header class="collector-page-heading">

        <div>

            <span class="collector-eyebrow">
                Verified collection output
            </span>

            <h1>
                My E-Lots
            </h1>

            <p>
                Create and manage E-Lots using verified
                collection items. Submit completed drafts
                to the Municipal Officer for verification.
            </p>

        </div>

        <button
            id="collector-create-elot"
            class="collector-primary-button"
            type="button">

            Create E-Lot

        </button>

    </header>

    <!-- ===================================================== -->
    <!-- SUCCESS MESSAGE -->
    <!-- ===================================================== -->

    <?php if (!empty($notice)): ?>

        <div
            class="collector-feedback collector-feedback--success"
            role="status">

            <?= $escape($notice) ?>

        </div>

    <?php endif; ?>

    <!-- ===================================================== -->
    <!-- ERROR MESSAGE -->
    <!-- ===================================================== -->

    <?php if (!empty($error)): ?>

        <div
            class="collector-feedback collector-feedback--error"
            role="alert">

            <?= $escape($error) ?>

        </div>

    <?php endif; ?>

    <!-- ===================================================== -->
    <!-- E-LOT SUMMARY -->
    <!-- ===================================================== -->

    <section
        class="collector-elot-summary"
        aria-label="E-Lot summary">

        <!-- Available Items -->

        <article>

            <span>
                Available Items
            </span>

            <strong id="elot-verified-item-count">
                0
            </strong>

        </article>

        <!-- Draft E-Lots -->

        <article>

            <span>
                Draft E-Lots
            </span>

            <strong id="elot-draft-count">
                0
            </strong>

        </article>

        <!-- Pending Verification -->

        <article>

            <span>
                Pending Verification
            </span>

            <strong id="elot-pending-count">
                0
            </strong>

        </article>

        <!-- Active E-Lots -->

        <article>

            <span>
                Open / Awarded / Processing
            </span>

            <strong id="elot-active-count">
                0
            </strong>

        </article>

    </section>

    <!-- ===================================================== -->
    <!-- VERIFIED ITEMS POOL -->
    <!-- ===================================================== -->

    <section
        class="collector-elot-card"
        aria-labelledby="verified-items-title">

        <div class="collector-section-header">

            <div>

                <h2 id="verified-items-title">
                    Verified Items Pool
                </h2>

                <p>
                    Items verified by the Municipal Officer
                    appear here. Available items can be
                    selected when creating an E-Lot.
                </p>

            </div>

            <span
                id="elot-available-weight"
                class="collector-count-pill">

                0.00 kg available

            </span>

        </div>

        <!-- VERIFIED ITEMS TABLE -->

        <div class="collector-table-wrap">

            <table
                class="collector-table collector-table--verified-items">

                <thead>

                    <tr>

                        <th scope="col">
                            Item
                        </th>

                        <th scope="col">
                            Category
                        </th>

                        <th scope="col">
                            Quantity
                        </th>

                        <th scope="col">
                            Actual Weight
                        </th>

                        <th scope="col">
                            Verified On
                        </th>

                        <th scope="col">
                            Availability
                        </th>

                    </tr>

                </thead>

                <tbody id="collector-verified-item-rows">

                    <!--
                        JavaScript automatically displays:

                        Item Name
                        Category
                        Quantity
                        Actual Weight
                        Verified Date

                        Availability:
                        - Available
                        - Selected
                        - Allocated
                    -->

                </tbody>

            </table>

        </div>

    </section>

    <!-- ===================================================== -->
    <!-- MY E-LOTS SECTION -->
    <!-- ===================================================== -->

    <section
        class="collector-elot-card"
        aria-labelledby="my-elots-title">

        <div
            class="collector-section-header collector-section-header--filters">

            <div>

                <h2 id="my-elots-title">
                    My E-Lots
                </h2>

                <p>
                    Create, edit, delete, and submit E-Lot
                    drafts. Submitted E-Lots remain pending
                    until Municipal Officer verification.
                </p>

            </div>

            <!-- STATUS FILTER -->

            <label class="collector-inline-filter">

                <span class="sr-only">
                    Filter E-Lots by status
                </span>

                <select id="collector-elot-status-filter">

                    <option value="ALL">
                        All statuses
                    </option>

                    <option value="DRAFT">
                        Draft
                    </option>

                    <option value="PENDING_VERIFICATION">
                        Pending Verification
                    </option>

                    <option value="REJECTED">
                        Rejected
                    </option>

                    <option value="OPEN_FOR_BIDDING">
                        Open for Bidding
                    </option>

                    <option value="AWARDED">
                        Awarded
                    </option>

                    <option value="PROCESSING">
                        Processing
                    </option>

                    <option value="COMPLETED">
                        Completed
                    </option>

                    <option value="CANCELLED">
                        Cancelled
                    </option>

                </select>

            </label>

        </div>

        <!-- ================================================= -->
        <!-- MY E-LOTS TABLE -->
        <!-- ================================================= -->

        <div class="collector-table-wrap">

            <table
                class="collector-table collector-table--elots">

                <thead>

                    <tr>

                        <th scope="col">
                            E-Lot ID
                        </th>

                        <th scope="col">
                            Name
                        </th>

                        <th scope="col">
                            Category
                        </th>

                        <th scope="col">
                            Items
                        </th>

                        <th scope="col">
                            Total Weight
                        </th>

                        <th scope="col">
                            Status
                        </th>

                        <th
                            scope="col"
                            class="collector-action-column">

                            Action

                        </th>

                    </tr>

                </thead>

                <tbody id="collector-elot-rows">

                    <!--
                        JavaScript displays saved E-Lots.

                        Each row includes:

                        - E-Lot ID
                        - E-Lot Name
                        - Category
                        - Number of Items
                        - Total Weight
                        - Current Status
                        - View
                        - Edit
                        - Submit
                        - Delete
                    -->

                </tbody>

            </table>

        </div>

        <!-- ================================================= -->
        <!-- EMPTY E-LOT MESSAGE -->
        <!-- ================================================= -->

        <div
            id="collector-elot-empty"
            class="collector-empty-state"
            hidden>

            <strong>
                No E-Lots found
            </strong>

            <span>
                Create an E-Lot from verified items
                or change the current status filter.
            </span>

        </div>

    </section>

</section>

<!-- ========================================================= -->
<!-- CREATE / EDIT / VIEW E-LOT MODAL -->
<!-- ========================================================= -->

<div
    id="collector-elot-modal"
    class="collector-modal"
    hidden>

    <!-- MODAL BACKDROP -->

    <div
        class="collector-modal__backdrop"
        data-elot-modal-close>
    </div>

    <!-- MODAL DIALOG -->

    <section
        class="collector-modal__dialog collector-modal__dialog--elot"
        role="dialog"
        aria-modal="true"
        aria-labelledby="collector-elot-modal-title">

        <!-- MODAL HEADER -->

        <header class="collector-modal__header">

            <div>

                <span class="collector-eyebrow">
                    E-Lot Management
                </span>

                <h2 id="collector-elot-modal-title">
                    Create E-Lot
                </h2>

                <p id="collector-elot-modal-description">
                    Choose a category and select verified
                    items to create an E-Lot draft.
                </p>

            </div>

            <!-- CLOSE BUTTON -->

            <button
                type="button"
                class="collector-modal__close"
                aria-label="Close E-Lot dialog"
                data-elot-modal-close>

                &times;

            </button>

        </header>

        <!-- ================================================= -->
        <!-- DYNAMIC MODAL CONTENT -->
        <!-- ================================================= -->

        <div
            id="collector-elot-modal-body"
            class="collector-modal__body">

            <!--
                elots.js inserts the appropriate content.

                CREATE MODE:
                - E-Lot Category
                - E-Lot Name
                - Verified Item Checkboxes
                - Selected Item Count
                - Total Weight
                - Cancel
                - Save Draft

                EDIT MODE:
                - Existing E-Lot Details
                - Previously Selected Items
                - Update Draft

                VIEW MODE:
                - E-Lot Information
                - Verified Items Table
                - Total Weight
                - Close
            -->

        </div>

    </section>

</div>