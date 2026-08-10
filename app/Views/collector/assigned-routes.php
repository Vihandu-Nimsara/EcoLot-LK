<section class="assigned-routes-page">
    <div class="page-toolbar">
        <div>
            <h1>Today's Assigned Routes</h1>
            <p>Review scheduled collection zones and confirm completed pickups.</p>
        </div>
        <span class="updated-status">Updated 5 minutes ago</span>
    </div>

    <div class="route-list">
        <?php foreach ([
            ['id' => 'kol', 'zone' => 'Kollupitiya', 'address' => 'Galle Road — Liberty Plaza Sector', 'status' => 'Scheduled', 'class' => '', 'weight' => '18.4 kg', 'time' => '8:42 AM'],
            ['id' => 'nar', 'zone' => 'Narahenpita', 'address' => 'Kirimandala Mawatha — Hospital District', 'status' => 'Afternoon', 'class' => '', 'weight' => '22.1 kg', 'time' => '9:15 AM'],
            ['id' => 'raj', 'zone' => 'Rajagiriya', 'address' => 'Parliament Road — Ethul Kotte Junction', 'status' => 'In Progress', 'class' => 'in-progress', 'weight' => '15.7 kg', 'time' => '10:03 AM'],
            ['id' => 'wel', 'zone' => 'Wellawatte', 'address' => 'W. A. Silva Mawatha — Canal Side', 'status' => 'Scheduled', 'class' => '', 'weight' => '9.9 kg', 'time' => '10:40 AM'],
        ] as $route): ?>
            <article
                class="assigned-route-card"
                data-route-id="<?= $route['id'] ?>"
                data-route-weight="<?= $route['weight'] ?>"
                data-route-time="<?= $route['time'] ?>"
            >
                <div class="route-summary">
                    <div class="route-title-row">
                        <h2><?= $route['zone'] ?></h2>
                        <span class="route-status <?= $route['class'] ?>"><?= $route['status'] ?></span>
                        <span class="hazard-indicator" data-hazard-indicator="<?= $route['id'] ?>" aria-label="Hazard flagged">🚩</span>
                    </div>
                    <p><?= $route['address'] ?></p>
                </div>

                <div class="route-actions">
                    <button type="button" class="primary-btn" data-confirm-pickup="<?= $route['id'] ?>">Confirm Pickup</button>
                    <button type="button" class="secondary-btn" data-view-route="<?= $route['id'] ?>">View Details</button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <aside class="urgent-notice" aria-live="polite">
        <strong>Urgent Notice</strong>
        <p>Road closure on Flower Road. Sector B routes are redirected via Green Path.</p>
    </aside>
</section>

<div class="collector-modal-overlay" data-route-modal hidden>
    <section class="collector-modal-card" role="dialog" aria-modal="true" aria-labelledby="route-modal-title">
        <button type="button" class="modal-close" aria-label="Close route details" data-close-route-modal>×</button>
        <h2 id="route-modal-title" data-route-zone></h2>
        <dl class="route-details">
            <div><dt>Estimated Weight</dt><dd data-route-modal-weight></dd></div>
            <div><dt>Scheduled Time</dt><dd data-route-modal-time></dd></div>
        </dl>
        <button type="button" class="danger-btn" data-mark-hazard>Mark as Hazard</button>
    </section>
</div>
