<?php
declare(strict_types=1);

class RecyclerController extends Controller
{
    public function dashboard(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $userId = (int) Auth::id();
            $bids = (new RecyclerBid())->listForRecycler($userId);
            $this->view('recycler/dashboard', ['currentPage' => 'dashboard',
                'eligibleCount' => count((new ELot())->eligibleForRecycler($userId)),
                'bidCount' => count($bids),
                'wonCount' => count(array_filter($bids, static fn (array $bid): bool => $bid['bid_status'] === 'WINNING'))]);
        });
    }

    public function eligibleELots(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $this->view('recycler/eligible_e-lots', ['currentPage' => 'eligible-e-lots',
                'lots' => (new ELot())->eligibleForRecycler((int) Auth::id())]);
        });
    }

    public function eLotDetails(string $id): void
    {
        Auth::requireRole('RECYCLER');
        $lotId = $this->positiveId($id);
        if ($lotId === null) return;
        $this->readPage(function () use ($lotId): void {
            $model = new ELot();
            $lot = $model->findVisibleForRecycler($lotId, (int) Auth::id());
            if (!$lot) { http_response_code(404); echo '404 E-Lot not found'; return; }
            $this->view('recycler/e_lot_details', ['currentPage' => 'eligible-e-lots',
                'lot' => $lot, 'items' => $model->itemDetails($lotId)]);
        });
    }

    public function myBids(): void
    {
        Auth::requireRole('RECYCLER');
        $this->readPage(function (): void {
            $this->view('recycler/my_bids', ['currentPage' => 'my-bids',
                'bids' => (new RecyclerBid())->listForRecycler((int) Auth::id())]);
        });
    }

    public function placeBid(string $id): void { $this->mutateBid('place', $id); }
    public function updateBid(string $id): void { $this->mutateBid('update', $id); }
    public function withdrawBid(string $id): void { $this->mutateBid('withdraw', $id); }

    private function mutateBid(string $action, string $id): void
    {
        Auth::requireRole('RECYCLER');
        $recordId = $this->positiveId($id);
        if ($recordId === null) return;
        if (!$this->hasValidCsrfToken()) {
            http_response_code(403);
            echo '403 Invalid or expired form. Please reload the page and try again.';
            return;
        }
        $destination = $action === 'place' ? '/recycler/eligible-e-lots' : '/recycler/my-bids';
        try {
            $service = new RecyclerBidService();
            $userId = (int) Auth::id();
            if ($action === 'place') {
                $service->placeBid($userId, $recordId, $this->postString('bid_amount'));
                $lotId = $recordId;
            } elseif ($action === 'update') {
                $lotId = $service->updateBid($userId, $recordId, $this->postString('bid_amount'));
            } else {
                $lotId = $service->withdrawBid($userId, $recordId);
            }
            $destination = '/recycler/e-lot/' . $lotId;
            Session::flash('bid_success', $action === 'withdraw' ? 'Bid withdrawn. It remains in your bid history.' : 'Your bid has been saved.');
        } catch (DomainException $error) {
            if ($error->getCode() === 404) { http_response_code(404); echo '404 Record not found'; return; }
            Session::flash('bid_error', $error->getMessage());
        } catch (Throwable $error) {
            error_log('Recycler bid mutation failed: ' . $error);
            Session::flash('bid_error', 'We could not save your bid. Please try again.');
        }
        $this->redirect($destination);
    }

    private function positiveId(string $id): ?int
    {
        if (!preg_match('/^[1-9][0-9]*$/D', $id) || filter_var($id, FILTER_VALIDATE_INT) === false) {
            http_response_code(404); echo '404 Record not found'; return null;
        }
        return (int) $id;
    }

    private function readPage(callable $render): void
    {
        try { $render(); }
        catch (Throwable $error) {
            error_log('Recycler bid read failed: ' . $error);
            http_response_code(500);
            echo 'We could not load your bidding information. Please try again later.';
        }
    }

    public function awardedELots(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/awarded_e-lots', ['currentPage' => 'awarded-e-lots']);
    }

    public function awardedELotDetails(string $id): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/awarded_e-lot_details', [
            'currentPage' => 'awarded-e-lots',
            'eLotId' => $id,
        ]);
    }

    public function profile(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/profile', ['currentPage' => 'profile']);
    }

    public function reports(): void
    {
        Auth::requireRole('RECYCLER');
        $this->view('recycler/reports', ['currentPage' => 'reports']);
    }
}
