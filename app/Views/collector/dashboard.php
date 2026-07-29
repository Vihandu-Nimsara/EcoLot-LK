<main>
    <div class="page-header">
        <h1>Good morning, Kasun Perera</h1>
        <p>Here are your assigned routes for today, April 24.</p>
    </div>

    <div class="section-row">
        <h2>Today's Assigned Routes</h2>
        <span class="updated-badge">Updated 5 min ago</span>
    </div>

    <article class="route-card" data-id="kol" data-weight="18.4 kg" data-time="8:42 AM">
        <div class="route-info">
            <h3>Kollupitiya QA Zone <span class="badge">SCHEDULED</span> <span class="flag-icon" data-flagid="kol" aria-label="Hazard flagged">🚩</span></h3>
            <p>📍 Galle Road — Liberty Plaza Sector</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" type="button" data-pickup-id="kol">✔ Confirm Pickup</button>
            <button class="btn-view" type="button" data-details-id="kol">👁 View Details</button>
        </div>
    </article>

    <article class="route-card" data-id="nar" data-weight="22.1 kg" data-time="9:15 AM">
        <div class="route-info">
            <h3>Narahenpita QA Zone <span class="badge">AFTERNOON</span> <span class="flag-icon" data-flagid="nar" aria-label="Hazard flagged">🚩</span></h3>
            <p>📍 Kirimandala Mawatha — Hospital District</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" type="button" data-pickup-id="nar">✔ Confirm Pickup</button>
            <button class="btn-view" type="button" data-details-id="nar">👁 View Details</button>
        </div>
    </article>

    <article class="route-card" data-id="raj" data-weight="15.7 kg" data-time="10:03 AM">
        <div class="route-info">
            <h3>Rajagiriya QA Zone <span class="badge in-progress">IN PROGRESS</span> <span class="flag-icon" data-flagid="raj" aria-label="Hazard flagged">🚩</span></h3>
            <p>📍 Parliament Road — Ethul Kotte Junction</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" type="button" data-pickup-id="raj">✔ Confirm Pickup</button>
            <button class="btn-view" type="button" data-details-id="raj">👁 View Details</button>
        </div>
    </article>

    <article class="route-card" data-id="wel" data-weight="9.9 kg" data-time="10:40 AM">
        <div class="route-info">
            <h3>Wellawatta QA Zone <span class="badge">SCHEDULED</span> <span class="flag-icon" data-flagid="wel" aria-label="Hazard flagged">🚩</span></h3>
            <p>📍 W. A. Silva Mawatha — Canal Side</p>
        </div>
        <div class="route-actions">
            <button class="btn-confirm" type="button" data-pickup-id="wel">✔ Confirm Pickup</button>
            <button class="btn-view" type="button" data-details-id="wel">👁 View Details</button>
        </div>
    </article>
</main>

<aside class="urgent-notice" aria-live="polite">
    <h4>Urgent Notice</h4>
    <p>Road closure on Flower Road. Sector B routes are redirected via Green Path.</p>
</aside>

<div class="modal-overlay" id="detailsModal" role="dialog" aria-modal="true" aria-labelledby="modalZoneName" hidden>
    <div class="modal-box">
        <button class="modal-close" type="button" data-close-details aria-label="Close details">✕</button>
        <h3 id="modalZoneName"></h3>
        <p><strong>Estimated weight:</strong> <span id="modalWeight"></span></p>
        <p><strong>Scheduled time:</strong> <span id="modalTime"></span></p>
        <button class="btn-hazard" type="button" data-mark-hazard>⚠ Mark as Hazard</button>
    </div>
</div>
