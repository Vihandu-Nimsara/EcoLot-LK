<?php
declare(strict_types=1);
require_once __DIR__.'/support/bootstrap.php';
Session::start();
$count=0;
$check=static function(bool $ok,string $message) use (&$count):void {verify($ok,$message);$count++;};
$reject=static function(callable $fn,string $message) use ($check):void {try {$fn();}catch(DomainException $e){$check(true,$message);return;}throw new RuntimeException($message);};
foreach (AdminRiskRuleService::LEVELS as $i=>$default) {
    $check(AdminRiskRuleService::effective($default,null)===$default,'Absent rule fallback');
    foreach(AdminRiskRuleService::LEVELS as $j=>$risk) {
        $check(AdminRiskRuleService::effective($default,['risk_level'=>$risk,'rule_status'=>'ACTIVE'])===AdminRiskRuleService::LEVELS[max($i,$j)],'Active risk floor');
        $check(AdminRiskRuleService::effective($default,['risk_level'=>$risk,'rule_status'=>'INACTIVE'])===$default,'Inactive fallback');
    }
}
$item=['waste_item_id'=>1,'category_id'=>1,'category_name'=>'Domestic <b>test</b>','category_status'=>'ACTIVE','item_name'=>'<script>item</script>','item_status'=>'ACTIVE','collection_status'=>'ACCEPTED','default_risk_level'=>'LOW','rules'=>[]];
$check(AdminRiskRuleService::outcome($item,'LOW')==='Normal pickup eligible','Normal');
$check(AdminRiskRuleService::outcome($item,'HIGH')==='Officer review required','High review');
$check(AdminRiskRuleService::outcome(array_replace($item,['collection_status'=>'REVIEW_REQUIRED']),'LOW')==='Officer review required','Policy review');
foreach(['item_status'=>'INACTIVE','category_status'=>'INACTIVE','collection_status'=>'DO_NOT_COLLECT'] as $k=>$v) $check(AdminRiskRuleService::outcome(array_replace($item,[$k=>$v]),'HIGH')==='Unavailable for new pickups','Eligibility precedence');
$valid=['condition_type'=>'DAMAGED','risk_level'=>'HIGH','action_note'=>' Test <script>guidance</script> ','rule_status'=>'ACTIVE'];
$check(AdminRiskRuleService::validate($valid)['action_note']==='Test <script>guidance</script>','Trim');
foreach(['condition_type'=>'BROKEN','risk_level'=>'CRITICAL','rule_status'=>'true','action_note'=>str_repeat('a',501)] as $k=>$v) $reject(fn()=>AdminRiskRuleService::validate(array_replace($valid,[$k=>$v])),'Bad field '.$k);
$reject(fn()=>AdminRiskRuleService::validate(array_replace($valid,['action_note'=>"\xff"])),'Invalid UTF8');
$reject(fn()=>AdminRiskRuleService::validate(array_replace($valid,['risk_level'=>['HIGH']])),'Array rejected');
$reject(fn()=>AdminRiskRuleService::validate(array_replace($valid,['action_note'=>''])),'Empty guidance');
$check(AdminRiskRuleService::validate(array_replace($valid,['action_note'=>str_repeat('අ',500)]))['action_note']===str_repeat('අ',500),'Unicode boundary');
$check(AdminRiskRuleController::positiveId('1')===1,'Valid ID');
foreach(['0','-1','1e2','01','999999999999999999999999',[],null] as $id) $check(AdminRiskRuleController::positiveId($id)===null,'Bad ID');
$rule=AdminRiskRuleService::validate($valid)+['risk_rule_id'=>4];
$check(AdminRiskRuleService::revision($item,null)!==AdminRiskRuleService::revision($item,$rule),'Create vs update revision');
$check(AdminRiskRuleService::revision($item,$rule)!==AdminRiskRuleService::revision(array_replace($item,['default_risk_level'=>'MEDIUM']),$rule),'Default revision');
$render=static function(array $items,?array $selected,?string $editCondition=null,?string $loadError=null):string {$basePath='/EcoLot-Risk-Rules/public';$notice=null;ob_start();try{include APP_ROOT.'/app/Views/admin/risk-rules.php';return (string)ob_get_clean();}catch(Throwable $e){ob_end_clean();throw $e;}};
$item['rules']['DAMAGED']=$rule;
$html=$render([$item],$item,'DAMAGED');
foreach(['Working','Damaged','Unknown','No custom rule','Active rule','Officer review required','name="_csrf_token"','name="revision"','name="confirm_reduction"','/admin/risk-rules/1/DAMAGED','&lt;script&gt;item&lt;/script&gt;','&lt;script&gt;guidance&lt;/script&gt;'] as $s) $check(str_contains($html,$s),'Rendered '.$s);
$check(!str_contains($html,'<script>guidance</script>'),'Guidance escaped');
$blocked=array_replace($item,['collection_status'=>'DO_NOT_COLLECT']);
$check(!str_contains($render([$blocked],$blocked,'DAMAGED'),'name="revision"'),'Blocked editor absent');
$check(str_contains($render([],null),'No catalogue items'),'Empty state');
$check(str_contains($render([],null,null,'Database unavailable'),'Database unavailable'),'Error state');
$router=new Router('/EcoLot-Risk-Rules/public');(require APP_ROOT.'/routes/web.php')($router,[]);
Session::put('auth_user',['id'=>1,'role'=>'ADMIN']);
$_POST=['_csrf_token'=>'bad'];
ob_start();$router->dispatch('POST','/EcoLot-Risk-Rules/public/admin/risk-rules/1/DAMAGED');$body=ob_get_clean();
$check(http_response_code()===403 && str_contains($body,'Forbidden'),'CSRF before DB');
$_POST=['_csrf_token'=>Csrf::token()];
ob_start();$router->dispatch('POST','/EcoLot-Risk-Rules/public/admin/risk-rules/1/INVALID');$body=ob_get_clean();
$check(http_response_code()===404,'Invalid condition route');
ob_start();$router->dispatch('GET','/EcoLot-Risk-Rules/public/admin/risk-rules/1/DAMAGED');ob_end_clean();$check(http_response_code()===405,'GET cannot mutate');
ob_start();$router->dispatch('POST','/EcoLot-Risk-Rules/public/admin/risk-rules/1/DAMAGED/delete');ob_end_clean();$check(http_response_code()===404,'No delete route');
echo "PASS: $count risk profile validation, rendering and route checks\n";
