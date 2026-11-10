<?php
declare(strict_types=1);

final class RecyclerLicenceCoverage extends Model
{
    protected string $table='recycler_license_coverage';
    protected string $primaryKey='coverage_id';

    private function authorize(int $owner): void
    {
        if ($owner<=0 || (int)Auth::id()<=0 || (Auth::role()!=='ADMIN' && (Auth::role()!=='RECYCLER' || (int)Auth::id()!==$owner))) throw new DomainException('Unauthorized licence coverage access.');
    }

    public function history(int $owner): array
    {
        $this->authorize($owner);
        $rows=$this->query("SELECT c.*,d.license_id,d.license_number,d.document_version,d.expiry_date,w.category_name,
            CASE WHEN lic.document_version=d.document_version AND lic.document_path=d.document_path
                AND lic.license_status='VALID' AND lic.expiry_date>=CURRENT_DATE
                AND lic.license_id=(SELECT cur.license_id FROM recycler_licenses cur WHERE cur.recycler_user_id=d.recycler_user_id AND cur.license_status='VALID' ORDER BY cur.submitted_at DESC,cur.license_id DESC LIMIT 1)
                THEN 1 ELSE 0 END AS current_document
            FROM recycler_license_coverage c JOIN recycler_license_evidence d ON d.evidence_id=c.evidence_id
            JOIN recycler_licenses lic ON lic.license_id=d.license_id JOIN waste_categories w ON w.category_id=c.category_id
            WHERE d.recycler_user_id=:owner ORDER BY c.proposed_at DESC,c.coverage_id DESC LIMIT 200",['owner'=>$owner])->fetchAll();
        try { (new RecyclerLicenceAuthorization($this->db))->requireCurrentEvidence($owner); }
        catch (DomainException) { foreach ($rows as &$row) $row['current_document']=0; unset($row); }
        return $rows;
    }

    public function lockScope(int $owner,int $coverage): ?array
    {
        $this->authorize($owner);
        if (Auth::role()!=='ADMIN' || !$this->db->inTransaction()) throw new LogicException('An Administrator transaction is required.');
        return $this->query('SELECT c.*,d.license_id,d.recycler_user_id,d.document_version,d.document_sha256
            FROM recycler_license_coverage c JOIN recycler_license_evidence d ON d.evidence_id=c.evidence_id
            WHERE c.coverage_id=? AND d.recycler_user_id=? FOR UPDATE',[$coverage,$owner])->fetch() ?: null;
    }

    public function reviewHistory(int $owner): array
    {
        $this->authorize($owner);
        return $this->query('SELECT r.* FROM recycler_license_coverage_reviews r
            JOIN recycler_license_coverage c ON c.coverage_id=r.coverage_id
            JOIN recycler_license_evidence d ON d.evidence_id=c.evidence_id WHERE d.recycler_user_id=? ORDER BY r.review_id DESC LIMIT 300',[$owner])->fetchAll();
    }

    public function currentSources(int $owner): array
    {
        $this->authorize($owner);
        return $this->query("SELECT license_id,license_number,expiry_date,license_status,document_version
            FROM recycler_licenses WHERE recycler_user_id=? ORDER BY submitted_at DESC,license_id DESC",[$owner])->fetchAll();
    }

    public function conflicts(int $owner): array
    {
        $this->authorize($owner);
        $authorized=RecyclerLicenceAuthorization::lotSql('b.recycler_user_id');
        try { (new RecyclerLicenceAuthorization($this->db))->requireCurrentEvidence($owner); }
        catch (DomainException) { $authorized='0'; }
        return $this->query("SELECT b.bid_id,b.bid_status,l.e_lot_id,l.lot_code,h.handover_status
            FROM recycler_bids b JOIN e_lots l ON l.e_lot_id=b.e_lot_id
            LEFT JOIN handover_records h ON h.winning_bid_id=b.bid_id
            WHERE b.recycler_user_id=? AND l.lot_status NOT IN ('COMPLETED','CANCELLED')
                AND (b.bid_status='SUBMITTED' OR (b.bid_status='WINNING' AND COALESCE(h.handover_status,'PENDING')<>'COMPLETED'))
                AND NOT ($authorized)
            ORDER BY b.bid_id DESC LIMIT 100",[$owner])->fetchAll();
    }

    public function lotChoices(): array
    {
        if (Auth::role()!=='ADMIN' || (int)Auth::id()<=0) throw new DomainException('Administrator access required.');
        return $this->query("SELECT l.e_lot_id,l.lot_code,l.title,l.lot_status,COALESCE(r.requirement_version,0) AS requirement_version,
            (SELECT GROUP_CONCAT(a.activity_type ORDER BY a.activity_type) FROM e_lot_required_activities a WHERE a.e_lot_id=l.e_lot_id) AS required_activities
            FROM e_lots l LEFT JOIN e_lot_authorization_requirements r ON r.e_lot_id=l.e_lot_id
            WHERE l.lot_status NOT IN ('COMPLETED','CANCELLED') ORDER BY l.e_lot_id DESC")->fetchAll();
    }

    public function all(): array { throw new LogicException('Use owned coverage queries.'); }
    public function find(int|string $id): ?array { throw new LogicException('Use owned coverage queries.'); }
    public function create(array $attributes): int { throw new LogicException('Use the coverage review service.'); }
    public function update(int|string $id,array $attributes): bool { throw new LogicException('Use the coverage review service.'); }
    public function delete(int|string $id): bool { throw new LogicException('Coverage audit history is retained.'); }
}
