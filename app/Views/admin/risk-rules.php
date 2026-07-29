<section class="risk-rules-page">

    <!-- =========================================
         Page Introduction
    ========================================== -->
    <div class="risk-page-intro">

        <span class="page-eyebrow">
            Administration
        </span>

        <h1>
            Risk Rule Management
        </h1>

        <p>
            Configure risk classification rules for E-Waste collection.
        </p>

    </div>


    <!-- =========================================
         Create Risk Rule Card
    ========================================== -->
    <section class="risk-card create-rule-card">

        <div class="risk-card-header">

            <div>
                <h2>Create Risk Rule</h2>

                <p>
                    Define how specific E-Waste items should be classified and handled.
                </p>
            </div>

        </div>


        <!--
            IMPORTANT:
            Keep your EXISTING form action, method,
            field names and backend bindings here.
        -->
        <form method="POST" class="risk-rule-form">

            <div class="risk-form-grid">

                <!-- Category -->
                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <select
                        id="category"
                        name="category"
                    >
                        <option value="">
                            Optional context
                        </option>

                        <!-- Keep your existing dynamic options here -->

                    </select>

                </div>


                <!-- Specific Item -->
                <div class="form-group">

                    <label for="specific_item">
                        Specific Item
                    </label>

                    <select
                        id="specific_item"
                        name="specific_item"
                    >
                        <option value="">
                            Select item
                        </option>

                        <!-- Keep your existing dynamic options here -->

                    </select>

                </div>


                <!-- Condition -->
                <div class="form-group">

                    <label for="condition">
                        Condition
                    </label>

                    <select
                        id="condition"
                        name="condition"
                    >
                        <option value="">
                            Unknown
                        </option>

                        <!-- Keep your existing options here -->

                    </select>

                </div>


                <!-- Risk Level -->
                <div class="form-group">

                    <label for="risk_level">
                        Risk Level
                    </label>

                    <select
                        id="risk_level"
                        name="risk_level"
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


                <!-- Description -->
                <div class="form-group form-group-full">

                    <label for="description">
                        Rule Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Example: Leaking batteries must be reviewed before collection."
                    ></textarea>

                </div>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    class="create-rule-btn"
                >
                    Create Risk Rule
                </button>

            </div>

        </form>

    </section>


    <!-- =========================================
         Risk Rules Table Card
    ========================================== -->
    <section class="risk-card rules-list-card">

        <div class="risk-card-header">

            <div>
                <h2>Risk Rules</h2>

                <p>
                    Review the currently configured collection risk rules.
                </p>
            </div>

        </div>


        <div class="risk-table-wrapper">

            <table class="risk-table">

                <thead>

                    <tr>
                        <th>Rule ID</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Condition</th>
                        <th>Risk Level</th>
                        <th>Action</th>
                        <th>Description</th>
                    </tr>

                </thead>


                <tbody>

                    <!--
                        REPLACE these demo rows with your EXISTING PHP loop.
                        Keep the same classes inside each row.
                    -->


                    <!-- Row 1 -->
                    <tr>

                        <td data-label="Rule ID">
                            #50
                        </td>

                        <td data-label="Category">
                            Do Not Collect
                        </td>

                        <td data-label="Item">
                            Biohazardous equipment
                        </td>

                        <td data-label="Condition">
                            Biohazardous equipment should not be collected as a normal item.
                        </td>

                        <td data-label="Risk Level">

                            <span class="risk-badge risk-high">
                                High
                            </span>

                        </td>

                        <td data-label="Action">

                            <span class="action-badge action-reject">
                                Reject Collection
                            </span>

                        </td>

                        <td data-label="Description">
                            Biohazardous equipment must not be collected through the normal E-Waste process.
                        </td>

                    </tr>


                    <!-- Row 2 -->
                    <tr>

                        <td data-label="Rule ID">
                            #49
                        </td>

                        <td data-label="Category">
                            Do Not Collect
                        </td>

                        <td data-label="Item">
                            Items containing mercury/cadmium/phosphorous
                        </td>

                        <td data-label="Condition">
                            Items containing mercury/cadmium/phosphorous should not be collected as a normal item.
                        </td>

                        <td data-label="Risk Level">

                            <span class="risk-badge risk-high">
                                High
                            </span>

                        </td>

                        <td data-label="Action">

                            <span class="action-badge action-reject">
                                Reject Collection
                            </span>

                        </td>

                        <td data-label="Description">
                            These materials require controlled handling and should not enter the normal collection stream.
                        </td>

                    </tr>


                    <!-- Row 3 -->
                    <tr>

                        <td data-label="Rule ID">
                            #48
                        </td>

                        <td data-label="Category">
                            Do Not Collect
                        </td>

                        <td data-label="Item">
                            Smoke detectors / radioactive sources
                        </td>

                        <td data-label="Condition">
                            Smoke detectors or radioactive sources should not be collected as a normal item.
                        </td>

                        <td data-label="Risk Level">

                            <span class="risk-badge risk-high">
                                High
                            </span>

                        </td>

                        <td data-label="Action">

                            <span class="action-badge action-reject">
                                Reject Collection
                            </span>

                        </td>

                        <td data-label="Description">
                            Radioactive-source devices require specialized handling procedures.
                        </td>

                    </tr>


                    <!-- Row 4 -->
                    <tr>

                        <td data-label="Rule ID">
                            #47
                        </td>

                        <td data-label="Category">
                            Do Not Collect
                        </td>

                        <td data-label="Item">
                            CT scanners / X-ray equipment
                        </td>

                        <td data-label="Condition">
                            CT scanners and X-ray equipment should not be collected as normal E-Waste.
                        </td>

                        <td data-label="Risk Level">

                            <span class="risk-badge risk-high">
                                High
                            </span>

                        </td>

                        <td data-label="Action">

                            <span class="action-badge action-reject">
                                Reject Collection
                            </span>

                        </td>

                        <td data-label="Description">
                            These devices require specialist inspection and controlled disposal.
                        </td>

                    </tr>


                    <!-- Row 5 -->
                    <tr>

                        <td data-label="Rule ID">
                            #46
                        </td>

                        <td data-label="Category">
                            Do Not Collect
                        </td>

                        <td data-label="Item">
                            Leaking batteries
                        </td>

                        <td data-label="Condition">
                            Leaking batteries should not be collected as normal items.
                        </td>

                        <td data-label="Risk Level">

                            <span class="risk-badge risk-high">
                                High
                            </span>

                        </td>

                        <td data-label="Action">

                            <span class="action-badge action-reject">
                                Reject Collection
                            </span>

                        </td>

                        <td data-label="Description">
                            Leaking batteries must be reviewed before acceptance into the collection process.
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</section>