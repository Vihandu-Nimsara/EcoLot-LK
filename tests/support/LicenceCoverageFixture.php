<?php
declare(strict_types=1);

/** Synthetic evidence only, for explicitly disposable test databases. */
final class LicenceCoverageFixture
{
    private static array $paths=[];

    public static function pdf(): string
    {
        $relative='uploads/recycler-licenses/scope-fixture-' . bin2hex(random_bytes(10)) . '.pdf';
        $path=APP_ROOT . '/storage/' . $relative;
        if (file_put_contents($path,"%PDF-1.4\n% Synthetic test evidence " . bin2hex(random_bytes(8)) . "\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n")===false) throw new RuntimeException('Cannot write test PDF');
        self::$paths[]=$path; return $relative;
    }

    public static function scope(PDO $db,int $owner,int $admin,int $category,string $activity='RECYCLING',int $risk=1,bool $ensureActivity=true): int
    {
        if (!preg_match('/\Aecolot_(test_|schedule_test_)/',(string)$db->query('SELECT DATABASE()')->fetchColumn())) throw new RuntimeException('Fixture refuses an application database.');
        $auth=Session::get('auth_user');
        Session::put('auth_user',['id'=>$admin,'role'=>'ADMIN']);
        try {
            $license=(new RecyclerLicenceAuthorization($db))->currentLicense($owner);
            if (!$license) {
                $path=self::pdf();
                $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,license_status,document_path) VALUES (?,?,CURRENT_DATE + INTERVAL 1 YEAR,'VALID',?)")->execute([$owner,'TEST-SCOPE-' . bin2hex(random_bytes(8)),$path]);
            } elseif (!$license['document_path'] || (new RecyclerLicenseDocumentValidator())->validatedPath($license['document_path'])===null) {
                $db->prepare('UPDATE recycler_licenses SET document_path=? WHERE license_id=?')->execute([self::pdf(),$license['license_id']]);
            }
            if ($ensureActivity) {
                $s=$db->prepare('SELECT activity_id FROM recycler_authorized_activities WHERE recycler_user_id=? AND activity_type=?'); $s->execute([$owner,$activity]);
                if (!$s->fetchColumn()) $db->prepare("INSERT INTO recycler_authorized_activities (recycler_user_id,activity_type,activity_status,reviewed_by_admin_user_id,reviewed_at) VALUES (?,?,'APPROVED',?,CURRENT_TIMESTAMP)")->execute([$owner,$activity,$admin]);
            }
            $license=(new RecyclerLicenceAuthorization($db))->currentLicense($owner);
            $s=$db->prepare('SELECT c.coverage_id FROM recycler_license_coverage c JOIN recycler_license_evidence d ON d.evidence_id=c.evidence_id WHERE d.license_id=? AND d.document_version=? AND c.category_id=? AND c.activity_type=? AND c.covers_high_risk=? AND c.verification_status=\'VERIFIED\' AND c.superseded_at IS NULL');
            $s->execute([$license['license_id'],$license['document_version'],$category,$activity,$risk]);
            if ($existing=$s->fetchColumn()) return (int)$existing;
            $service=new RecyclerLicenceCoverageService($db);
            $id=$service->propose($owner,$admin,['license_id'=>$license['license_id'],'document_version'=>$license['document_version'],'category_id'=>$category,'activity_type'=>$activity,'covers_high_risk'=>$risk,'authorization_basis'=>'SWML','waste_description'=>'Synthetic fixture waste description','document_waste_code'=>'TEST-ONLY','evidence_reference'=>'Synthetic PDF page 1; simulated human verification, not real CEA evidence','licence_conditions'=>'Synthetic test conditions']);
            $service->review($owner,$id,$admin,'VERIFIED','Synthetic test decision after inspecting fixture evidence');
            return $id;
        } finally {
            if ($auth===null) Session::forget('auth_user'); else Session::put('auth_user',$auth);
        }
    }

    public static function cleanup(): void
    {
        foreach (self::$paths as $path) if (is_file($path)) unlink($path);
        self::$paths=[];
    }
}
