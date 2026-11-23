<?php
declare(strict_types=1);
final class CollectionReviewFixture
{
    public static function seed(PDO $db): void
    {
        $month = TestDatabase::seedScheduleActors($db);
        $db->exec("INSERT INTO users (user_id,full_name,mobile_number,password_hash,role) VALUES (4,'Other Officer','0771234570','unused','MUNICIPAL_OFFICER')");
        $db->exec('INSERT INTO municipal_officers VALUES (4)');
        $db->exec("UPDATE users SET account_status='ACTIVE', mobile_verified_at=NOW(), mobile_number=CONCAT('94',SUBSTRING(mobile_number,2))");
        $db->prepare('UPDATE users SET password_hash=?')->execute([password_hash('BrowserTest123!',PASSWORD_DEFAULT)]);
        $db->exec("INSERT INTO waste_categories (category_id,category_name) VALUES (1,'Review category')");
        $db->exec("INSERT INTO e_waste_items (waste_item_id,category_id,item_name) VALUES (1,1,'Laptop'),(2,1,'Monitor'),(3,1,'Printer')");
        $cutoff = (new DateTimeImmutable($month.'-01'))->modify('-1 day')->format('Y-m-d 23:59:59');
        $schedules = new AreaCollectionSchedule($db); $records = new CollectionRecord($db);
        for ($i=1; $i<=26; $i++) {
            $sid = $schedules->create(['campaign_id'=>1,'postal_area_id'=>1,'created_by_officer_user_id'=>$i===5?4:1,
                'collection_date'=>sprintf('%s-%02d',$month,$i),'request_cutoff_at'=>$cutoff,'request_capacity'=>10,'schedule_status'=>'OPEN']);
            $requests=[];
            for ($j=0; $j<($i===1?3:($i===26?5:1)); $j++) {
                $rid = (new EWasteRequest($db))->create(['public_user_id'=>2,'schedule_id'=>$sid,'pickup_address'=>'Review address','request_status'=>'APPROVED']);
                $items=[];
                for ($k=0; $k<($i===1 && $j===0?2:1); $k++) {
                    $db->prepare("INSERT INTO request_items (request_id,waste_item_id,quantity,item_condition,applied_risk_level) VALUES (?,?,2,'WORKING','LOW')")->execute([$rid,$k===1?2:1]);
                    $itemId=(int)$db->lastInsertId();
                    $quantity = $i===1 ? ($j===0?($k===0?'1':'0'):($j===1?'2':'0')) : '2';
                    $weight = $quantity==='0' ? '' : ($i===1?($j===0?'1.250':'2.375'):'1.000');
                    $items[$itemId]=['actual_quantity'=>$quantity,'actual_weight_kg'=>$weight,'actual_condition'=>'WORKING','notes'=>$k===0?'Item <note>':''];
                }
                $requests[]=[$rid,$items];
            }
            $schedules->update($sid,['schedule_status'=>'ASSIGNED']);
            $db->prepare('INSERT INTO schedule_assignments (schedule_id,collector_user_id,assigned_by_officer_user_id) VALUES (?,3,?)')->execute([$sid,$i===5?4:1]);
            foreach ($requests as [$rid,$items]) $records->saveDraft($rid,3,['items'=>$items,'collector_note'=>'<script>alert("collector")</script>']);
            if ($i!==4) $records->submitSchedule($sid,3);
            if ($i===2) (new ScheduleCollection($db))->decide($i,1,'VERIFIED','Verified fixture');
            if ($i===3) (new ScheduleCollection($db))->decide($i,1,'REJECTED','Reason <rejected>');
        }
    }
}
