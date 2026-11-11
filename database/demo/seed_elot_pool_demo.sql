-- EcoLot LK - Verified Items Pool demo data (min 4 items per category)

SET @collector_id := 3;  -- put YOUR collector user_id here, e.g. 3

-- 1) Pick the VERIFIED schedule collection
SET @sc_id := (
    SELECT sc.schedule_collection_id
    FROM schedule_collections sc
    WHERE sc.verification_status = 'VERIFIED'
      AND (@collector_id IS NULL
           OR sc.submitted_by_collector_user_id = @collector_id)
    ORDER BY sc.verified_at DESC, sc.schedule_collection_id DESC
    LIMIT 1
);
SET @sc_collector := (SELECT submitted_by_collector_user_id
                      FROM schedule_collections
                      WHERE schedule_collection_id = @sc_id);

-- Reuse one existing collected pickup record of that schedule
SET @cr_id := (
    SELECT collection_record_id FROM collection_records
    WHERE schedule_collection_id = @sc_id
      AND pickup_result IN ('COLLECTED', 'PARTIAL')
    ORDER BY collection_record_id LIMIT 1
);
SET @req_id := (SELECT request_id FROM collection_records
                WHERE collection_record_id = @cr_id);

SELECT IF(@cr_id IS NULL,
          'ERROR: no VERIFIED schedule with a collected record found',
          CONCAT('Using schedule_collection_id=', @sc_id,
                 ', collector_user_id=', @sc_collector,
                 ', collection_record_id=', @cr_id)) AS info;

-- 2) Demo items
DROP TEMPORARY TABLE IF EXISTS pool_seed;
CREATE TEMPORARY TABLE pool_seed (
    seq INT NOT NULL,
    category_name VARCHAR(120) NOT NULL,
    item_name VARCHAR(160) NOT NULL,
    qty INT NOT NULL,
    weight DECIMAL(10,3) NOT NULL
);

INSERT INTO pool_seed (seq, category_name, item_name, qty, weight) VALUES
 (1,  'Automobile E-Waste', 'Dashboard electronics',            2,  3.600),
 (2,  'Automobile E-Waste', 'LED headlights',                   4,  2.800),
 (3,  'Automobile E-Waste', 'Hybrid batteries / EV batteries',  1, 18.500),
 (4,  'Automobile E-Waste', 'Motors / Alternators',             2,  9.200),
 (5,  'Automobile E-Waste', 'Sensors',                          6,  1.200),
 (6,  'Industrial E-Waste', 'Inverters / VFDs',                 2, 12.400),
 (7,  'Industrial E-Waste', 'Power supply units',               3,  6.300),
 (8,  'Industrial E-Waste', 'Automation equipment',             1, 15.000),
 (9,  'Industrial E-Waste', 'Sign board displays',              2,  8.500),
 (10, 'Industrial E-Waste', 'Solar power equipment',            2, 10.500),
 (11, 'Medical E-Waste',    'Oximeters / Electronic thermometers', 6, 1.500),
 (12, 'Medical E-Waste',    'Glucometers / Weight scales',      5,  3.200),
 (13, 'Medical E-Waste',    'Hearing aids / Electric wheelchairs', 2, 4.800),
 (14, 'Medical E-Waste',    'Ultrasound machines and probes',   1, 14.000),
 (15, 'Medical E-Waste',    'Centrifuges / Spectrophotometers', 1,  9.800),
 (16, 'Office E-Waste',     'Photocopy machines',               1, 25.000),
 (17, 'Office E-Waste',     'Projectors / Speakers',            2,  7.200),
 (18, 'Office E-Waste',     'Barcode readers / POS machines',   3,  4.100),
 (19, 'Domestic E-Waste',   'Electric ovens / Microwave ovens', 1, 12.000),
 (20, 'Domestic E-Waste',   'Electronic exercise equipment',    1, 20.000);

-- 3) Skip rows already inserted (safe to re-run)
DROP TEMPORARY TABLE IF EXISTS pool_final;
CREATE TEMPORARY TABLE pool_final AS
SELECT s.seq, s.category_name, s.item_name, s.qty, s.weight, wi.waste_item_id,
       CONCAT('POOL-DEMO-', s.seq) AS marker
FROM pool_seed s
JOIN waste_categories wc ON wc.category_name = s.category_name
JOIN e_waste_items wi
     ON wi.category_id = wc.category_id AND wi.item_name = s.item_name
WHERE NOT EXISTS (
        SELECT 1 FROM request_items x
        WHERE x.condition_note = CONCAT('POOL-DEMO-', s.seq))
  AND @cr_id IS NOT NULL;

-- 4) Insert request items, then the collected items
INSERT INTO request_items
    (request_id, waste_item_id, quantity, estimated_weight_kg,
     item_condition, condition_note, applied_risk_level)
SELECT @req_id, f.waste_item_id, f.qty, f.weight, 'WORKING', f.marker, 'LOW'
FROM pool_final f;

INSERT INTO collection_record_items
    (collection_record_id, request_item_id, actual_quantity, actual_weight_kg,
     actual_condition, item_result, actual_risk_level)
SELECT @cr_id, ri.request_item_id, f.qty, f.weight,
       'WORKING', 'COLLECTED', 'LOW'
FROM pool_final f
JOIN request_items ri ON ri.request_id = @req_id
                     AND ri.condition_note = f.marker;

-- 5) Check: each category should show >= 4
SELECT wc.category_name, COUNT(cri.record_item_id) AS verified_items
FROM waste_categories wc
LEFT JOIN e_waste_items wi ON wi.category_id = wc.category_id
LEFT JOIN request_items ri ON ri.waste_item_id = wi.waste_item_id
LEFT JOIN collection_record_items cri
       ON cri.request_item_id = ri.request_item_id
      AND cri.item_result IN ('COLLECTED','PARTIAL')
LEFT JOIN collection_records cr ON cr.collection_record_id = cri.collection_record_id
LEFT JOIN schedule_collections sc
       ON sc.schedule_collection_id = cr.schedule_collection_id
      AND sc.verification_status = 'VERIFIED'
      AND sc.submitted_by_collector_user_id = @sc_collector
WHERE wc.category_id <> 6 AND sc.schedule_collection_id IS NOT NULL
GROUP BY wc.category_name
ORDER BY wc.category_name;

DROP TEMPORARY TABLE IF EXISTS pool_seed;
DROP TEMPORARY TABLE IF EXISTS pool_final;