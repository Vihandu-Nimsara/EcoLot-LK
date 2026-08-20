<?php
declare(strict_types=1);
?>

<section class="collector-elots" data-collector-page="elots">
    <header class="collector-page-heading">
        <div>
            <span class="collector-eyebrow">Verified collection output</span>
            <h1>My E-Lots</h1>
            <p>Create E-Lot drafts from verified collection items, update them before submission, and track their verification lifecycle.</p>
        </div>

        <button id="collector-create-elot" class="collector-primary-button" type="button">Create E-Lot</button>
    </header>

    <div id="elot-feedback" class="collector-feedback" role="status" aria-live="polite" hidden></div>

    <section class="collector-elot-summary" aria-label="E-Lot summary">
        <article>
            <span>Verified Items</span>
            <strong id="elot-verified-item-count">0</strong>
        </article>
        <article>
            <span>Draft E-Lots</span>
            <strong id="elot-draft-count">0</strong>
        </article>
        <article>
            <span>Pending Verification</span>
            <strong id="elot-pending-count">0</strong>
        </article>
        <article>
            <span>Open / Awarded</span>
            <strong id="elot-active-count">0</strong>
        </article>
    </section>

    <section class="collector-elot-card" aria-labelledby="verified-items-title">
        <div class="collector-section-header">
            <div>
                <h2 id="verified-items-title">Verified Items Pool</h2>
                <p>Only officer-verified collected items should become available for E-Lot creation.</p>
            </div>
            <span id="elot-available-weight" class="collector-count-pill">0.00 kg available</span>
        </div>

        <div class="collector-table-wrap">
            <table class="collector-table collector-table--verified-items">
                <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Category</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Actual Weight</th>
                        <th scope="col">Verified On</th>
                        <th scope="col">Availability</th>
                    </tr>
                </thead>
                <tbody id="collector-verified-item-rows"></tbody>
            </table>
        </div>
    </section>

    <section class="collector-elot-card" aria-labelledby="my-elots-title">
        <div class="collector-section-header collector-section-header--filters">
            <div>
                <h2 id="my-elots-title">My E-Lots</h2>
                <p>Drafts support full CRUD. Once submitted, E-Lots become read-only while awaiting Municipal Officer verification.</p>
            </div>

            <label class="collector-inline-filter">
                <span class="sr-only">Filter E-Lots by status</span>
                <select id="collector-elot-status-filter">
                    <option value="ALL">All statuses</option>
                    <option value="DRAFT">Draft</option>
                    <option value="PENDING_VERIFICATION">Pending Verification</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="OPEN_FOR_BIDDING">Open for Bidding</option>
                    <option value="AWARDED">Awarded</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </label>
        </div>

        <div class="collector-table-wrap">
            <table class="collector-table collector-table--elots">
                <thead>
                    <tr>
                        <th scope="col">E-Lot ID</th>
                        <th scope="col">Name</th>
                        <th scope="col">Category</th>
                        <th scope="col">Items</th>
                        <th scope="col">Total Weight</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody id="collector-elot-rows"></tbody>
            </table>
        </div>

        <div id="collector-elot-empty" class="collector-empty-state" hidden>
            <strong>No E-Lots found</strong>
            <span>Create a draft from the verified items pool or change the current filter.</span>
        </div>
    </section>
</section>

<div id="collector-elot-modal" class="collector-modal" hidden>
    <div class="collector-modal__backdrop" data-elot-modal-close></div>
    <section class="collector-modal__dialog collector-modal__dialog--elot" role="dialog" aria-modal="true" aria-labelledby="collector-elot-modal-title">
        <header class="collector-modal__header">
            <div>
                <span class="collector-eyebrow">E-Lot management</span>
                <h2 id="collector-elot-modal-title">Create E-Lot</h2>
                <p id="collector-elot-modal-description">Choose one category and combine verified items into an E-Lot draft.</p>
            </div>
            <button type="button" class="collector-modal__close" aria-label="Close E-Lot dialog" data-elot-modal-close>×</button>
        </header>
        <div id="collector-elot-modal-body" class="collector-modal__body"></div>
    </section>
</div>