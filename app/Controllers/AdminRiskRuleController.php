<?php
declare(strict_types=1);

class AdminRiskRuleController extends Controller
{
    public function index(): void
    {
        Auth::requireRole('ADMIN');
        $items = []; $error = null;
        try { $items = (new AdminRiskRuleService())->profiles(); }
        catch (Throwable $e) { error_log('Admin risk profiles unavailable: '.$e->getMessage()); $error = 'Risk profiles could not be loaded. Please try again.'; http_response_code(503); }
        $id = self::positiveId($_GET['item'] ?? null);
        $selected = null;
        foreach ($items as $item) if ((int)$item['waste_item_id'] === $id) { $selected = $item; break; }
        if ($selected === null && $id === null && $items) $selected = $items[0];
        $condition = $_GET['condition'] ?? null;
        $editCondition = is_string($condition) && in_array($condition, AdminRiskRuleService::CONDITIONS,true) ? $condition : null;
        $this->view('admin/risk-rules', ['currentPage'=>'risk-rules','items'=>$items,'selected'=>$selected,'editCondition'=>$editCondition,'loadError'=>$error,'notice'=>Session::pullFlash('risk_rule_notice')]);
    }

    public static function positiveId(mixed $value): ?int
    {
        if (!is_string($value) || !preg_match('/^[1-9][0-9]*$/D',$value)) return null;
        $id = filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        return $id === false ? null : $id;
    }

    public function save(string $id, string $condition): void
    {
        Auth::requireRole('ADMIN');
        if (!$this->hasValidCsrfToken()) { http_response_code(403); echo '403 Forbidden'; return; }
        $itemId = self::positiveId($id);
        if ($itemId === null || !in_array($condition,AdminRiskRuleService::CONDITIONS,true)) { http_response_code(404); echo '404 Not Found'; return; }
        $path = '/admin/risk-rules?item='.$itemId;
        try {
            (new AdminRiskRuleService())->save((int)Auth::id(),$itemId,['condition_type'=>$condition]+$_POST);
            Session::flash('risk_rule_notice',['type'=>'success','message'=>'Risk rule saved.']);
        } catch (DomainException $e) {
            Session::flash('risk_rule_notice',['type'=>'error','message'=>$e->getMessage()]); $path .= '&condition='.$condition;
        } catch (Throwable $e) {
            error_log('Risk rule save failed: '.$e->getMessage());
            Session::flash('risk_rule_notice',['type'=>'error','message'=>'Risk rule could not be saved. No changes were committed.']); $path .= '&condition='.$condition;
        }
        $this->redirect($path);
    }
}
