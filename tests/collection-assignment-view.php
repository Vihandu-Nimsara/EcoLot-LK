<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$basePath=''; $csrfToken='test-token'; $notice=null; $errors=[]; $collectors=[]; $vehicles=[];
$render = static function (array $overrides) use ($basePath,$csrfToken,$notice,$errors,$collectors,$vehicles): string {
    $schedules = [array_replace(['schedule_id'=>4,'campaign_name'=>'Test','area_name'=>'<Area>', 'collection_date'=>'2026-11-21',
        'ready_requests'=>1,'pending_reviews'=>0,'collector_name'=>'Collector','vehicle_number'=>'Test', 'assigned_at'=>null,
        'assignment_id'=>1,'has_work'=>0,'collection_status'=>null,'saved_record_count'=>0,'schedule_status'=>'ASSIGNED'], $overrides)];
    ob_start(); require APP_ROOT.'/app/Views/municipal_officer/collection-assignments.php'; return ob_get_clean();
};
foreach (['CLOSED'=>'Unassigned','ASSIGNED'=>'Assigned','IN_PROGRESS'=>'In Progress','COLLECTION_SUBMITTED'=>'Submitted for Review','COMPLETED'=>'Completed'] as $status=>$label) {
    $html=$render(['schedule_status'=>$status]);
    verify((bool)preg_match('/data-status="'.$status.'"\s*>\s*'.preg_quote($label,'/').'\s*</', $html),'Incorrect assignment label: '.$status);
}
foreach ([0,1,3] as $count) {
    $html=preg_replace('/\s+/',' ',$render(['has_work'=>1,'collection_status'=>'DRAFT','saved_record_count'=>$count]));
    verify(!str_contains($html,'data-unassign-form'),'Draft exposed unassign');
    verify(str_contains($html,$count === 0 ? 'Empty collector draft exists' : "$count saved ".($count === 1 ? 'record)' : 'records)')), 'Draft count/grammar incorrect');
    verify(str_contains($html,'inconsistent; review required') === ($count > 0),'Incorrect historical mismatch explanation');
}
foreach (['PENDING'=>'Awaiting Officer verification','REJECTED'=>'Collection rejected','VERIFIED'=>'Collection verified'] as $status=>$reason) {
    verify(str_contains($render(['has_work'=>1,'collection_status'=>$status]),$reason),'Missing lock reason: '.$status);
}
verify(str_contains($render(['schedule_status'=>'CLOSED','assignment_id'=>null,'pending_reviews'=>1]),'Resolve pending risk reviews'),'Risk blockers hidden');
verify(str_contains($render(['schedule_status'=>'CLOSED','assignment_id'=>null,'ready_requests'=>0]),'No collectable requests'),'Empty schedule mislabeled ready');
$html=$render([]);
verify(str_contains($html,'&lt;Area&gt;') && str_contains($html,'data-unassign-trigger disabled'),'Unsafe fallback or escaping');
echo "PASS: assignment aliases, raw statuses, lock reasons, singular/plural counts, eligibility and safe no-JS fallback\n";
