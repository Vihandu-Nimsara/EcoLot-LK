<?php
declare(strict_types=1);

/** Display data shared by pickup history and dashboard views. */
final class PickupRequestPresenter
{
    public static function present(array $request): array
    {
        $statusMap = [
            'SUBMITTED' => ['Submitted', 'badge-pending', 'status-badge pending'],
            'PENDING_REVIEW' => ['Pending Review', 'badge-pending', 'status-badge pending'],
            'APPROVED' => ['Approved', 'badge-pending', 'status-badge pending'],
            'COMPLETED' => ['Completed', 'badge-completed', 'status-badge completed'],
            'REJECTED' => ['Rejected', 'badge-cancelled', 'status-badge cancelled'],
            'CANCELLED' => ['Cancelled', 'badge-cancelled', 'status-badge cancelled'],
        ];
        [$statusLabel, $historyBadgeClass, $dashboardBadgeClass] =
            $statusMap[$request['request_status']] ?? ['Pending', 'badge-pending', 'status-badge pending'];

        $isEditable = EWasteRequest::canModify($request);

        $items = $request['items'] ?? [];
        $categories = [];
        $conditions = [];
        $totalQuantity = 0;
        $totalWeight = 0.0;
        $viewItems = [];

        foreach ($items as $item) {
            $categories[$item['category_name']] = true;
            $conditions[$item['item_condition']] = true;
            $totalQuantity += (int) $item['quantity'];
            $totalWeight += (float) $item['estimated_weight_kg'];

            $viewItems[] = [
                'category' => $item['category_name'],
                'item' => $item['item_name'],
                'quantity' => (int) $item['quantity'],
                'weight' => (float) $item['estimated_weight_kg'],
                'condition' => strtoupper($item['item_condition']),
                'note' => $item['condition_note'] ?? '',
            ];
        }

        $conditionSummary = count($conditions) === 1
            ? ucfirst(strtolower(array_key_first($conditions)))
            : 'Mixed';

        return [
            'request_id' => (int) $request['request_id'],
            'schedule_id' => (int) $request['schedule_id'],
            'code' => 'REQ-' . $request['request_id'],
            'submitted_date' => date('M j, Y', strtotime((string) $request['submitted_at'])),
            'collection_date' => date('M j, Y', strtotime((string) $request['collection_date'])),
            'collection_date_iso' => date('Y-m-d', strtotime((string) $request['collection_date'])),
            'postal_label' => sprintf('%s (%s)', $request['area_name'], $request['postal_code']),
            'address' => $request['pickup_address'],
            'status_label' => $statusLabel,
            'is_editable' => $isEditable,
            'history_badge_class' => $historyBadgeClass,
            'dashboard_badge_class' => $dashboardBadgeClass,
            'category_summary' => $categories === [] ? '—' : implode(' / ', array_keys($categories)),
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'total_weight_label' => number_format($totalWeight, 1) . ' kg',
            'condition_summary' => $conditionSummary,
            'items' => $viewItems,
            'items_json' => json_encode($viewItems, JSON_UNESCAPED_SLASHES),
        ];
    }
}
