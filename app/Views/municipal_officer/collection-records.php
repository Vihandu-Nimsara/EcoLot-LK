<section class="collection-records-page">

    <div class="page-toolbar">

        <div>
            <h1>Collection Records</h1>

            <p>
                Review collector submissions grouped by collection schedule and verify completed collections.
            </p>
        </div>

    </div>


    <section class="records-filter-card">

        <div class="filter-heading">

            <h2>Filter Collection Records</h2>

            <p>
                Find collection records by campaign, collection schedule, or verification status.
            </p>

        </div>


        <form class="records-filter-form">

            <div class="records-filter-grid">

                <div class="form-group">

                    <label for="campaign">
                        Campaign
                    </label>

                    <select id="campaign" name="campaign">

                        <option value="">
                            All Campaigns
                        </option>

                        <option value="1">
                            July 2026 Campaign
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="schedule">
                        Collection Schedule
                    </label>

                    <select id="schedule" name="schedule">

                        <option value="">
                            All Schedules
                        </option>

                        <option value="SCH-0009">
                            SCH-0009 - Rajagiriya - 23 Jul 2026
                        </option>

                        <option value="SCH-0007">
                            SCH-0007 - Kollupitiya - 16 Jul 2026
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="verification-status">
                        Verification Status
                    </label>

                    <select id="verification-status" name="verification_status">

                        <option value="">
                            All Statuses
                        </option>

                        <option value="PENDING">
                            Pending Verification
                        </option>

                        <option value="VERIFIED">
                            Verified
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


    <section class="records-list-card">

        <div class="records-list-heading">

            <div>

                <h2>Schedule Collection Summary</h2>

                <p>
                    Each row summarizes collection records submitted for one area collection schedule.
                </p>

            </div>

        </div>


        <div class="records-table-wrapper">

            <table class="records-table">

                <thead>

                    <tr>
                        <th>Schedule</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Assigned Stops</th>
                        <th>Collected</th>
                        <th>Partial</th>
                        <th>Pending</th>
                        <th>Rejected / Failed</th>
                        <th>Total Weight</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>

                    <tr>

                        <td>
                            SCH-0009
                        </td>

                        <td>
                            Rajagiriya QA Zone
                        </td>

                        <td>
                            23 Jul 2026
                        </td>

                        <td>
                            4
                        </td>

                        <td>
                            3
                        </td>

                        <td>
                            1
                        </td>

                        <td>
                            0
                        </td>

                        <td>
                            0
                        </td>

                        <td>
                            31.50 kg
                        </td>

                        <td>

                            <span class="verification-status pending">
                                PENDING
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="record-action-btn primary-action">
                                Review
                            </button>

                        </td>

                    </tr>


                    <tr>

                        <td>
                            SCH-0007
                        </td>

                        <td>
                            Kollupitiya QA Zone
                        </td>

                        <td>
                            16 Jul 2026
                        </td>

                        <td>
                            2
                        </td>

                        <td>
                            2
                        </td>

                        <td>
                            0
                        </td>

                        <td>
                            0
                        </td>

                        <td>
                            0
                        </td>

                        <td>
                            18.00 kg
                        </td>

                        <td>

                            <span class="verification-status verified">
                                VERIFIED
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="record-action-btn secondary-action">
                                View
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</section>