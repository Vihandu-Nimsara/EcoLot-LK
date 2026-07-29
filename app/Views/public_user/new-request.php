<div class="content">
  <p class="breadcrumb"><a href="<?= $basePath ?>/user/dashboard">Dashboard</a> &nbsp;›&nbsp; New Pickup Request</p>
  <h1 class="title">New Pickup Request</h1>
  <p class="subtitle">Please cross-check your items with the official University of Kelaniya EDIC reference guide on the right before choosing a category.</p>

  <form>
    <div class="layout-grid">

      <!-- Left: form fields -->
      <div class="form-column">
        <div class="card">
          <h3 class="card-title">1. Pickup Details</h3>
          <div class="field">
            <label>Postal Code Area</label>
            <input 
              type="text" 
              name="postal_code" 
              value="Pannipitiya (10230) " 
              readonly>
          </div>
          <div class="field">
            <label>Available Collection Date</label>
            <input type="date" required>
          </div>
          <div class="field">
            <label>Pickup Address</label>
            <textarea 
              name="pickup_address" 
              rows="2" 
              readonly
            ><?php echo htmlspecialchars($user_address ?? '123 Main Street, Pannipitiya'); ?></textarea>
          </div>
        </div>

        <div class="card">
          <h3 class="card-title">2. E-waste Items Classification</h3>
          <div class="field">
            <label>E-Waste Category (select matching classification based on the guide)</label>
            <select required>
              <option value="">Select category</option>
              <option value="domestic">Domestic E-Waste</option>
              <option value="automobile">Automobile E-Waste</option>
              <option value="office">Office E-Waste</option>
              <option value="industrial">Industrial E-Waste</option>
              <option value="medical">Medical E-Waste</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="field">
            <label>Other E-wastes</label>
            <input type="text" min="1">
          </div>

          <div class="field">
            <label>Total Item Quantity</label>
            <input type="number" min="1" placeholder="Enter quantity" required>
          </div>
          <div class="field">
            <label>Estimated E-waste weight</label>
            <input type="number" min="1" placeholder="Enter weight" required>
          </div>

          <div class="field">
            <label>Condition</label>
            <select required>
              <option value="working">Working</option>
              <option value="Damaged">Damaged</option>
            </select>
          </div>

          <div class="field">
            <label>Damaged/Unusual Note</label>
            <textarea rows="2" placeholder="e.g. Cracked Screen , Swollen Batteries" required></textarea>
          </div>
        </div>

        <div class="action-bar">
          <a href="<?= $basePath ?>/user/dashboard"><button type="button" class="btn">Cancel</button></a>
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </div>

      <!-- Right: interactive reference guide -->
      <div class="card guide-card">
        <h3 style="margin:0 0 8px;font-size:15px;">E-waste Categories Guide</h3>
       
        <div class="guide-tabs">
          <button type="button" class="guide-tab-btn active" onclick="switchGuideTab(this,'domestic')">Domestic</button>
          <button type="button" class="guide-tab-btn" onclick="switchGuideTab(this,'automobile')">Automobile</button>
          <button type="button" class="guide-tab-btn" onclick="switchGuideTab(this,'office')">Office</button>
          <button type="button" class="guide-tab-btn" onclick="switchGuideTab(this,'industrial')">Industrial</button>
          <button type="button" class="guide-tab-btn" onclick="switchGuideTab(this,'medical')">Medical</button>
          <button type="button" class="guide-tab-btn not-collected" onclick="switchGuideTab(this,'not-collected')">Hazardous Waste ⚠️</button>
        </div>

        <div class="guide-content">
          <ul id="guide-domestic" class="guide-list active">
            <li>LCD TVs / Monitors</li>
            <li>LED lamps</li>
            <li>Computer hardware</li>
            <li>Radios, DVD players</li>
            <li>Electric ovens / Microwave ovens</li>
            <li>Fans / Hair dryers</li>
            <li>Electronic exercise equipment</li>
            <li>Mobile phones / Laptops / Chargers</li>
            <li>Bluetooth speakers / Earbuds</li>
            <li>Cameras / CCTV equipment</li>
          </ul>

          <ul id="guide-automobile" class="guide-list">
            <li>Dashboard electronics</li>
            <li>LED headlights</li>
            <li>Hybrid batteries / EV batteries</li>
            <li>Motors / Alternators</li>
            <li>Switches</li>
            <li>Sensors</li>
            <li>Cables</li>
            <li>Relays</li>
            <li>Heaters</li>
          </ul>

          <ul id="guide-office" class="guide-list">
            <li>Photocopy machines</li>
            <li>UPS units / UPS batteries</li>
            <li>Printers / Scanners</li>
            <li>Projectors / Speakers</li>
            <li>Access control equipment (fingerprint machines)</li>
            <li>Network equipment</li>
            <li>Telephone / Fax / Intercom equipment</li>
            <li>Barcode readers / POS machines</li>
          </ul>

          <ul id="guide-industrial" class="guide-list">
            <li>Inverters / VFDs</li>
            <li>CNC machines</li>
            <li>Elevator electronic components</li>
            <li>Sign board displays</li>
            <li>Air conditioners</li>
            <li>Automation equipment</li>
            <li>Power supply units</li>
            <li>Vending machine hardware</li>
            <li>Solar power equipment</li>
          </ul>

          <ul id="guide-medical" class="guide-list">
            <li>Ventilators / Insulin pumps</li>
            <li>Hearing aids / Electric wheelchairs</li>
            <li>Oximeters / Electronic thermometers</li>
            <li>Ultrasound machines and probes</li>
            <li>Centrifuges / Spectrophotometers</li>
            <li>Electronic medical record systems</li>
            <li>Glucometers / Weight scales</li>
          </ul>

          <ul id="guide-not-collected" class="guide-list unacceptable">
            <li>CFL bulbs / Tube lights / Mercury lamps</li>
            <li>Refrigerators / Washing machines</li>
            <li>CRT TVs / CRT monitors</li>
            <li>Leaking batteries</li>
            <li>CT scanners / X-ray equipment</li>
            <li>Smoke detectors / Radioactive sources</li>
            <li>Items containing mercury, cadmium, phosphorous</li>
            <li>Biohazardous equipment</li>
          </ul>
        </div>

      </div>
    </div>
  </form>
</div>
