<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isEdit ? 'Edit' : 'New'; ?> Pickup Request · EcoLotLK</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/new-request.css">
</head>
<body>

<div class="layout">
  <!-- Include Sidebar Navigation -->
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="content">
    <p class="breadcrumb"><a href="index.php?route=dashboard">Dashboard</a> &nbsp;›&nbsp; <?php echo $isEdit ? 'Edit' : 'New'; ?> Pickup Request</p>
    <h1 class="title"><?php echo $isEdit ? 'Edit' : 'New'; ?> Pickup Request</h1>
    <p class="subtitle">Please cross-check your items with the official University of Kelaniya EDIC reference guide on the right before choosing a category.</p>

    <form method="POST" action="index.php?route=new-request-submit">
      <!-- Hidden ID input for updating requests -->
      <input type="hidden" name="id" value="<?php echo htmlspecialchars($request['id'] ?? ''); ?>">

      <div class="layout-grid">

        <!-- Left: form fields -->
        <div class="form-column">
          <div class="card">
            <h3 class="card-title">1. Pickup Details</h3>
            <div class="field">
              <label>Postal Code Area</label>
              <select name="postal_code_area" required>
                <option value="">Select area</option>
                <option value="Colombo 05" <?php echo (isset($request['postal_code_area']) && $request['postal_code_area'] === 'Colombo 05') ? 'selected' : ''; ?>>Colombo 05</option>
                <option value="Pannipitiya (10230)" <?php echo (isset($request['postal_code_area']) && $request['postal_code_area'] === 'Pannipitiya (10230)') ? 'selected' : ''; ?>>Pannipitiya (10230)</option>
              </select>
            </div>
            <div class="field">
              <label>Available Collection Date</label>
              <input type="date" name="collection_date" value="<?php echo htmlspecialchars($request['collection_date'] ?? ''); ?>" required>
            </div>
            <div class="field">
              <label>Pickup Address</label>
              <textarea name="pickup_address" rows="2" placeholder="Enter full address" required><?php echo htmlspecialchars($request['pickup_address'] ?? 'No. 45, Galle Road, Colombo 06'); ?></textarea>
            </div>
          </div>

          <div class="card">
            <h3 class="card-title">2. E-waste Items Classification</h3>
            <div class="field">
              <label>E-Waste Category (select matching classification based on the guide)</label>
              <select name="category" id="categorySelect" required>
                <option value="">Select category</option>
                <option value="domestic" <?php echo (isset($request['category']) && $request['category'] === 'domestic') ? 'selected' : ''; ?>>Domestic E-Waste</option>
                <option value="automobile" <?php echo (isset($request['category']) && $request['category'] === 'automobile') ? 'selected' : ''; ?>>Automobile E-Waste</option>
                <option value="office" <?php echo (isset($request['category']) && $request['category'] === 'office') ? 'selected' : ''; ?>>Office E-Waste</option>
                <option value="industrial" <?php echo (isset($request['category']) && $request['category'] === 'industrial') ? 'selected' : ''; ?>>Industrial E-Waste</option>
                <option value="medical" <?php echo (isset($request['category']) && $request['category'] === 'medical') ? 'selected' : ''; ?>>Medical E-Waste</option>
                <option value="Other" <?php echo (isset($request['category']) && $request['category'] === 'Other') ? 'selected' : ''; ?>>Other</option>
              </select>
            </div>
            <div class="field">
              <label>Other E-wastes</label>
              <input type="text" name="other_category" value="<?php echo htmlspecialchars($request['other_category'] ?? ''); ?>" placeholder="If Other, specify here">
            </div>

            <div class="field">
              <label>Total Item Quantity</label>
              <input type="number" name="quantity" min="1" placeholder="Enter quantity" value="<?php echo htmlspecialchars($request['quantity'] ?? '1'); ?>" required>
            </div>
            <div class="field">
              <label>Estimated E-waste weight (kg)</label>
              <input type="number" name="weight" min="0.1" step="0.01" placeholder="Enter weight" value="<?php echo htmlspecialchars($request['weight'] ?? '1.0'); ?>" required>
            </div>

            <div class="field">
              <label>Condition</label>
              <select name="condition_status" required>
                <option value="working" <?php echo (isset($request['condition_status']) && $request['condition_status'] === 'working') ? 'selected' : ''; ?>>Working</option>
                <option value="damaged" <?php echo (isset($request['condition_status']) && $request['condition_status'] === 'damaged') ? 'selected' : ''; ?>>Damaged</option>
              </select>
            </div>

            <div class="field">
              <label>Damaged/Unusual Note</label>
              <textarea name="note" rows="2" placeholder="e.g. Cracked Screen, Swollen Batteries"><?php echo htmlspecialchars($request['note'] ?? ''); ?></textarea>
            </div>
          </div>

          <div class="action-bar">
            <a href="index.php?route=my-requests" class="btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Cancel</a>
            <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Update' : 'Submit'; ?> Request</button>
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
</div>

<script src="public/js/new-request.js"></script>
</body>
</html>
