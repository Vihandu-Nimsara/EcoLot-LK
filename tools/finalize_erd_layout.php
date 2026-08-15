<?php
declare(strict_types=1);

$input = $argv[1] ?? '';
$output = $argv[2] ?? '';

if ($input === '' || $output === '') {
    fwrite(STDERR, "Usage: php finalize_erd_layout.php INPUT OUTPUT\n");
    exit(1);
}

$doc = new DOMDocument('1.0', 'UTF-8');
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;

if (!$doc->load($input)) {
    throw new RuntimeException('Unable to load input ERD: ' . $input);
}

$xpath = new DOMXPath($doc);
$root = $xpath->query('//mxGraphModel/root')->item(0);
$graph = $xpath->query('//mxGraphModel')->item(0);

if (!$root instanceof DOMElement || !$graph instanceof DOMElement) {
    throw new RuntimeException('Invalid draw.io mxGraphModel document.');
}

$graph->setAttribute('dx', '3900');
$graph->setAttribute('dy', '3600');
$graph->setAttribute('page', '0');

$positions = [
    // Identity and access
    'Administrator' => [0, 0],
    'Public_Profile' => [1, 0],
    'User' => [2, 0],
    'Municipal_Officer' => [3, 0],
    'Collector' => [4, 0],
    'Authorized_Recycler' => [5, 0],

    // Campaign and scheduling
    'Postal_Code_Area' => [0, 1],
    'Monthly_Campaign' => [1, 1],
    'Area_Collection_Schedule' => [2, 1],
    'Schedule_Assignment' => [3, 1],
    'Vehicle' => [4, 1],
    'Recycler_License' => [5, 1],

    // Requests, catalogue, risk and feedback
    'Waste_Category' => [0, 2],
    'E_Waste_Item' => [1, 2],
    'Request_Item' => [2, 2],
    'E_Waste_Request' => [3, 2],
    'Risk_Rule' => [4, 2],
    'Complaint_Feedback' => [5, 2],

    // Collection verification and recycler qualification
    'Recycler_Capability' => [0, 3],
    'Schedule_Collection' => [2, 3],
    'Collection_Record' => [3, 3],
    'Collection_Record_Item' => [4, 3],

    // Marketplace
    'E_Lot' => [1, 4],
    'E_Lot_Item' => [2, 4],
    'Recycler_Bid' => [4, 4],
    'Handover_Record' => [5, 4],
];

$entityIds = [];
$entityNamesById = [];

foreach ($xpath->query('//mxCell[starts-with(@id,"entity_") and not(contains(@id,"_attr_"))]') as $cell) {
    if (!$cell instanceof DOMElement) {
        continue;
    }

    $name = trim(strip_tags(html_entity_decode($cell->getAttribute('value'), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $entityIds[$name] = $cell->getAttribute('id');
    $entityNamesById[$cell->getAttribute('id')] = $name;
}

$missingEntities = array_diff(array_keys($positions), array_keys($entityIds));
if ($missingEntities !== []) {
    throw new RuntimeException('Missing required entities: ' . implode(', ', $missingEntities));
}

// Remove old relationship edges (including manually drawn/unlabelled ones),
// title/legend/section cells if the script is rerun. Attribute connectors stay.
foreach ($xpath->query('//mxCell[starts-with(@id,"layout_") or starts-with(@id,"rel_") or (@edge="1" and not(contains(@id,"_attr_")))]') as $oldCell) {
    $oldCell->parentNode?->removeChild($oldCell);
}

$originX = 90;
$originY = 165;
$columnSpacing = 600;
$rowSpacing = 620;
$attributeWidth = 205;
$attributeHeight = 36;
$attributeGapX = 220;
$attributeGapY = 46;

foreach ($positions as $entityName => [$column, $row]) {
    $entityId = $entityIds[$entityName];
    $entityCell = $xpath->query('//mxCell[@id="' . $entityId . '"]')->item(0);
    if (!$entityCell instanceof DOMElement) {
        continue;
    }

    $baseX = $originX + ($column * $columnSpacing);
    $baseY = $originY + ($row * $rowSpacing);
    $geometry = $xpath->query('./mxGeometry', $entityCell)->item(0);
    if ($geometry instanceof DOMElement) {
        $geometry->setAttribute('x', (string)($baseX + 112));
        $geometry->setAttribute('y', (string)$baseY);
        $geometry->setAttribute('width', '205');
        $geometry->setAttribute('height', '62');
    }

    $attributeCells = [];
    foreach ($xpath->query('//mxCell[starts-with(@id,"' . $entityId . '_attr_") and not(substring(@id, string-length(@id) - 4) = "_edge")]') as $attributeCell) {
        if ($attributeCell instanceof DOMElement) {
            $attributeCells[] = $attributeCell;
        }
    }

    usort($attributeCells, static function (DOMElement $a, DOMElement $b): int {
        preg_match('/_attr_(\d+)$/', $a->getAttribute('id'), $am);
        preg_match('/_attr_(\d+)$/', $b->getAttribute('id'), $bm);
        return ((int)($am[1] ?? 0)) <=> ((int)($bm[1] ?? 0));
    });

    foreach ($attributeCells as $index => $attributeCell) {
        $attributeGeometry = $xpath->query('./mxGeometry', $attributeCell)->item(0);
        if (!$attributeGeometry instanceof DOMElement) {
            continue;
        }

        $attributeGeometry->setAttribute('x', (string)($baseX + (($index % 2) * $attributeGapX)));
        $attributeGeometry->setAttribute('y', (string)($baseY + 86 + (intdiv($index, 2) * $attributeGapY)));
        $attributeGeometry->setAttribute('width', (string)$attributeWidth);
        $attributeGeometry->setAttribute('height', (string)$attributeHeight);
    }
}

function addVertex(
    DOMDocument $doc,
    DOMElement $root,
    string $id,
    string $value,
    string $style,
    int $x,
    int $y,
    int $width,
    int $height
): void {
    $cell = $doc->createElement('mxCell');
    $cell->setAttribute('id', $id);
    $cell->setAttribute('value', $value);
    $cell->setAttribute('style', $style);
    $cell->setAttribute('vertex', '1');
    $cell->setAttribute('parent', '1');

    $geometry = $doc->createElement('mxGeometry');
    $geometry->setAttribute('x', (string)$x);
    $geometry->setAttribute('y', (string)$y);
    $geometry->setAttribute('width', (string)$width);
    $geometry->setAttribute('height', (string)$height);
    $geometry->setAttribute('as', 'geometry');
    $cell->appendChild($geometry);
    $root->appendChild($cell);
}

addVertex(
    $doc,
    $root,
    'layout_title',
    '<b>EcoLot LK — Simplified Final ER Diagram</b><br><font style="font-size:12px">Normalized entities, essential attributes, relationships and cardinalities</font>',
    'rounded=1;whiteSpace=wrap;html=1;fillColor=#173f35;strokeColor=#0f2b24;fontColor=#ffffff;fontSize=20;align=center;verticalAlign=middle;strokeWidth=2;',
    905,
    20,
    1830,
    82
);

$sections = [
    ['IDENTITY & ACCESS', 120],
    ['CAMPAIGN, ZONE & SCHEDULING', 740],
    ['REQUESTS, CATALOGUE, RISK & FEEDBACK', 1360],
    ['COLLECTION VERIFICATION & RECYCLER QUALIFICATION', 1980],
    ['E-LOT, BIDDING & HANDOVER', 2600],
];

foreach ($sections as $index => [$label, $y]) {
    addVertex(
        $doc,
        $root,
        'layout_section_' . ($index + 1),
        '<b>' . $label . '</b>',
        'rounded=1;whiteSpace=wrap;html=1;fillColor=#edf7f3;strokeColor=#8bb7a6;fontColor=#285c4b;fontSize=12;align=left;spacingLeft=14;verticalAlign=middle;',
        70,
        $y,
        3430,
        34
    );
}

$relationships = [
    // Identity and account audit
    ['User', 'Administrator', 'has admin profile · 1 : 0..1', 'solid'],
    ['User', 'Public_Profile', 'has public profile · 1 : 0..1', 'solid'],
    ['User', 'Municipal_Officer', 'has officer profile · 1 : 0..1', 'solid'],
    ['User', 'Collector', 'has collector profile · 1 : 0..1', 'solid'],
    ['User', 'Authorized_Recycler', 'has recycler profile · 1 : 0..1', 'solid'],
    ['Administrator', 'User', 'creates internal accounts · 1 : 0..M', 'audit'],
    ['Administrator', 'Authorized_Recycler', 'verifies registrations · 1 : 0..M', 'audit'],

    // Zone, campaigns and schedules
    ['Postal_Code_Area', 'Public_Profile', 'contains residents · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'Monthly_Campaign', 'creates · 1 : 0..M', 'solid'],
    ['Monthly_Campaign', 'Area_Collection_Schedule', 'contains · 1 : 0..M', 'solid'],
    ['Postal_Code_Area', 'Area_Collection_Schedule', 'scheduled for · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'Area_Collection_Schedule', 'creates · 1 : 0..M', 'audit'],
    ['Area_Collection_Schedule', 'Schedule_Assignment', 'has assignment history · 1 : 0..M', 'solid'],
    ['Collector', 'Schedule_Assignment', 'is assigned through · 1 : 0..M', 'solid'],
    ['Vehicle', 'Schedule_Assignment', 'optionally allocated · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'Schedule_Assignment', 'assigns · 1 : 0..M', 'audit'],

    // Requests, catalogue, risk, feedback
    ['Public_Profile', 'E_Waste_Request', 'submits · 1 : 0..M', 'solid'],
    ['Area_Collection_Schedule', 'E_Waste_Request', 'accepts · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'E_Waste_Request', 'reviews flagged requests · 1 : 0..M', 'audit'],
    ['E_Waste_Request', 'Request_Item', 'contains · 1 : 1..M', 'solid'],
    ['Waste_Category', 'E_Waste_Item', 'classifies · 1 : 0..M', 'solid'],
    ['E_Waste_Item', 'Request_Item', 'is requested as · 1 : 0..M', 'solid'],
    ['E_Waste_Item', 'Risk_Rule', 'governed by · 1 : 0..M', 'solid'],
    ['Public_Profile', 'Complaint_Feedback', 'submits · 1 : 0..M', 'solid'],
    ['E_Waste_Request', 'Complaint_Feedback', 'may receive feedback · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'Complaint_Feedback', 'reviews/responds · 1 : 0..M', 'audit'],

    // Recycler qualification
    ['Authorized_Recycler', 'Recycler_License', 'holds · 1 : 0..M', 'solid'],
    ['Authorized_Recycler', 'Recycler_Capability', 'has · 1 : 0..M', 'solid'],
    ['Waste_Category', 'Recycler_Capability', 'approved through · 1 : 0..M', 'solid'],

    // Collection and whole-schedule verification
    ['Area_Collection_Schedule', 'Schedule_Collection', 'produces · 1 : 0..1', 'solid'],
    ['Collector', 'Schedule_Collection', 'submits · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'Schedule_Collection', 'verifies whole schedule · 1 : 0..M', 'audit'],
    ['Schedule_Collection', 'Collection_Record', 'contains request results · 1 : 1..M', 'solid'],
    ['E_Waste_Request', 'Collection_Record', 'has actual result · 1 : 0..1', 'solid'],
    ['Collection_Record', 'Collection_Record_Item', 'contains actual items · 1 : 1..M', 'solid'],
    ['Request_Item', 'Collection_Record_Item', 'has actual collection · 1 : 0..1', 'solid'],

    // E-Lots
    ['Collector', 'E_Lot', 'creates from verified items · 1 : 0..M', 'solid'],
    ['Waste_Category', 'E_Lot', 'groups by one category · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'E_Lot', 'verifies and opens bidding · 1 : 0..M', 'audit'],
    ['E_Lot', 'E_Lot_Item', 'contains · 1 : 1..M', 'solid'],
    ['Collection_Record_Item', 'E_Lot_Item', 'allocated once · 1 : 0..1', 'solid'],

    // Bidding and handover
    ['E_Lot', 'Recycler_Bid', 'receives · 1 : 0..M', 'solid'],
    ['Authorized_Recycler', 'Recycler_Bid', 'submits · 1 : 0..M', 'solid'],
    ['Municipal_Officer', 'Recycler_Bid', 'reviews/awards · 1 : 0..M', 'audit'],
    ['Recycler_Bid', 'Handover_Record', 'winning bid produces · 1 : 0..1', 'solid'],
    ['Municipal_Officer', 'Handover_Record', 'records · 1 : 0..M', 'audit'],
];

$baseEdgeStyle = 'edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;endArrow=none;startArrow=none;strokeWidth=2;strokeColor=#455a64;fontSize=10;labelBackgroundColor=#ffffff;jumpStyle=arc;jumpSize=8;';
$auditEdgeStyle = 'edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;endArrow=none;startArrow=none;strokeWidth=2;strokeColor=#7e57c2;fontSize=10;fontColor=#5e3f86;labelBackgroundColor=#ffffff;dashed=1;dashPattern=6 4;jumpStyle=arc;jumpSize=8;';
$laneCounters = [];

foreach ($relationships as $index => [$sourceName, $targetName, $label, $kind]) {
    [$sourceColumn, $sourceRow] = $positions[$sourceName];
    [$targetColumn, $targetRow] = $positions[$targetName];
    $goRight = $targetColumn > $sourceColumn;

    if ($targetColumn === $sourceColumn) {
        $goRight = ($index % 2) === 0;
    }

    $exitX = $goRight ? '1' : '0';
    $entryX = $goRight ? '0' : '1';
    $style = ($kind === 'audit' ? $auditEdgeStyle : $baseEdgeStyle)
        . 'exitX=' . $exitX . ';exitY=0.5;exitDx=0;exitDy=0;'
        . 'entryX=' . $entryX . ';entryY=0.5;entryDx=0;entryDy=0;';

    $cell = $doc->createElement('mxCell');
    $cell->setAttribute('id', 'rel_' . ($index + 1));
    $cell->setAttribute('value', $label);
    $cell->setAttribute('style', $style);
    $cell->setAttribute('edge', '1');
    $cell->setAttribute('parent', '1');
    $cell->setAttribute('source', $entityIds[$sourceName]);
    $cell->setAttribute('target', $entityIds[$targetName]);

    $geometry = $doc->createElement('mxGeometry');
    $geometry->setAttribute('relative', '1');
    $geometry->setAttribute('as', 'geometry');

    $columnBlockX = static fn (int $column): int => $originX + ($column * $columnSpacing);
    $entityCenterY = static fn (int $row): int => $originY + ($row * $rowSpacing) + 31;
    $sourceOuterX = $goRight
        ? $columnBlockX($sourceColumn) + 458
        : $columnBlockX($sourceColumn) - 28;
    $targetOuterX = $goRight
        ? $columnBlockX($targetColumn) - 28
        : $columnBlockX($targetColumn) + 458;

    $sameRowAndAdjacent = $sourceRow === $targetRow && abs($sourceColumn - $targetColumn) === 1;

    if (!$sameRowAndAdjacent) {
        $laneKey = (string)$sourceRow;
        $laneIndex = $laneCounters[$laneKey] ?? 0;
        $laneCounters[$laneKey] = $laneIndex + 1;
        $laneY = $originY + ($sourceRow * $rowSpacing) + 430 + (($laneIndex % 8) * 18);

        $points = $doc->createElement('Array');
        $points->setAttribute('as', 'points');

        foreach ([
            [$sourceOuterX, $entityCenterY($sourceRow)],
            [$sourceOuterX, $laneY],
            [$targetOuterX, $laneY],
            [$targetOuterX, $entityCenterY($targetRow)],
        ] as [$pointX, $pointY]) {
            $point = $doc->createElement('mxPoint');
            $point->setAttribute('x', (string)$pointX);
            $point->setAttribute('y', (string)$pointY);
            $points->appendChild($point);
        }

        $geometry->appendChild($points);
    }

    $cell->appendChild($geometry);
    $root->appendChild($cell);
}

addVertex(
    $doc,
    $root,
    'layout_legend',
    '<b>LEGEND</b><br>Green oval = PK · Red oval = FK · Yellow oval = normal attribute · UQ = unique · NULL = optional<br>Solid dark line = structural relationship · Dashed purple line = creation/review/verification audit relationship',
    'rounded=1;whiteSpace=wrap;html=1;fillColor=#f7f9fa;strokeColor=#607d8b;fontSize=11;align=left;spacingLeft=14;verticalAlign=middle;',
    70,
    3225,
    1580,
    82
);

addVertex(
    $doc,
    $root,
    'layout_rules',
    '<b>CORE RULES</b><br>One active collector assignment per schedule · Officer verifies/rejects the whole Schedule_Collection · Only verified collected items enter E-Lots · One category per E-Lot · One collected item can enter at most one E-Lot · Officer sets bidding window when approving the E-Lot',
    'rounded=1;whiteSpace=wrap;html=1;fillColor=#e8f5e9;strokeColor=#4caf50;fontSize=11;align=left;spacingLeft=14;verticalAlign=middle;',
    1685,
    3225,
    1815,
    82
);

$outputDirectory = dirname($output);
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException('Unable to create output directory: ' . $outputDirectory);
}

if ($doc->save($output) === false) {
    throw new RuntimeException('Unable to save final ERD: ' . $output);
}

echo "Final ERD: {$output}\n";
echo 'Entities: ' . count($entityIds) . "\n";
echo 'Relationships: ' . count($relationships) . "\n";
