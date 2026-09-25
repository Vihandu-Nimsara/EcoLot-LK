<section class="routes-page">
    <div class="page-toolbar">
        <div>
            <h1>Collection Assignments</h1>
            <p>Assign one Collector and Vehicle pair to eligible Area Collection Schedules.</p>
        </div>
    </div>

    <section class="route-filter-card compact-filter-card">
        <form class="route-filter-form" data-assignment-filter-form>
            <div class="route-filter-grid">
                <div class="form-group">
                    <label for="campaign">Campaign</label>
                    <select id="campaign" name="campaign">
                        <option value="">All Campaigns</option>
                        <option value="oct-2026">October 2026 E-Waste Collection Campaign</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="schedule">Area Collection Schedule</label>
                    <select id="schedule" name="schedule">
                        <option value="">All Schedules</option>
                        <option value="SCH-1001">SCH-1001 — Colombo 03 — 10 Oct 2026</option>
                        <option value="SCH-1002">SCH-1002 — Wellawatte — 15 Oct 2026</option>
                        <option value="SCH-1003">SCH-1003 — Dehiwala — 20 Oct 2026</option>
                        <option value="SCH-1004">SCH-1004 — Moratuwa — 27 Oct 2026</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assignment-status">Schedule Status</label>
                    <select id="assignment-status" name="schedule_status">
                        <option value="">All Statuses</option>
                        <option value="CLOSED">Closed</option>
                        <option value="ASSIGNED">Assigned</option>
                        <option value="IN_PROGRESS">In Progress</option>
                        <option value="COMPLETED">Completed</option>
                    </select>
                </div>
            </div>

            <div class="filter-actions">
                <button type="reset" class="secondary-btn">Clear</button>
                <button type="submit" class="primary-btn">Apply Filters</button>
            </div>
        </form>
    </section>

    <section class="routes-list-card">
        <div class="routes-list-heading">
            <div>
                <h2>Schedule Assignments</h2>
                <p>Each Area Collection Schedule can have one active Collector and Vehicle assignment.</p>
                <span class="route-result-count officer-result-count" data-assignment-result-count role="status" aria-live="polite">4 schedules shown</span>
            </div>
        </div>

        <div class="routes-table-wrapper">
            <table class="routes-table">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Requests</th>
                        <th>Collector</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-assignments-table-body>
                    <tr data-assignment-key="SCH-1001" data-campaign="oct-2026" data-schedule="SCH-1001" data-schedule-status="CLOSED">
                        <td>SCH-1001</td><td>Colombo 03</td><td>10 Oct 2026</td><td>12</td>
                        <td><span class="not-assigned">Not Assigned</span></td>
                        <td><span class="not-assigned">Not Assigned</span></td>
                        <td><span class="route-status planned">CLOSED</span></td>
                        <td><button type="button" class="route-action-btn primary-action">Assign</button></td>
                    </tr>
                    <tr data-assignment-key="SCH-1002" data-campaign="oct-2026" data-schedule="SCH-1002" data-schedule-status="ASSIGNED">
                        <td>SCH-1002</td><td>Wellawatte</td><td>15 Oct 2026</td><td>8</td>
                        <td>Nuwan Silva</td><td>EC-1002</td>
                        <td><span class="route-status assigned">ASSIGNED</span></td>
                        <td><button type="button" class="route-action-btn secondary-action">Manage</button></td>
                    </tr>
                    <tr data-assignment-key="SCH-1003" data-campaign="oct-2026" data-schedule="SCH-1003" data-schedule-status="IN_PROGRESS">
                        <td>SCH-1003</td><td>Dehiwala</td><td>20 Oct 2026</td><td>10</td>
                        <td>Sunil Perera</td><td>EC-1001</td>
                        <td><span class="route-status in-progress">IN PROGRESS</span></td>
                        <td><button type="button" class="route-action-btn secondary-action">Manage</button></td>
                    </tr>
                    <tr data-assignment-key="SCH-1004" data-campaign="oct-2026" data-schedule="SCH-1004" data-schedule-status="COMPLETED">
                        <td>SCH-1004</td><td>Moratuwa</td><td>27 Oct 2026</td><td>6</td>
                        <td>Chamara Fernando</td><td>EC-1003</td>
                        <td><span class="route-status completed">COMPLETED</span></td>
                        <td><button type="button" class="route-action-btn secondary-action">View</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="routes-empty-state officer-empty-state" data-assignments-empty-state hidden>
            No collection assignments match the selected filters.
        </div>
    </section>
</section>

<div class="route-dialog officer-dialog" data-assignment-dialog hidden>
    <section class="route-dialog-card officer-dialog-card" role="dialog" aria-modal="true" aria-labelledby="assignment-dialog-title">
        <div class="route-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-assignment-dialog-eyebrow>Collection assignment</span>
                <h2 id="assignment-dialog-title" data-assignment-dialog-title>Manage Assignment</h2>
                <p data-assignment-dialog-description>Assign collection resources to this Area Collection Schedule.</p>
            </div>
            <button type="button" class="route-dialog-close officer-dialog-close" aria-label="Close assignment details" data-close-assignment-dialog>×</button>
        </div>

        <div class="route-summary-grid">
            <div><span>Schedule</span><strong data-assignment-schedule></strong></div>
            <div><span>Area</span><strong data-assignment-area></strong></div>
            <div><span>Collection Date</span><strong data-assignment-date></strong></div>
            <div><span>Requests</span><strong data-assignment-requests></strong></div>
        </div>

        <form class="route-manage-form" data-assignment-manage-form>
            <div class="route-resource-grid">
                <div class="form-group">
                    <label for="assignment-collector">Collector</label>
                    <select id="assignment-collector" name="collector">
                        <option value="">Select collector</option>
                        <option value="Nuwan Silva">Nuwan Silva</option>
                        <option value="Sunil Perera">Sunil Perera</option>
                        <option value="Chamara Fernando">Chamara Fernando</option>
                        <option value="Malini Jayawardena">Malini Jayawardena</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assignment-vehicle">Vehicle</label>
                    <select id="assignment-vehicle" name="vehicle">
                        <option value="">Select vehicle</option>
                        <option value="EC-1001">EC-1001</option>
                        <option value="EC-1002">EC-1002</option>
                        <option value="EC-1003">EC-1003</option>
                        <option value="EC-1004">EC-1004</option>
                    </select>
                </div>
                <div class="form-group route-status-field">
                    <label for="assignment-manage-status">Schedule Status</label>
                    <select id="assignment-manage-status" name="status" required>
                        <option value="CLOSED">CLOSED</option>
                        <option value="ASSIGNED">ASSIGNED</option>
                        <option value="IN_PROGRESS">IN PROGRESS</option>
                        <option value="COMPLETED">COMPLETED</option>
                    </select>
                </div>
            </div>

            <p class="route-form-error officer-form-error" role="alert" data-assignment-form-error hidden></p>
            <div class="route-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-assignment-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-assignment-submit>Save Changes</button>
            </div>
        </form>
    </section>
</div>

<div class="route-toast officer-toast" role="status" aria-live="polite" data-assignment-toast hidden>
    Assignment changes saved in this browser.
</div>
