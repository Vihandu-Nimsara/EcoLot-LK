<div class="collector-modal-overlay request-detail-overlay" data-request-modal hidden>
    <section class="collector-modal-card request-detail-modal" role="dialog" aria-modal="true" aria-labelledby="request-detail-title">
        <button type="button" class="modal-close" aria-label="Close request details" data-close-request-modal>×</button>

        <div class="request-modal-header">
            <div>
                <span class="page-eyebrow">Waste collection request</span>
                <h2 id="request-detail-title" data-modal-request-id></h2>
                <p data-modal-request-address></p>
            </div>
            <span class="status-badge" data-modal-request-status></span>
        </div>

        <dl class="request-metadata-grid">
            <div><dt>Schedule</dt><dd data-modal-schedule-id></dd></div>
            <div><dt>Requested items</dt><dd data-modal-item-count></dd></div>
            <div><dt>Estimated weight</dt><dd data-modal-estimated-weight></dd></div>
            <div><dt>Collection result</dt><dd data-modal-pickup-result></dd></div>
        </dl>

        <div class="section-heading modal-section-heading">
            <div>
                <h3>Collected Item Details</h3>
                <p>Compare the request with the actual quantity and weight collected.</p>
            </div>
        </div>

        <div class="table-scroll item-table-scroll">
            <table class="workflow-table item-detail-table">
                <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Requested</th>
                        <th scope="col">Actual Quantity</th>
                        <th scope="col">Actual Weight (kg)</th>
                        <th scope="col">Result</th>
                    </tr>
                </thead>
                <tbody data-request-items-body></tbody>
            </table>
        </div>

        <div class="request-note-field">
            <label for="collectorRequestNote">Collection Note <span>(optional)</span></label>
            <textarea id="collectorRequestNote" rows="3" maxlength="500" data-collector-request-note placeholder="Add a note about this request or collection outcome..."></textarea>
            <div class="field-help-row">
                <small>This note applies to the overall request.</small>
                <small><span data-note-character-count>0</span>/500</small>
            </div>
        </div>

        <div class="request-validation-message" data-request-validation role="alert" hidden></div>

        <div class="modal-action-row">
            <button type="button" class="secondary-btn" data-close-request-modal>Close</button>
            <button type="button" class="primary-btn confirm-collection-btn" data-confirm-request>Confirm Collection</button>
        </div>
    </section>
</div>
