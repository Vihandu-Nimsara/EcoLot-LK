<?php
declare(strict_types=1);
require_once __DIR__.'/CollectionReviewFixture.php';
final class OfficerManagementFixture
{
    public static function seed(PDO $db): array
    {
        CollectionReviewFixture::seed($db);
        $db->exec("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at) VALUES
            (5,'Admin','94771234571','unused','ADMIN','ACTIVE',NOW()),
            (6,'Recycler One','94771234572','unused','RECYCLER','ACTIVE',NOW()),
            (7,'Recycler Two','94771234573','unused','RECYCLER','ACTIVE',NOW())");
        $db->exec('INSERT INTO administrators VALUES (5)');
        $db->prepare('UPDATE users SET password_hash=?')->execute([password_hash('BrowserTest123!',PASSWORD_DEFAULT)]);
        foreach ([6,7] as $uid) {
            $db->prepare("INSERT INTO authorized_recyclers (user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at) VALUES (?,?,'Test','Colombo','VERIFIED',5,NOW())")->execute([$uid,'Recycler Company '.$uid]);
            $db->prepare("INSERT INTO recycler_licenses (recycler_user_id,license_number,expiry_date,license_status,reviewed_by_admin_user_id,reviewed_at) VALUES (?,?,CURRENT_DATE+INTERVAL 1 YEAR,'VALID',5,NOW())")->execute([$uid,'LIC-'.$uid]);
            $db->prepare("INSERT INTO recycler_capabilities (recycler_user_id,category_id,can_handle_high_risk,capability_status,reviewed_by_admin_user_id,reviewed_at) VALUES (?,1,1,'APPROVED',5,NOW())")->execute([$uid]);
        }
        $batches=new ScheduleCollection($db); $lots=new ELot($db); $ids=[];
        foreach ($db->query("SELECT * FROM schedule_collections WHERE verification_status<>'DRAFT' ORDER BY schedule_collection_id")->fetchAll() as $batch) {
            $sid=(int)$batch['schedule_id'];
            if ($batch['verification_status']==='REJECTED') continue;
            if ($batch['verification_status']==='PENDING') $batches->decide((int)$batch['schedule_collection_id'],$sid===5?4:1,'VERIFIED','Fixture source verified');
            $stmt=$db->prepare("SELECT ci.record_item_id FROM collection_record_items ci JOIN collection_records cr USING(collection_record_id) WHERE cr.schedule_collection_id=? AND ci.actual_quantity>0 ORDER BY ci.record_item_id");
            $stmt->execute([$batch['schedule_collection_id']]); $items=array_map('intval',$stmt->fetchAll(PDO::FETCH_COLUMN));
            $lot=$lots->createCollectorLot(3,'Officer lot '.$sid,1,$items);
            $lots->submitCollectorLot($lot,3);
            $ids[$sid]=$lot;
        }
        $db->exec("INSERT INTO complaint_feedback (public_user_id,request_id,feedback_type,message) VALUES
            (2,1,'COMPLAINT','Collection message <script>alert(1)</script>'),
            (2,NULL,'SUGGESTION','General area suggestion'),
            (2,7,'COMPLIMENT','Foreign schedule feedback')");
        for ($i=0;$i<23;$i++) $db->prepare("INSERT INTO complaint_feedback (public_user_id,request_id,feedback_type,message) VALUES (2,1,'SUGGESTION',?)")->execute(['Searchable feedback '.$i]);
        return ['lots'=>$ids,'primary'=>$ids[1],'foreign'=>$ids[5],'second'=>$ids[2]];
    }
}
