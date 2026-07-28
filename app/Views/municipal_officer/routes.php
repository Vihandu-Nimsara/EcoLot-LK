<section class="routes-page">

    <div class="page-toolbar">

        <div>
            <h1>Collection Routes</h1>

            <p>
                Manage automatically generated collection routes and assign collectors and vehicles.
            </p>
        </div>

    </div>


    <section class="route-filter-card">

        <div class="filter-heading">

            <h2>Filter Collection Routes</h2>

            <p>
                Find routes by campaign, collection schedule, or route status.
            </p>

        </div>


        <form class="route-filter-form">

            <div class="route-filter-grid">

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

                        <option value="SCH-0010">
                            SCH-0010 - Wellawatte - 30 Jul 2026
                        </option>

                        <option value="SCH-0009">
                            SCH-0009 - Rajagiriya - 23 Jul 2026
                        </option>

                        <option value="SCH-0008">
                            SCH-0008 - Narahenpita - 19 Jul 2026
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="route-status">
                        Route Status
                    </label>

                    <select id="route-status" name="route_status">

                        <option value="">
                            All Statuses
                        </option>

                        <option value="PLANNED">
                            Planned
                        </option>

                        <option value="ASSIGNED">
                            Assigned
                        </option>

                        <option value="IN_PROGRESS">
                            In Progress
                        </option>

                        <option value="COMPLETED">
                            Completed
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


    <section class="routes-list-card">

        <div class="routes-list-heading">

            <div>

                <h2>Collection Routes</h2>

                <p>
                    Each area collection schedule has one automatically generated route.
                </p>

            </div>

        </div>


        <div class="routes-table-wrapper">

            <table class="routes-table">

                <thead>

                    <tr>
                        <th>Route ID</th>
                        <th>Schedule</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Stops</th>
                        <th>Collector</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>

                </thead>


                <tbody>

                    <!-- Completed Route -->
                    <tr>

                        <td>
                            RT-001
                        </td>

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
                            3
                        </td>

                        <td>
                            QA Collector 02
                        </td>

                        <td>
                            QA-EC-1002
                        </td>

                        <td>

                            <span class="route-status completed">
                                COMPLETED
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="route-action-btn secondary-action">
                                View
                            </button>

                        </td>

                    </tr>


                    <!-- Assigned Route -->
                    <tr>

                        <td>
                            RT-002
                        </td>

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
                            3
                        </td>

                        <td>
                            QA Collector 01
                        </td>

                        <td>
                            QA-EC-1001
                        </td>

                        <td>

                            <span class="route-status assigned">
                                ASSIGNED
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="route-action-btn secondary-action">
                                Manage
                            </button>

                        </td>

                    </tr>


                    <!-- Planned Route -->
                    <tr>

                        <td>
                            RT-003
                        </td>

                        <td>
                            SCH-0010
                        </td>

                        <td>
                            Wellawatte QA Zone
                        </td>

                        <td>
                            30 Jul 2026
                        </td>

                        <td>
                            0
                        </td>

                        <td>

                            <span class="not-assigned">
                                Not Assigned
                            </span>

                        </td>

                        <td>
                            <span class="not-assigned">
                                Not Assigned
                            </span>
                        </td>

                        <td>

                            <span class="route-status planned">
                                PLANNED
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="route-action-btn primary-action">
                                Assign
                            </button>

                        </td>

                    </tr>


                    <!-- In Progress Route -->
                    <tr>

                        <td>
                            RT-004
                        </td>

                        <td>
                            SCH-0011
                        </td>

                        <td>
                            Moratuwa QA Zone
                        </td>

                        <td>
                            02 Aug 2026
                        </td>

                        <td>
                            4
                        </td>

                        <td>
                            QA Collector 03
                        </td>

                        <td>
                            QA-EC-1003
                        </td>

                        <td>

                            <span class="route-status in-progress">
                                IN PROGRESS
                            </span>

                        </td>

                        <td>

                            <button
                                type="button"
                                class="route-action-btn secondary-action">
                                View
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</section>