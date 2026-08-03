<section class="area-schedules-page">

    <div class="page-toolbar">

        <div>
            <h1>Area Collection Schedules</h1>

            <p>
                Assign collection dates and capacity limits for postal-code areas.
            </p>
        </div>

        <div class="toolbar-actions">
            <button type="button" class="primary-btn create-schedule-trigger" data-open-schedule-dialog>
                <span aria-hidden="true">+</span>
                Create Schedule
            </button>
        </div>

    </div>

    <section class="scheduled-areas-card">

        <div class="scheduled-areas-header">

            <div>

                <h2>
                    Scheduled Area Dates
                </h2>

                <p>
                    View and manage collection schedules for the selected campaign.
                </p>

            </div>

            <div class="campaign-filter">

                <label for="campaign-filter">
                    Campaign
                </label>

                <select id="campaign-filter">

                    <option>
                        All Campaigns
                    </option>

                    <option>
                        Colombo Municipal E-Waste Campaign — 8/2026
                    </option>

                </select>

            </div>

        </div>

        <div class="schedule-table-wrapper">

            <table class="schedule-table">

                <thead>

                    <tr>

                        <th>Schedule ID</th>

                        <th>Campaign</th>

                        <th>Area</th>

                        <th>Postal Code</th>

                        <th>Collection Date</th>

                        <th>Cut-off Date</th>

                        <th>Requests</th>

                        <th>Capacity</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody data-schedule-table-body>

                    <tr>

                        <td>SCH-0010</td>

                        <td>Colombo Municipal E-Waste Campaign<br>8/2026</td>

                        <td>Wellawatte</td>

                        <td>11100</td>

                        <td>30 Aug 2026</td>

                        <td>20 Aug 2026</td>

                        <td>0</td>

                        <td>30</td>

                        <td>

                            <span class="status open">
                                OPEN
                            </span>

                        </td>

                        <td>

                            <button type="button" class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>SCH-0009</td>

                        <td>Colombo Municipal E-Waste Campaign<br>8/2026</td>

                        <td>Rajagiriya</td>

                        <td>10800</td>

                        <td>23 Aug 2026</td>

                        <td>13 Aug 2026</td>

                        <td>3</td>

                        <td>35</td>

                        <td>

                            <span class="status open">
                                OPEN
                            </span>

                        </td>

                        <td>

                            <button type="button" class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>SCH-0008</td>

                        <td>Colombo Municipal E-Waste Campaign<br>8/2026</td>

                        <td>Narahenpita</td>

                        <td>10600</td>

                        <td>19 Aug 2026</td>

                        <td>10 Aug 2026</td>

                        <td>2</td>

                        <td>55</td>

                        <td>

                            <span class="status open">
                                OPEN
                            </span>

                        </td>

                        <td>

                            <button type="button" class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>

                    <tr>

                        <td>SCH-0007</td>

                        <td>Colombo Municipal E-Waste Campaign<br>8/2026</td>

                        <td>Kollupitiya</td>

                        <td>10500</td>

                        <td>16 Aug 2026</td>

                        <td>10 Aug 2026</td>

                        <td>3</td>

                        <td>45</td>

                        <td>

                            <span class="status open">
                                OPEN
                            </span>

                        </td>

                        <td>

                            <button type="button" class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</section>

<div class="schedule-dialog officer-dialog" data-schedule-dialog hidden>
    <section
        class="schedule-dialog-card officer-dialog-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="schedule-dialog-title"
    >
        <div class="schedule-dialog-header officer-dialog-header">
            <div>
                <span class="dialog-eyebrow" data-schedule-dialog-eyebrow>New area schedule</span>
                <h2 id="schedule-dialog-title" data-schedule-dialog-title>Create Schedule</h2>
                <p data-schedule-dialog-description>Assign a collection date and request capacity to a postal-code area.</p>
            </div>

            <button
                type="button"
                class="schedule-dialog-close officer-dialog-close"
                aria-label="Close schedule form"
                data-close-schedule-dialog
            >×</button>
        </div>

        <form class="schedule-create-form" data-schedule-form>
            <div class="form-group">
                <label for="schedule-campaign">Monthly Campaign</label>
                <select id="schedule-campaign" name="campaign" required>
                    <option value="">Select campaign</option>
                    <option
                        value="colombo-2026-08"
                        data-campaign-name="Colombo Municipal E-Waste Campaign"
                        data-campaign-period="2026-08"
                    >Colombo Municipal E-Waste Campaign — 8/2026</option>
                </select>
            </div>

            <div class="schedule-form-grid">
                <div class="form-group">
                    <label for="schedule-area">Postal-code Area</label>
                    <select id="schedule-area" name="area" required>
                        <option value="">Select area</option>
                        <option value="11100" data-area-name="Wellawatte">Wellawatte — 11100</option>
                        <option value="10800" data-area-name="Rajagiriya">Rajagiriya — 10800</option>
                        <option value="10600" data-area-name="Narahenpita">Narahenpita — 10600</option>
                        <option value="10500" data-area-name="Kollupitiya">Kollupitiya — 10500</option>
                        <option value="00800" data-area-name="Borella">Borella — 00800</option>
                        <option value="00700" data-area-name="Cinnamon Gardens">Cinnamon Gardens — 00700</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="schedule-capacity">Maximum Public Requests</label>
                    <input
                        type="number"
                        id="schedule-capacity"
                        name="capacity"
                        value="30"
                        min="1"
                        max="500"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="schedule-cutoff">Request Cut-off Date</label>
                    <input type="date" id="schedule-cutoff" name="cutoff" required>
                </div>

                <div class="form-group">
                    <label for="schedule-collection-date">Collection Date</label>
                    <input
                        type="date"
                        id="schedule-collection-date"
                        name="collection_date"
                        required
                    >
                </div>

                <div class="form-group schedule-status-field">
                    <label for="schedule-status">Schedule Status</label>
                    <select id="schedule-status" name="status" required>
                        <option value="OPEN">OPEN</option>
                        <option value="CLOSED">CLOSED</option>
                    </select>
                    <small>New schedules normally start open. FULL is calculated from request capacity.</small>
                </div>
            </div>

            <p class="schedule-form-error officer-form-error" role="alert" data-schedule-form-error hidden></p>

            <div class="schedule-dialog-actions officer-dialog-actions">
                <button type="button" class="secondary-btn" data-close-schedule-dialog>Cancel</button>
                <button type="submit" class="primary-btn" data-schedule-submit>Create Schedule</button>
            </div>
        </form>
    </section>
</div>

<div class="schedule-toast officer-toast" role="status" aria-live="polite" data-schedule-toast hidden>
    <span data-schedule-toast-message>Schedule created and added to the list.</span>
</div>
