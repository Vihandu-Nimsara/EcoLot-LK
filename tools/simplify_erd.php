<?php
declare(strict_types=1);

$input = $argv[1] ?? '';
$output = $argv[2] ?? '';

if ($input === '' || $output === '') {
    fwrite(STDERR, "Usage: php simplify_erd.php INPUT OUTPUT\n");
    exit(1);
}

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;

if (!$doc->load($input)) {
    throw new RuntimeException('Unable to load ERD: ' . $input);
}

$xpath = new DOMXPath($doc);

function removeAttribute(DOMXPath $xpath, string $attributeId): void
{
    foreach ([$attributeId, $attributeId . '_edge'] as $id) {
        $cell = $xpath->query('//mxCell[@id="' . $id . '"]')->item(0);
        $cell?->parentNode?->removeChild($cell);
    }
}

function renameAttribute(DOMXPath $xpath, string $attributeId, string $value, bool $primary = false): void
{
    $cell = $xpath->query('//mxCell[@id="' . $attributeId . '"]')->item(0);
    if (!$cell instanceof DOMElement) {
        throw new RuntimeException('Attribute not found: ' . $attributeId);
    }

    $cell->setAttribute('value', $value);

    if ($primary) {
        $cell->setAttribute(
            'style',
            'ellipse;whiteSpace=wrap;html=1;fillColor=#d5e8d4;strokeColor=#82b366;fontSize=10;fontStyle=4;align=center;verticalAlign=middle;'
        );
    }
}

// Role subtypes use the User key directly. Separate surrogate IDs duplicated identity.
removeAttribute($xpath, 'entity_administrator_attr_1');
renameAttribute($xpath, 'entity_administrator_attr_2', 'user_id PK FK', true);

removeAttribute($xpath, 'entity_public_profile_attr_1');
renameAttribute($xpath, 'entity_public_profile_attr_2', 'user_id PK FK', true);

removeAttribute($xpath, 'entity_municipal_officer_attr_1');
renameAttribute($xpath, 'entity_municipal_officer_attr_2', 'user_id PK FK', true);

removeAttribute($xpath, 'entity_collector_attr_1');
renameAttribute($xpath, 'entity_collector_attr_2', 'user_id PK FK', true);

removeAttribute($xpath, 'entity_authorized_recycler_attr_1');
renameAttribute($xpath, 'entity_authorized_recycler_attr_2', 'user_id PK FK', true);

// User.full_name is the recycler contact-person name; do not store it twice.
removeAttribute($xpath, 'entity_authorized_recycler_attr_4');

// ACTIVE is derived from unassigned_at IS NULL; historical rows retain assigned/unassigned timestamps.
removeAttribute($xpath, 'entity_schedule_assignment_attr_6');

// Pure junction tables use natural/composite keys instead of unnecessary surrogate IDs.
removeAttribute($xpath, 'entity_recycler_capability_attr_1');
renameAttribute($xpath, 'entity_recycler_capability_attr_2', 'recycler_user_id PK FK', true);
renameAttribute($xpath, 'entity_recycler_capability_attr_3', 'category_id PK FK', true);

removeAttribute($xpath, 'entity_e_lot_item_attr_1');
renameAttribute($xpath, 'entity_e_lot_item_attr_2', 'e_lot_id FK');
renameAttribute($xpath, 'entity_e_lot_item_attr_3', 'record_item_id PK FK', true);

// Handover is one-to-zero-or-one from the winning bid, so that bid key can identify it.
removeAttribute($xpath, 'entity_handover_record_attr_1');
renameAttribute($xpath, 'entity_handover_record_attr_2', 'winning_bid_id PK FK', true);

// Actor FK names explicitly show that role subtype keys are User IDs.
$renames = [
    'entity_user_attr_10' => 'created_by_admin_user_id FK NULL',
    'entity_authorized_recycler_attr_8' => 'verified_by_admin_user_id FK NULL',
    'entity_monthly_campaign_attr_2' => 'created_by_officer_user_id FK',
    'entity_area_collection_schedule_attr_4' => 'created_by_officer_user_id FK',
    'entity_schedule_assignment_attr_3' => 'collector_user_id FK',
    'entity_schedule_assignment_attr_5' => 'assigned_by_officer_user_id FK',
    'entity_complaint_feedback_attr_2' => 'public_user_id FK',
    'entity_complaint_feedback_attr_8' => 'reviewed_by_officer_user_id FK NULL',
    'entity_e_waste_request_attr_2' => 'public_user_id FK',
    'entity_e_waste_request_attr_7' => 'reviewed_by_officer_user_id FK NULL',
    'entity_recycler_license_attr_2' => 'recycler_user_id FK',
    'entity_schedule_collection_attr_3' => 'submitted_by_collector_user_id FK',
    'entity_schedule_collection_attr_6' => 'verified_by_officer_user_id FK NULL',
    'entity_e_lot_attr_3' => 'created_by_collector_user_id FK',
    'entity_e_lot_attr_8' => 'verified_by_officer_user_id FK NULL',
    'entity_recycler_bid_attr_3' => 'recycler_user_id FK',
    'entity_recycler_bid_attr_7' => 'reviewed_by_officer_user_id FK NULL',
    'entity_handover_record_attr_5' => 'recorded_by_officer_user_id FK',
];

foreach ($renames as $attributeId => $value) {
    renameAttribute($xpath, $attributeId, $value);
}

if ($doc->save($output) === false) {
    throw new RuntimeException('Unable to save simplified ERD source: ' . $output);
}

echo "Simplified ERD source: {$output}\n";
