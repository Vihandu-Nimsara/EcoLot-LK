<section class="pickup-request-page">
    <div class="page-toolbar">
        <div>
            <h1>New Pickup Request</h1>
            <p>Enter pickup details and use the category guide to classify your e-waste correctly.</p>
        </div>
    </div>

    <form data-pickup-request-form>
        <div class="pickup-form-layout">
            <div class="pickup-form-sections">
                <section class="surface-card">
                    <div class="card-heading">
                        <div>
                            <h2>Pickup Details</h2>
                            <p>Confirm where and when the items should be collected.</p>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="postal-code">Postal Code Area</label>
                        <input id="postal-code" name="postal_code" type="text" value="Pannipitiya (10230)" readonly>
                    </div>
                    <div class="form-field">
                        <label for="collection-date">Available Collection Date</label>
                        <input id="collection-date" name="collection_date" type="date" required>
                    </div>
                    <div class="form-field">
                        <label for="pickup-address">Pickup Address</label>
                        <textarea id="pickup-address" name="pickup_address" rows="2" readonly><?= htmlspecialchars($user_address ?? '123 Main Street, Pannipitiya') ?></textarea>
                    </div>
                </section>

                <section class="surface-card">
                    <div class="card-heading">
                        <div>
                            <h2>E-waste Classification</h2>
                            <p>Describe the items and their condition.</p>
                        </div>
                    </div>

                    <div class="form-field">
                        <label for="waste-category">E-Waste Category</label>
                        <select id="waste-category" name="category" required>
                            <option value="">Select category</option>
                            <option value="domestic">Domestic E-Waste</option>
                            <option value="automobile">Automobile E-Waste</option>
                            <option value="office">Office E-Waste</option>
                            <option value="industrial">Industrial E-Waste</option>
                            <option value="medical">Medical E-Waste</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="other-items">Other E-waste Items</label>
                        <input id="other-items" name="other_items" type="text">
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="item-quantity">Total Item Quantity</label>
                            <input id="item-quantity" name="quantity" type="number" min="1" required>
                        </div>
                        <div class="form-field">
                            <label for="estimated-weight">Estimated Weight (kg)</label>
                            <input id="estimated-weight" name="estimated_weight" type="number" min="1" required>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="item-condition">Condition</label>
                        <select id="item-condition" name="condition" required>
                            <option value="working">Working</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="condition-note">Damage or Unusual Condition Note</label>
                        <textarea id="condition-note" name="condition_note" rows="2" placeholder="Example: cracked screen or swollen battery"></textarea>
                    </div>
                </section>

                <div class="form-actions">
                    <a href="<?= $basePath ?>/user/dashboard" class="secondary-btn">Cancel</a>
                    <button type="submit" class="primary-btn">Submit Request</button>
                </div>
            </div>

            <aside class="surface-card category-guide-card">
                <div class="card-heading">
                    <div>
                        <h2>E-waste Category Guide</h2>
                        <p>Select a category to review accepted items.</p>
                    </div>
                </div>

                <div class="category-guide-tabs" role="tablist" aria-label="E-waste categories">
                    <button type="button" class="category-guide-tab active" data-guide-category="domestic">Domestic</button>
                    <button type="button" class="category-guide-tab" data-guide-category="automobile">Automobile</button>
                    <button type="button" class="category-guide-tab" data-guide-category="office">Office</button>
                    <button type="button" class="category-guide-tab" data-guide-category="industrial">Industrial</button>
                    <button type="button" class="category-guide-tab" data-guide-category="medical">Medical</button>
                    <button type="button" class="category-guide-tab hazardous" data-guide-category="hazardous">Hazardous</button>
                </div>

                <div class="category-guide-content">
                    <ul class="category-item-list active" data-guide-list="domestic">
                        <li>LCD TVs and monitors</li><li>LED lamps</li><li>Computer hardware</li>
                        <li>Radios and DVD players</li><li>Electric ovens and microwaves</li>
                        <li>Fans and hair dryers</li><li>Mobile phones, laptops, and chargers</li>
                    </ul>
                    <ul class="category-item-list" data-guide-list="automobile">
                        <li>Dashboard electronics</li><li>LED headlights</li><li>Hybrid and EV batteries</li>
                        <li>Motors and alternators</li><li>Switches, sensors, cables, and relays</li>
                    </ul>
                    <ul class="category-item-list" data-guide-list="office">
                        <li>Photocopiers</li><li>UPS units and batteries</li><li>Printers and scanners</li>
                        <li>Projectors and speakers</li><li>Network and access-control equipment</li>
                    </ul>
                    <ul class="category-item-list" data-guide-list="industrial">
                        <li>Inverters and VFDs</li><li>CNC machines</li><li>Automation equipment</li>
                        <li>Power supply units</li><li>Solar power equipment</li>
                    </ul>
                    <ul class="category-item-list" data-guide-list="medical">
                        <li>Ventilators and insulin pumps</li><li>Hearing aids and electric wheelchairs</li>
                        <li>Oximeters and thermometers</li><li>Ultrasound equipment</li>
                    </ul>
                    <ul class="category-item-list hazardous" data-guide-list="hazardous">
                        <li>CFL bulbs, mercury lamps, and tube lights</li><li>Leaking batteries</li>
                        <li>CRT televisions and monitors</li><li>Radioactive or biohazardous equipment</li>
                    </ul>
                </div>
            </aside>
        </div>
    </form>
</section>
