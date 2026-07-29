<section class="feedback-form-page">
    <div class="page-toolbar">
        <div>
            <h1>Feedback &amp; Support</h1>
            <p>Send a complaint, suggestion, or compliment to the EcoLot LK team.</p>
        </div>
    </div>

    <form class="surface-card feedback-form" data-feedback-form>
        <div class="form-field">
            <label for="feedback-type">Feedback Type</label>
            <select id="feedback-type" name="feedback_type" required>
                <option value="">Select type</option>
                <option value="complaint">Complaint</option>
                <option value="suggestion">Suggestion</option>
                <option value="compliment">Compliment</option>
            </select>
        </div>
        <div class="form-field">
            <label for="related-request">Related Request (optional)</label>
            <select id="related-request" name="related_request">
                <option value="">Select request</option>
                <option value="REQ-2024-00012">REQ-2024-00012</option>
                <option value="REQ-2024-00011">REQ-2024-00011</option>
            </select>
        </div>
        <div class="form-field">
            <label for="feedback-message">Message</label>
            <textarea id="feedback-message" name="message" rows="6" placeholder="Enter your message" required></textarea>
        </div>
        <div class="form-actions">
            <a href="<?= $basePath ?>/user/dashboard" class="secondary-btn">Cancel</a>
            <button type="submit" class="primary-btn">Submit Feedback</button>
        </div>
    </form>
</section>
