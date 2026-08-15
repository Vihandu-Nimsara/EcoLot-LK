<section class="initial-request-page">
    <div class="form-card">
        <h1>Initial Request Form</h1>
        <p class="form-subtitle">Please provide collector assignment details.</p>

        <div class="form-field">
            <label for="collectorId">Collector ID</label>
            <select id="collectorId">
                <option value="">Select a Collector ID</option>
            </select>
            <small>Select the specific ID mapping to the collector.</small>
        </div>

        <div class="form-field">
            <label for="postalCode">Postal Code</label>
            <input type="text" id="postalCode" placeholder="Auto-filled after selecting Collector ID" readonly>
        </div>

        <div class="form-field">
            <label for="collectionDate">Collection Date</label>
            <select id="collectionDate">
                <option value="">Select a Date</option>
            </select>
        </div>

        <button type="button" class="primary-btn full-width" id="submitRequestBtn">Submit</button>
    </div>
</section>