<?php
declare(strict_types=1);

final class AdminCatalogueController extends Controller
{
    private const PATH = '/admin/categories-items';

    public function index(): void
    {
        Auth::requireRole('ADMIN');
        $categories = $items = [];
        $unavailable = false;
        try {
            $service = new AdminCatalogueService();
            $categories = $service->categories();
            $items = $service->items();
        } catch (Throwable $error) {
            error_log('Catalogue read failed: ' . $error::class);
            $unavailable = true;
            http_response_code(503);
        }
        $this->view('admin/categories-items', [
            'currentPage' => 'categories-items', 'categories' => $categories, 'items' => $items,
            'unavailable' => $unavailable, 'notice' => Session::pullFlash('catalogue_notice'),
            'failure' => Session::pullFlash('catalogue_error'), 'csrfToken' => Csrf::token(),
            'selectedCategory' => $this->selected($categories, 'category_id', $_GET['category'] ?? null),
            'editItem' => $this->selected($items, 'waste_item_id', $_GET['edit_item'] ?? null),
        ]);
    }

    private function selected(array $rows, string $key, mixed $value): ?array
    {
        if ($value === null) return null;
        try { $id = AdminCatalogueService::id($value); } catch (DomainException) { return null; }
        foreach ($rows as $row) if ((int)$row[$key] === $id) return $row;
        return null;
    }

    public function createItem(): void { $this->save(false); }
    public function updateItem(string $id): void { $this->save(true, $id); }

    private function save(bool $update, ?string $rawId = null): never
    {
        Auth::requireRole('ADMIN');
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            exit('403 Forbidden: Invalid CSRF token');
        }
        $id = null;
        try {
            if ($update) $id = AdminCatalogueService::id($rawId);
            $actor = AdminCatalogueService::id(Auth::id());
            $service = new AdminCatalogueService();
            $service->saveItem($actor, $id, $_POST);
            Session::flash('catalogue_notice', 'Item' . ($update ? ' updated.' : ' created.'));
            $this->redirect(self::PATH . '#items');
        } catch (DomainException $error) {
            Session::flash('catalogue_error', $error->getMessage());
        } catch (Throwable $error) {
            error_log('Catalogue write failed: ' . $error::class);
            Session::flash('catalogue_error', 'Unable to save the record. Please try again.');
        }
        $query = $id === null ? '' : '?edit_item=' . $id;
        $this->redirect(self::PATH . $query . '#item-form');
    }
}
