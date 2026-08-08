<?php

/* =========================================================
   Demo Category Data
   Later these can come from the Controller / Model
========================================================= */

$categories = [

    [
        'id' => 3,
        'name' => 'Automobile E-Waste',
        'description' => 'Vehicle-related electronic waste items',
        'items' => 9,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 8,
        'name' => 'Demo Battery and Circuit Boards',
        'description' => 'Demo batteries, circuit boards, and components requiring no special recycler handling.',
        'items' => 2,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 7,
        'name' => 'Demo Consumer Electronics',
        'description' => 'Demo laptops, mobile phones, printers, monitors, keyboards, routers, and accessories.',
        'items' => 4,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 10,
        'name' => 'DEMO-FIX Recycler Batteries',
        'description' => 'DEMO-FIX category for batteries and higher-risk e-waste lots.',
        'items' => 1,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 9,
        'name' => 'DEMO-FIX Recycler Electronics',
        'description' => 'DEMO-FIX category for laptops, phones, monitors, printers, routers, and boards.',
        'items' => 3,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 6,
        'name' => 'Do Not Collect',
        'description' => 'Items excluded from normal collection and should be flagged or rejected.',
        'items' => 8,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 1,
        'name' => 'Domestic E-Waste',
        'description' => 'Household electronic waste items',
        'items' => 9,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 4,
        'name' => 'Industrial E-Waste',
        'description' => 'Industrial electronic waste items',
        'items' => 9,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 5,
        'name' => 'Medical E-Waste',
        'description' => 'Medical electronic waste items requiring careful review',
        'items' => 7,
        'status' => 'ACTIVE'
    ],

    [
        'id' => 2,
        'name' => 'Office E-Waste',
        'description' => 'Office and business electronic waste items',
        'items' => 8,
        'status' => 'ACTIVE'
    ]

];


/* =========================================================
   Demo E-Waste Item Data
========================================================= */

$items = [

    [
        'id' => 24,
        'category' => 'Automobile E-Waste',
        'item' => 'Cables',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 18,
        'category' => 'Automobile E-Waste',
        'item' => 'Dashboard electronics',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 26,
        'category' => 'Automobile E-Waste',
        'item' => 'Heaters',
        'status' => 'ACCEPTED',
        'risk' => 'MEDIUM'
    ],

    [
        'id' => 20,
        'category' => 'Automobile E-Waste',
        'item' => 'Hybrid batteries/EV batteries',
        'status' => 'REVIEW_REQUIRED',
        'risk' => 'HIGH'
    ],

    [
        'id' => 19,
        'category' => 'Automobile E-Waste',
        'item' => 'LED headlights',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 21,
        'category' => 'Automobile E-Waste',
        'item' => 'Motors/Alternators',
        'status' => 'ACCEPTED',
        'risk' => 'MEDIUM'
    ],

    [
        'id' => 25,
        'category' => 'Automobile E-Waste',
        'item' => 'Relays',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 23,
        'category' => 'Automobile E-Waste',
        'item' => 'Sensors',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 22,
        'category' => 'Automobile E-Waste',
        'item' => 'Switches',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 56,
        'category' => 'Demo Battery and Circuit Boards',
        'item' => 'Demo Circuit Boards',
        'status' => 'ACCEPTED',
        'risk' => 'MEDIUM'
    ],

    [
        'id' => 55,
        'category' => 'Demo Battery and Circuit Boards',
        'item' => 'Demo Lithium Batteries',
        'status' => 'REVIEW_REQUIRED',
        'risk' => 'HIGH'
    ],

    [
        'id' => 54,
        'category' => 'Demo Consumer Electronics',
        'item' => 'Demo Keyboards and Routers',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 51,
        'category' => 'Demo Consumer Electronics',
        'item' => 'Demo Laptops',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 52,
        'category' => 'Demo Consumer Electronics',
        'item' => 'Demo Mobile Phones',
        'status' => 'ACCEPTED',
        'risk' => 'LOW'
    ],

    [
        'id' => 53,
        'category' => 'Demo Consumer Electronics',
        'item' => 'Demo Printers and Monitors',
        'status' => 'ACCEPTED',
        'risk' => 'MEDIUM'
    ],

    [
        'id' => 60,
        'category' => 'DEMO-FIX Recycler Batteries',
        'item' => 'DEMO-FIX Lithium Batteries',
        'status' => 'REVIEW_REQUIRED',
        'risk' => 'HIGH'
    ]

];

?>


<section class="category-items-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="category-items-header">

        <div>

            <h1>
                Category & Item Management
            </h1>

            <p>
                Maintain the E-Waste categories and accepted item catalogue.
            </p>

        </div>

    </div>

    <p class="page-notice" data-page-notice tabindex="-1" hidden>Demo only — catalogue changes will be connected during backend implementation.</p>



    <!-- =====================================================
         CREATE CATEGORY / CREATE ITEM
    ====================================================== -->

    <section class="management-card">


        <!-- =========================
             CREATE CATEGORY
        ========================== -->

        <div class="management-section">

            <div class="section-heading">

                <h2>
                    Create Category
                </h2>

                <p>
                    Add a new E-Waste category to the system catalogue.
                </p>

            </div>


            <form method="POST" class="admin-form" data-demo-form>

                <div class="form-grid">


                    <div class="form-group form-group-full">

                        <label for="category_name">
                            Category Name
                        </label>

                        <input
                            type="text"
                            id="category_name"
                            name="category_name"
                            placeholder="Enter category name"
                        >

                    </div>


                    <div class="form-group form-group-full">

                        <label for="category_description">
                            Description
                        </label>

                        <textarea
                            id="category_description"
                            name="description"
                            rows="4"
                            placeholder="Enter category description"
                        ></textarea>

                    </div>


                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="primary-action-btn"
                    >
                        Create Category
                    </button>

                </div>

            </form>

        </div>



        <!-- Divider -->

        <div class="management-divider"></div>



        <!-- =========================
             CREATE E-WASTE ITEM
        ========================== -->

        <div class="management-section">

            <div class="section-heading">

                <h2>
                    Create E-Waste Item
                </h2>

                <p>
                    Add an item and configure its default collection behaviour.
                </p>

            </div>


            <form method="POST" class="admin-form" data-demo-form>

                <div class="form-grid">


                    <!-- Category -->

                    <div class="form-group form-group-full">

                        <label for="item_category">
                            Category
                        </label>

                        <select
                            id="item_category"
                            name="category_id"
                        >

                            <option value="">
                                Select category
                            </option>


                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= htmlspecialchars((string)$category['id']) ?>"
                                >
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>



                    <!-- Item Name -->

                    <div class="form-group form-group-full">

                        <label for="item_name">
                            Item Name
                        </label>

                        <input
                            type="text"
                            id="item_name"
                            name="item_name"
                            placeholder="Enter E-Waste item name"
                        >

                    </div>



                    <!-- Collection Status -->

                    <div class="form-group">

                        <label for="collection_status">
                            Collection Status
                        </label>

                        <select
                            id="collection_status"
                            name="collection_status"
                        >

                            <option value="ACCEPTED">
                                Accepted
                            </option>

                            <option value="REVIEW_REQUIRED">
                                Review Required
                            </option>

                            <option value="DO_NOT_COLLECT">
                                Do Not Collect
                            </option>

                        </select>

                    </div>



                    <!-- Default Risk -->

                    <div class="form-group">

                        <label for="default_risk_level">
                            Default Risk Level
                        </label>

                        <select
                            id="default_risk_level"
                            name="default_risk_level"
                        >

                            <option value="LOW">
                                Low
                            </option>

                            <option value="MEDIUM">
                                Medium
                            </option>

                            <option value="HIGH">
                                High
                            </option>

                        </select>

                    </div>


                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="primary-action-btn"
                    >
                        Create Item
                    </button>

                </div>

            </form>

        </div>


    </section>



    <!-- =====================================================
         CATEGORIES
    ====================================================== -->

    <section class="data-card categories-card">


        <div class="data-card-header">

            <div>

                <h2>
                    Categories
                </h2>

                <p>
                    Manage the E-Waste categories currently available in the system.
                </p>

            </div>

        </div>



        <div class="data-table-wrapper categories-table-wrapper">

            <table class="categories-table">


                <thead>

                    <tr>

                        <th>Category ID</th>

                        <th>Name</th>

                        <th>Description</th>

                        <th>Items</th>

                        <th>Status</th>

                        <th>Update</th>

                    </tr>

                </thead>



                <tbody>


                    <?php foreach ($categories as $category): ?>


                        <tr>


                            <td data-label="Category ID">

                                #<?= htmlspecialchars((string)$category['id']) ?>

                            </td>


                            <td data-label="Name">

                                <?= htmlspecialchars($category['name']) ?>

                            </td>


                            <td data-label="Description">

                                <?= htmlspecialchars($category['description']) ?>

                            </td>


                            <td
                                data-label="Items"
                                class="center-cell"
                            >

                                <?= htmlspecialchars((string)$category['items']) ?>

                            </td>


                            <td data-label="Status">

                                <span class="status-badge status-active">

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            strtolower($category['status'])
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <td data-label="Update">


                                <form
                                    method="POST"
                                    class="category-update-form"
                                    data-demo-form
                                >

                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value="<?= htmlspecialchars((string)$category['id']) ?>"
                                    >


                                    <select
                                        name="status"
                                        class="status-select"
                                    >

                                        <option
                                            value="ACTIVE"
                                            <?= $category['status'] === 'ACTIVE'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Active
                                        </option>


                                        <option
                                            value="INACTIVE"
                                            <?= $category['status'] === 'INACTIVE'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Inactive
                                        </option>

                                    </select>


                                    <button
                                        type="button"
                                        class="table-action-btn"
                                        data-admin-dialog="category-status"
                                        data-action="Update Status"
                                        data-name="<?= htmlspecialchars($category['name']) ?>"
                                    >
                                        Update
                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>


            </table>

        </div>


    </section>



    <!-- =====================================================
         E-WASTE ITEMS
    ====================================================== -->

    <section class="data-card items-card">


        <div class="data-card-header">

            <div>

                <h2>
                    E-Waste Items
                </h2>

                <p>
                    Review item categories, collection status and default risk levels.
                </p>

            </div>

        </div>



        <div class="data-table-wrapper items-table-wrapper">


            <table class="items-table">


                <thead>

                    <tr>

                        <th>Item ID</th>

                        <th>Category</th>

                        <th>Item</th>

                        <th>Collection Status</th>

                        <th>Risk Level</th>

                    </tr>

                </thead>



                <tbody>


                    <?php foreach ($items as $item): ?>


                        <?php

                        $statusText = ucwords(
                            strtolower(
                                str_replace(
                                    '_',
                                    ' ',
                                    $item['status']
                                )
                            )
                        );


                        if ($item['status'] === 'REVIEW_REQUIRED') {

                            $statusClass = 'status-review';

                        } elseif ($item['status'] === 'DO_NOT_COLLECT') {

                            $statusClass = 'status-rejected';

                        } else {

                            $statusClass = 'status-accepted';

                        }


                        if ($item['risk'] === 'HIGH') {

                            $riskClass = 'risk-high';

                        } elseif ($item['risk'] === 'MEDIUM') {

                            $riskClass = 'risk-medium';

                        } else {

                            $riskClass = 'risk-low';

                        }

                        ?>


                        <tr>


                            <td data-label="Item ID">

                                #<?= htmlspecialchars((string)$item['id']) ?>

                            </td>


                            <td data-label="Category">

                                <?= htmlspecialchars($item['category']) ?>

                            </td>


                            <td data-label="Item">

                                <?= htmlspecialchars($item['item']) ?>

                            </td>


                            <td data-label="Collection Status">

                                <span class="status-badge <?= $statusClass ?>">

                                    <?= htmlspecialchars($statusText) ?>

                                </span>

                            </td>


                            <td data-label="Risk Level">

                                <span class="risk-badge <?= $riskClass ?>">

                                    <?= htmlspecialchars(
                                        ucfirst(
                                            strtolower($item['risk'])
                                        )
                                    ) ?>

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>


            </table>


        </div>


    </section>


</section>
