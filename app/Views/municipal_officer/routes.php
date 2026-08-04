<section class="routes-page">

    <div class="page-toolbar">

        <div>
            <h1>Collection Routes</h1>

            <p>
                Manage automatically generated collection routes and assign collectors and vehicles.
            </p>
        </div>

    </div>

    <section class="route-filter-card compact-filter-card">

        <form class="route-filter-form" data-route-filter-form>

            <div class="route-filter-grid">

                <div class="form-group">

                    <label for="campaign">
                        Campaign
                    </label>

                    <select id="campaign" name="campaign">

                        <option value="">
                            All Campaigns
                        </option>

                        <option value="campaign-2">
                            July E-Waste Collection Campaign — 7/2026
                        </option>

                        <option value="campaign-1">
                            Colombo Municipal E-Waste Campaign — 8/2026
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

                        <option value="SCH-0008">
                            SCH-0008 — Narahenpita — 19 Aug 2026
                        </option>

                        <option value="SCH-0011">
                            SCH-0011 — Moratuwa — 02 Aug 2026
                        </option>

                        <option value="SCH-0004">
                            SCH-0004 — Rajagiriya — 23 Jul 2026
                        </option>

                        <option value="SCH-0003">
                            SCH-0003 — Kollupitiya — 16 Jul 2026
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

                <span class="route-result-count officer-result-count" data-route-result-count role="status" aria-live="polite">4 routes shown</span>

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

                <tbody data-routes-table-body>

                    <tr
                        data-route-id="RT-001"
                        data-campaign="campaign-2"
                        data-schedule="SCH-0003"
                        data-route-status="COMPLETED"
                    >

                        <td>
                            RT-001
                        </td>

                        <td>
                            SCH-0003
                        </td>

                        <td>
                            Kollupitiya
                        </td>

                        <td>
                            16 Jul 2026
                        </td>

                        <td>
                            3
                        </td>

                        <td>
                            Nuwan Silva
                        </td>

                        <td>
                            EC-1002
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

                    <tr
                        data-route-id="RT-002"
                        data-campaign="campaign-2"
                        data-schedule="SCH-0004"
                        data-route-status="COMPLETED"
                    >

                        <td>
                            RT-002
                        </td>

                        <td>
                            SCH-0004
                        </td>

                        <td>
                            Rajagiriya
                        </td>

                        <td>
                            23 Jul 2026
                        </td>

                        <td>
                            3
                        </td>

                        <td>
                            Sunil Perera
                        </td>

                        <td>
                            EC-1001
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

                    <tr
                        data-route-id="RT-003"
                        data-campaign="campaign-1"
                        data-schedule="SCH-0008"
                        data-route-status="PLANNED"
                    >

                        <td>
                            RT-003
                        </td>

                        <td>
                            SCH-0008
                        </td>

                        <td>
                            Narahenpita
                        </td>

                        <td>
                            19 Aug 2026
                        </td>

                        <td>
                            2
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

                    <tr
                        data-route-id="RT-004"
                        data-campaign="campaign-1"
                        data-schedule="SCH-0011"
                        data-route-status="IN_PROGRESS"
                    >

                        <td>
                            RT-004
                        </td>

                        <td>
                            SCH-0011
                        </td>

                        <td>
                            Moratuwa
                        </td>

                        <td>
                            02 Aug 2026
                        </td>

                        <td>
                            4
                        </td>

                        <td>
                            Chamara Fernando
                        </td>

                        <td>
                            EC-1003
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
                                Manage
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

        <div class="routes-empty-state officer-empty-state" data-routes-empty-state hidden>
            No collection routes match the selected filters.
        </div>

    </section>

</section>

<div class="route-dialog officer-dialog" data-route-dialog hidden>
    <section
        class="route-dialog-card officer-dialog-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="route-dialog-title"
    >
        <div class="route-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-route-dialog-eyebrow>Collection route</span>
                <h2 id="route-dialog-title" data-route-dialog-title>Manage Route</h2>
                <p data-route-dialog-description>Assign collection resources and update route progress.</p>
            </div>

            <button
                type="button"
                class="route-dialog-close officer-dialog-close"
                aria-label="Close route details"
                data-close-route-dialog
            >×</button>
        </div>

        <div class="route-summary-grid">
            <div><span>Schedule</span><strong data-route-schedule></strong></div>
            <div><span>Area</span><strong data-route-area></strong></div>
            <div><span>Collection Date</span><strong data-route-date></strong></div>
            <div><span>Stops</span><strong data-route-stops></strong></div>
        </div>

        <form class="route-manage-form" data-route-manage-form>
            <div class="route-resource-grid">
                <div class="form-group">
                    <label for="route-collector">Collector</label>
                    <select id="route-collector" name="collector">
                        <option value="">Select collector</option>
                        <option value="Nuwan Silva">Nuwan Silva</option>
                        <option value="Sunil Perera">Sunil Perera</option>
                        <option value="Chamara Fernando">Chamara Fernando</option>
                        <option value="Malini Jayawardena">Malini Jayawardena</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="route-vehicle">Vehicle</label>
                    <select id="route-vehicle" name="vehicle">
                        <option value="">Select vehicle</option>
                        <option value="EC-1001">EC-1001</option>
                        <option value="EC-1002">EC-1002</option>
                        <option value="EC-1003">EC-1003</option>
                        <option value="EC-1004">EC-1004</option>
                    </select>
                </div>

                <div class="form-group route-status-field">
                    <label for="route-manage-status">Route Status</label>
                    <select id="route-manage-status" name="status" required>
                        <option value="PLANNED">PLANNED</option>
                        <option value="ASSIGNED">ASSIGNED</option>
                        <option value="IN_PROGRESS">IN PROGRESS</option>
                        <option value="COMPLETED">COMPLETED</option>
                    </select>
                </div>
            </div>

            <p class="route-form-error officer-form-error" role="alert" data-route-form-error hidden></p>

            <div class="route-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-route-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-route-submit>Save Changes</button>
            </div>
        </form>
    </section>
</div>

<div class="route-toast officer-toast" role="status" aria-live="polite" data-route-toast hidden>
    Route changes saved in this browser.
</div>
