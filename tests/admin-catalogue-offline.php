<?php
declare(strict_types=1);
require_once __DIR__ . '/support/bootstrap.php';
$count = 0;
$check = static function (bool $ok, string $message) use (&$count): void { verify($ok, $message); $count++; };
$reject = static function (callable $fn) use ($check): void { try { $fn(); } catch (DomainException) { $check(true, 'Rejected invalid input'); return; } throw new RuntimeException('Invalid input accepted'); };
$i = ['category_id'=>'1', 'item_name'=>'Device', 'collection_status'=>'ACCEPTED', 'default_risk_level'=>'LOW', 'item_status'=>'ACTIVE'];
$check(AdminCatalogueService::itemValues($i)['category_id'] === 1, 'Item normalization');
foreach ([['category_id'=>'0'],['category_id'=>'1.5'],['category_id'=>[]],['item_name'=>''],['item_name'=>str_repeat('a',161)],['collection_status'=>'ANY'],['collection_status'=>''],['default_risk_level'=>'NONE'],['default_risk_level'=>''],['item_status'=>'DELETED']] as $bad) $reject(fn()=>AdminCatalogueService::itemValues(array_replace($i,$bad)));
foreach (['collection_status','default_risk_level','item_status'] as $field) { $missing=$i; unset($missing[$field]); $reject(fn()=>AdminCatalogueService::itemValues($missing)); }
$check(AdminCatalogueService::fixedName('Domestic E-Waste'), 'Standard name');
$check(!AdminCatalogueService::fixedName('New category'), 'Nonstandard name');
$check(AdminCatalogueService::excludedCategory(['category_id'=>6,'category_name'=>'Unexpected']), 'Reserved collector ID');
$check(AdminCatalogueService::excludedCategory(['category_id'=>9,'category_name'=>'E-Waste We Do Not Collect']), 'Excluded name');
$check(!method_exists(AdminCatalogueService::class,'saveCategory'), 'No category write service');
$check(!method_exists(AdminCatalogueController::class,'createCategory') && !method_exists(AdminCatalogueController::class,'updateCategory'), 'No category write controller');
$render = static function (bool $unavailable = false, string $edit = ''): string {
    $basePath = '/EcoLot-Categories-Items/public'; $csrfToken = 'test-token'; $notice = null; $failure = null;
    $categories = [
        ['category_id'=>1,'category_name'=>'Domestic E-Waste','description'=>'<script>x</script>','category_status'=>'ACTIVE','item_count'=>1],
        ['category_id'=>6,'category_name'=>'E-Waste We Do Not Collect','description'=>'Excluded','category_status'=>'ACTIVE','item_count'=>1]
    ];
    $items = [['waste_item_id'=>2,'category_id'=>1,'category_name'=>'Domestic E-Waste','category_status'=>'ACTIVE','item_name'=>'<img src=x onerror=alert(1)>','collection_status'=>'ACCEPTED','default_risk_level'=>'LOW','item_status'=>'ACTIVE','name_locked'=>1]];
    $selectedCategory = $categories[0];
    $editItem = $edit === '' ? null : $items[0];
    if ($edit === 'excluded') $editItem = array_replace($editItem,['category_id'=>6,'category_name'=>'E-Waste We Do Not Collect','collection_status'=>'DO_NOT_COLLECT']);
    ob_start(); require APP_ROOT . '/app/Views/admin/categories-items.php'; return ob_get_clean();
};
$html = $render();
$check(substr_count($html,'name="_csrf_token"') === 1, 'Only item form has CSRF');
$check(!str_contains($html,'name="category_name"') && !str_contains($html,'edit_category=') && !str_contains($html,'Create Category'), 'Categories read-only');
$check(str_contains($html,'?category=1#items') && str_contains($html,'View items'), 'Category item navigation');
$check(str_contains($html,'action="/EcoLot-Categories-Items/public/admin/categories-items/items"'), 'Item create route');
$check(!str_contains($html,'<script>x</script>') && !str_contains($html,'<img src=x'), 'Escaped database values');
$check(!str_contains($html,'data-demo-form') && !str_contains($html,'data-admin-dialog'), 'No demo submission interception');
$check(str_contains($html,'data-policy="ACCEPTED"'), 'Policy filter data');
$check(str_contains($html,'<option value="">Select collection policy</option>') && str_contains($html,'<option value="">Select default risk</option>'), 'Explicit policy and risk selection');
$check(str_contains($html,'value="INACTIVE" selected'), 'New item defaults inactive');
$check(str_contains($html,'data-excluded="1"'), 'Excluded category metadata');
$check(str_contains($html,'data-catalogue-tab="categories"') && str_contains($html,'data-catalogue-tab="items"'), 'Separate catalogue tabs');
$check(str_contains($html,'<dialog open') && str_contains($html,'data-catalogue-dialog') && str_contains($html,'data-catalogue-add'), 'Progressive item dialog and add link');
$check(str_contains($html,'data-catalogue-item-filter') && str_contains($html,'data-catalogue-pagination') && str_contains($html,'id="catalogue-page-size"'), 'Catalogue-owned filters and pagination');
$check(!str_contains($html,'data-rows="[data-item-row]"'), 'Shared demo filters cannot overwrite pagination');
$check(str_contains($html,'value="10" selected') && str_contains($html,'value="25"') && str_contains($html,'value="50"'), 'Page size options');

$html = $render(false,'normal');
$check(str_contains($html,'/items/2"'), 'Update form route');
$check(str_contains($html,'name="category_id" value="1"') && str_contains($html,'Name locked:'), 'Protected fields');
$check(str_contains($render(false,'excluded'),'type="hidden" name="collection_status" value="DO_NOT_COLLECT"'), 'Excluded item policy locked');
$check(!str_contains($render(true),'name="_csrf_token"'), 'Unavailable state has no write controls');
$router = new Router(); (require APP_ROOT . '/routes/web.php')($router, []);
$routes = (new ReflectionClass($router))->getProperty('routes')->getValue($router);
$expected = ['GET'=>['/admin/categories-items'=>'index'],'POST'=>['/admin/categories-items/items'=>'createItem','/admin/categories-items/items/{id}'=>'updateItem']];
foreach ($expected as $method=>$paths) foreach ($paths as $path=>$action) $check(count(array_filter($routes[$method],fn($r)=>$r['path']===$path && $r['action']==='AdminCatalogueController@'.$action))===1,'Route '.$path);
foreach ($routes as $method=>$registered) foreach ($registered as $route) {
    if (str_starts_with($route['path'],'/admin/categories-items')) $check(isset($expected[$method][$route['path']]),'No extra catalogue mutation routes');
}
foreach (['/admin/categories-items/categories','/admin/categories-items/categories/1'] as $path) { ob_start(); $router->dispatch('POST',$path); $body=ob_get_clean(); $check(http_response_code()===404 && $body==='404 Page Not Found','Category write request rejected'); }
echo "PASS: $count catalogue validation, rendering and route checks\n";
