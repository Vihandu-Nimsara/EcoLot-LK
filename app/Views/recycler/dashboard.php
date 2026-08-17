<section class="dashboard-page">

    <div class="stats-grid">
        <article class="stat-card">
            <div class="stat-icon eligible_e-lot">♻</div>
            <div class="stat-info">
                <span>Eligible Open E-Lots</span>
                <h2>4</h2>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon bids">🙋‍♂️</div>
            <div class="stat-info">
                <span>My Bids</span>
                <h2>8</h2>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon awarded">🏆</div>
            <div class="stat-info">
                <span>Awarded E-Lots</span>
                <h2>6</h2>
            </div>
        </article>

        <div class="stat-card">
            <div class="stat-icon awaiting">⏳</div>
            <div class="stat-info">
                <span>Awaiting Handover</span>
                <h2>2</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon handed">📦</div>
            <div class="stat-info">
                <span>Handed Over</span>
                <h2>1</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon processing">⚙️</div>
            <div class="stat-info">
                <span>Processing</span>
                <h2>2</h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon completed">🎉</div>
            <div class="stat-info">
                <span>Completed</span>
                <h2>1</h2>
            </div>
        </div>
    </div>

    <section class="compliance-summary" aria-label="Compliance summary">
        <div><span>Verification</span><strong>Verified</strong></div>
        <div><span>CEA Licence Status</span><strong>Valid until 2027-06-30</strong></div>
        <div><span>Approved Capabilities</span><strong>2</strong></div>
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/recycler/profile">View My Profile</a>
    </section>

    <section class="schedule-section">

        <div class="capability-card">

            <div class="section-header">

                <div>
                    <h2>Waste-handling Capabilities</h2>

                    <p>
                        EcoLot categories approved for your recycler profile.
                    </p>
                </div>

            </div>

            <div class="capability-table-wrapper">

                <table class="capability-table">

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>Demo Battery and Circuit Boards</td>

                            <td>
                                <span class="status op">
                                    Approved
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td>Demo Consumer Electronics</td>

                            <td>
                                <span class="status op">
                                    Approved
                                </span>
                            </td>
                        </tr>

                        <tr><td>Medical E-Waste</td><td><span class="status non-op">Pending</span></td></tr>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</section>
