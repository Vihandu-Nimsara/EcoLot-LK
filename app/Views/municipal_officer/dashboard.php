<section class="dashboard-page">

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon campaign">📅</div>
            <div class="stat-info">
                <span>Active Campaigns</span>
                <h2>1</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon request">📥</div>
            <div class="stat-info">
                <span>Pending Requests</span>
                <h2>1</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon route">🚚</div>
            <div class="stat-info">
                <span>Planned Routes</span>
                <h2>1</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon verify">✓</div>
            <div class="stat-info">
                <span>Pending Verifications</span>
                <h2>1</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon lot">♻</div>
            <div class="stat-info">
                <span>Open E-Lots</span>
                <h2>0</h2>
            </div>
        </div>
    </div>

    <section class="schedule-section">
        <div class="section-header">
            <div>
                <h2>Upcoming Collection Schedules</h2>
                <p>Review upcoming collection dates, request load, and availability for your council.</p>
            </div>
            <a
                href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/officer/area-schedules"
                class="primary-btn dashboard-schedule-link"
            >
                Manage Schedules
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Campaign</th>
                        <th>Postal Code</th>
                        <th>Area</th>
                        <th>Collection Date</th>
                        <th>Requests</th>
                        <th>Capacity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>SCH0007</td>
                        <td>
                            Colombo Municipal E-Waste Campaign<br>8/2026
                        </td>
                        <td>10500</td>
                        <td>Kollupitiya</td>
                        <td>2026-08-16</td>
                        <td>3</td>
                        <td>45</td>
                        <td><span class="status open">OPEN</span></td>
                    </tr>
                    <tr>
                        <td>SCH0008</td>
                        <td>
                            Colombo Municipal E-Waste Campaign<br>8/2026
                        </td>
                        <td>10600</td>
                        <td>Narahenpita</td>
                        <td>2026-08-19</td>
                        <td>2</td>
                        <td>55</td>
                        <td><span class="status open">OPEN</span></td>
                    </tr>
                    <tr>
                        <td>SCH0009</td>
                        <td>
                            Colombo Municipal E-Waste Campaign<br>8/2026
                        </td>
                        <td>10800</td>
                        <td>Rajagiriya</td>
                        <td>2026-08-23</td>
                        <td>3</td>
                        <td>35</td>
                        <td><span class="status open">OPEN</span></td>
                    </tr>
                    <tr>
                        <td>SCH0010</td>
                        <td>
                            Colombo Municipal E-Waste Campaign<br>8/2026
                        </td>
                        <td>11100</td>
                        <td>Wellawatte</td>
                        <td>2026-08-30</td>
                        <td>0</td>
                        <td>30</td>
                        <td><span class="status open">OPEN</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</section>
