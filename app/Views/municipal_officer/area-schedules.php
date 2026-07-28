<section class="area-schedules-page">

    <div class="page-toolbar">

        <div>
            <h1>Area Collection Schedule</h1>

            <p>
                Assign collection dates and capacity limits for postal-code areas.
            </p>
        </div>


    </div>


    <section class="schedule-form-card">

        <div class="card-heading">
            <h2>Create Collection Schedule</h2>

            <p>
                Assign a collection date and request capacity to a postal-code area.
            </p>
        </div>


        <form class="schedule-form">


            <div class="form-group full-width">

                <label for="campaign">
                    Monthly Campaign
                </label>

                <select id="campaign" name="campaign">
                    <option value="">
                        Select campaign
                    </option>

                    <option value="1">
                        July 2026 Collection Campaign
                    </option>
                </select>

            </div>


            <div class="form-grid">


                <div class="form-group">

                    <label for="area">
                        Postal-code Area
                    </label>

                    <select id="area" name="area">

                        <option value="">
                            Select area
                        </option>

                        <option value="11100">
                            Wellawatte
                        </option>

                        <option value="10800">
                            Rajagiriya
                        </option>

                        <option value="10600">
                            Narahenpita
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="cutoff">
                        Request Cut-off Date
                    </label>

                    <input
                        type="date"
                        id="cutoff"
                        name="cutoff"
                    >

                </div>


                <div class="form-group">

                    <label for="collection_date">
                        Collection Date
                    </label>

                    <input
                        type="date"
                        id="collection_date"
                        name="collection_date"
                    >

                </div>


                <div class="form-group">

                    <label for="capacity">
                        Maximum Public Requests
                    </label>

                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        value="100"
                        min="1"
                    >

                </div>


                <div class="form-group">

                    <label for="status">
                        Initial Status
                    </label>

                    <select id="status" name="status">

                        <option value="OPEN">
                            OPEN
                        </option>

                        <option value="FULL">
                            FULL
                        </option>

                        <option value="CLOSED">
                            CLOSED
                        </option>

                    </select>

                </div>


            </div>


            <div class="form-actions">

                <button
                    type="reset"
                    class="secondary-btn">
                    Cancel
                </button>


                <button
                    type="submit"
                    class="primary-btn">
                    Create Schedule
                </button>

            </div>


        </form>

    </section>



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
                        July 2026 Campaign
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



                <tbody>


                    <tr>

                        <td>SCH-0010</td>

                        <td>July 2026 Campaign</td>

                        <td>Wellawatte QA Zone</td>

                        <td>11100</td>

                        <td>30 Jul 2026</td>

                        <td>20 Jul 2026</td>

                        <td>0</td>

                        <td>30</td>

                        <td>

                            <span class="status full">
                                FULL
                            </span>

                        </td>

                        <td>

                            <button class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>



                    <tr>

                        <td>SCH-0009</td>

                        <td>July 2026 Campaign</td>

                        <td>Rajagiriya QA Zone</td>

                        <td>10800</td>

                        <td>23 Jul 2026</td>

                        <td>20 Jul 2026</td>

                        <td>3</td>

                        <td>35</td>

                        <td>

                            <span class="status open">
                                OPEN
                            </span>

                        </td>

                        <td>

                            <button class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>



                    <tr>

                        <td>SCH-0008</td>

                        <td>July 2026 Campaign</td>

                        <td>Narahenpita QA Zone</td>

                        <td>10600</td>

                        <td>19 Jul 2026</td>

                        <td>20 Jul 2026</td>

                        <td>2</td>

                        <td>55</td>

                        <td>

                            <span class="status open">
                                OPEN
                            </span>

                        </td>

                        <td>

                            <button class="edit-btn">
                                Edit
                            </button>

                        </td>

                    </tr>


                </tbody>


            </table>


        </div>


    </section>


</section>
