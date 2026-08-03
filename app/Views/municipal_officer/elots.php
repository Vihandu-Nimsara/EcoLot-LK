<section class="elots-page">

    <div class="page-toolbar">

        <div>
            <h1>E-Lot & Bid Review</h1>

            <p>
                Create E-Lots from verified collection items and manage recycler bidding.
            </p>
        </div>

        <div class="toolbar-actions">

            <button
                type="button"
                class="primary-btn">
                Create E-Lot
            </button>

            <button
                type="button"
                class="secondary-btn">
                Verified Item Pool
            </button>

        </div>

    </div>

    <section class="elot-filter-card compact-filter-card">

        <form class="elot-filter-form">

            <div class="elot-filter-grid">

                <div class="form-group">

                    <label for="elot-status">
                        E-Lot Status
                    </label>

                    <select id="elot-status" name="elot_status">

                        <option value="">
                            All Statuses
                        </option>

                        <option value="OPEN_FOR_BIDDING">
                            Open for Bidding
                        </option>

                        <option value="AWARDED">
                            Awarded
                        </option>

                        <option value="COMPLETED">
                            Completed
                        </option>

                    </select>

                </div>

                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <select id="category" name="category">

                        <option value="">
                            All Categories
                        </option>

                        <option value="DOMESTIC">
                            Domestic E-Waste
                        </option>

                        <option value="OFFICE">
                            Office E-Waste
                        </option>

                        <option value="INDUSTRIAL">
                            Industrial E-Waste
                        </option>

                    </select>

                </div>

            </div>

            <div class="filter-actions">

                <button
                    type="reset"
                    class="secondary-btn">
                    Clear
                </button>

                <button
                    type="submit"
                    class="primary-btn">
                    Apply Filters
                </button>

            </div>

        </form>

    </section>

    <section class="elots-list-card">

        <div class="elots-list-heading">

            <div>

                <h2>E-Lots</h2>

                <p>
                    Review bidding progress and manage awarded and completed E-Lots.
                </p>

            </div>

        </div>

        <div class="elots-table-wrapper">

            <table class="elots-table">

                <thead>

                    <tr>
                        <th>E-Lot Code</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Items</th>
                        <th>Total Weight</th>
                        <th>Bidding Period</th>
                        <th>Bids</th>
                        <th>Status</th>
                        <th>Winner</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td>
                            EL-001
                        </td>

                        <td>
                            Domestic E-Waste Lot
                        </td>

                        <td>
                            Domestic E-Waste
                        </td>

                        <td>
                            2
                        </td>

                        <td>
                            5.50 kg
                        </td>

                        <td>
                            09 Jul – 14 Jul 2026
                        </td>

                        <td>
                            2
                        </td>

                        <td>

                            <span class="elot-status open">
                                OPEN FOR BIDDING
                            </span>

                        </td>

                        <td>

                            <span class="not-assigned">
                                Not Selected
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="elot-action-btn primary-action">
                                Review Bids
                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>
                            EL-002
                        </td>

                        <td>
                            Office E-Waste Lot
                        </td>

                        <td>
                            Office E-Waste
                        </td>

                        <td>
                            2
                        </td>

                        <td>
                            9.00 kg
                        </td>

                        <td>
                            06 Jul – 09 Jul 2026
                        </td>

                        <td>
                            2
                        </td>

                        <td>

                            <span class="elot-status awarded">
                                AWARDED
                            </span>

                        </td>

                        <td>
                            GreenCycle Lanka (Pvt) Ltd
                        </td>

                        <td>

                            <button
                                type="button"
                                class="elot-action-btn secondary-action">
                                Manage
                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>
                            EL-003
                        </td>

                        <td>
                            Industrial E-Waste Lot
                        </td>

                        <td>
                            Industrial E-Waste
                        </td>

                        <td>
                            1
                        </td>

                        <td>
                            10.00 kg
                        </td>

                        <td>
                            06 Jul – 09 Jul 2026
                        </td>

                        <td>
                            0
                        </td>

                        <td>

                            <span class="elot-status completed">
                                COMPLETED
                            </span>

                        </td>

                        <td>
                            Ceylon Circular Metals (Pvt) Ltd
                        </td>

                        <td>

                            <button
                                type="button"
                                class="elot-action-btn secondary-action">
                                View
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</section>
