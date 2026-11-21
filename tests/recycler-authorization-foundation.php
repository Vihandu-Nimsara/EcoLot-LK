<?php
declare(strict_types=1);
// Opt-in disposable schema parity test. Never selects or mutates the development database.
if (getenv('ECOLOT_FOUNDATION_TEST') !== '1') { fwrite(STDERR,"Requires ECOLOT_FOUNDATION_TEST=1\n"); exit(1); }
require __DIR__.'/support/bootstrap.php';
require_once __DIR__.'/support/TestDatabase.php';
$config = require APP_ROOT.'/config/database.php';
$db = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4',$config['host'],$config['port']),$config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$legacy = getenv('ECOLOT_LEGACY_SCHEMA');
if (!$legacy || !is_file($legacy)) throw new RuntimeException('A read-only metadata export is required in ECOLOT_LEGACY_SCHEMA.');
$checks = 0; $files = [];
$check = static function(bool $value,string $why)use(&$checks):void{if(!$value)throw new RuntimeException($why);++$checks;};
$denied = static function(callable $call,string $why)use($check):void{try{$call();}catch(RuntimeException $e){$check(true,$why);return;}throw new RuntimeException('Expected denial: '.$why);};
$exec = static function(string $sql,array $args=[])use($db):void{$s=$db->prepare($sql);$s->execute($args);};
$migration = file_get_contents(APP_ROOT.'/database/migrations/20261119_recycler_authorization_foundation.sql');
try {
    foreach (['fresh','upgraded'] as $kind) {
        $name='ecolot_test_foundation_'.bin2hex(random_bytes(6));
        $db->exec("CREATE DATABASE `$name`");
        try {
            $db->exec("USE `$name`");$db->exec("SET time_zone='+05:30'");
            if ($kind==='fresh') TestDatabase::loadSchema($db);
            else {
                $env=getenv();$env['MYSQL_PWD']=$config['password'];
                $command=['/opt/lampp/bin/mysql','--no-defaults','--host='.$config['host'],'--port='.$config['port'],'--user='.$config['username'],$name];
                $process=proc_open($command,[0=>['file',$legacy,'r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);
                $output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
                if(proc_close($process)!==0)throw new RuntimeException('Legacy metadata import failed: '.$error);
            }
            $db->exec("INSERT INTO users(user_id,full_name,mobile_number,password_hash,role,account_status,mobile_verified_at) VALUES(1,'Test Admin','94779999111','test','ADMIN','ACTIVE',CURRENT_TIMESTAMP),(10,'Test Owner','94779999112','test','RECYCLER','ACTIVE',CURRENT_TIMESTAMP),(20,'Other Owner','94779999113','test','RECYCLER','ACTIVE',CURRENT_TIMESTAMP)");
            $db->exec('INSERT INTO administrators(user_id) VALUES(1)');
            $db->exec("INSERT INTO authorized_recyclers(user_id,company_name,business_address,district,verification_status,reviewed_by_admin_user_id,reviewed_at,review_note) VALUES(10,'Synthetic owner','Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP,'Test registration'),(20,'Other owner','Test address','Colombo','VERIFIED',1,CURRENT_TIMESTAMP,'Test registration')");
            $relative='uploads/recycler-licenses/foundation-'.bin2hex(random_bytes(8)).'.pdf';$path=APP_ROOT.'/storage/'.$relative;
            file_put_contents($path,"%PDF-1.4\nSynthetic foundation document\n%%EOF\n");$files[]=$path;
            $exec("INSERT INTO recycler_licenses(license_id,recycler_user_id,license_number,expiry_date,license_status,document_path) VALUES(100,10,'FOUNDATION-CURRENT','2099-01-01','VALID',?),(200,20,'FOUNDATION-OTHER','2099-01-01','VALID',NULL)",[$relative]);
            $db->exec("INSERT INTO waste_categories(category_id,category_name) VALUES(1,'Domestic'),(2,'Office')");
            $db->exec("INSERT INTO recycler_capabilities(recycler_user_id,category_id,capability_status,can_handle_high_risk) VALUES(10,1,'APPROVED',1),(10,2,'APPROVED',0)");
            $db->exec("INSERT INTO recycler_authorized_activities(recycler_user_id,activity_type,activity_status) VALUES(10,'RECYCLING','APPROVED'),(10,'STORAGE','APPROVED')");
            $legacySnapshot=[];$triggers=$db->query("SELECT TRIGGER_NAME,ACTION_STATEMENT FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME")->fetchAll();
            if($kind==='upgraded') {
                $exec("INSERT INTO recycler_license_evidence(evidence_id,license_id,recycler_user_id,document_version,license_number,expiry_date,document_path,document_sha256,captured_by_admin_user_id) VALUES(1,100,10,1,'FOUNDATION-CURRENT','2099-01-01',?,?,1)",[$relative,hash_file('sha256',$path)]);
                foreach(['VERIFIED','REJECTED','UNCERTAIN','REVOKED'] as $i=>$state){
                    $exec("INSERT INTO recycler_license_coverage(coverage_id,evidence_id,category_id,activity_type,covers_high_risk,authorization_basis,waste_description,evidence_reference,licence_conditions,verification_status,proposed_by_admin_user_id,reviewed_by_admin_user_id,reviewed_at,review_note) VALUES(?,1,1,'RECYCLING',0,'SWML','Test devices','Page 1','Conditions',?,1,1,CURRENT_TIMESTAMP,'Historical decision')",[$i+1,$state]);
                    $exec('INSERT INTO recycler_license_coverage_reviews(coverage_id,decision,admin_user_id,review_note) VALUES(?,?,1,?)',[$i+1,$state,'Historical decision']);
                }
                $exec("INSERT INTO recycler_license_update_requests(request_id,recycler_user_id,current_license_id,requested_license_number,requested_expiry_date,document_path) VALUES(1,10,100,'PENDING-REPLACEMENT','2099-02-01',?)",[$relative]);
                $exec("INSERT INTO recycler_license_revisions(license_id,update_request_id,previous_license_number,previous_expiry_date,previous_document_path,previous_license_status,previous_submitted_at,archived_by_admin_user_id) VALUES(100,1,'OLDER-VERSION','2098-01-01',?,'SUPERSEDED','2026-01-01',1)",[$relative]);
                foreach(['recycler_license_evidence','recycler_license_coverage','recycler_license_coverage_reviews','recycler_license_update_requests','recycler_license_revisions'] as $table)$legacySnapshot[$table]=$db->query("SELECT * FROM `$table`")->fetchAll();
                $db->exec(file_get_contents(APP_ROOT.'/database/migrations/20261116_recycler_initial_scope.sql'));
            }
            $before=$db->query('SELECT * FROM recycler_licenses ORDER BY license_id')->fetchAll();
            $db->exec($migration);$db->exec($migration);
            $check($before===$db->query('SELECT * FROM recycler_licenses ORDER BY license_id')->fetchAll(),$kind.' migration preserves licence identity/history');
            $check((int)$db->query('SELECT COUNT(*) FROM recycler_license_scopes')->fetchColumn()===0,$kind.' no automatic verified scope');
            $check((int)$db->query('SELECT COUNT(*) FROM authorized_recyclers WHERE current_license_id IS NOT NULL')->fetchColumn()===0,$kind.' no automatic legacy licence selection');
            foreach($legacySnapshot as $table=>$rows)$check($rows===$db->query("SELECT * FROM `$table`")->fetchAll(),'Legacy history preserved '.$table);
            $check($triggers===$db->query("SELECT TRIGGER_NAME,ACTION_STATEMENT FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() ORDER BY TRIGGER_NAME")->fetchAll(),$kind.' triggers unchanged');
            $model=new AuthorizedRecycler($db);$scope=new RecyclerLicenseReviewService($db);
            $check($model->currentApprovedLicenseForRecycler(10)===null,$kind.' unselected legacy VALID does not mean approved current');
            $db->exec('UPDATE authorized_recyclers SET current_license_id=100 WHERE user_id=10');
            $check($model->currentApprovedLicenseForRecycler(10)===null,$kind.' selection cannot fabricate reviewer history');
            $denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],false),$kind.' no documentary scope fails closed even with legacy coverage');
            $db->exec('UPDATE recycler_licenses SET reviewed_by_admin_user_id=1,reviewed_at=CURRENT_TIMESTAMP WHERE license_id=100');
            $check((int)$model->currentApprovedLicenseForRecycler(10)['license_id']===100,$kind.' explicitly selected reviewed approval');
            $check(count($model->pendingLicenseReplacementsForRecycler(10))===($kind==='upgraded'?1:0),$kind.' pending request audit read');
            $check(count($model->licenseRevisionHistoryForRecycler(10))===($kind==='upgraded'?1:0),$kind.' legacy revision history stays readable without authorizing');
            $exec("INSERT INTO recycler_licenses(license_id,recycler_user_id,license_number,expiry_date,license_status,submitted_at) VALUES(101,10,'NEW-PENDING','2099-02-01','PENDING','2090-01-01'),(102,10,'NEW-REJECTED','2099-02-01','REJECTED','2091-01-01')");
            $check((int)$model->profileLicenseForRecycler(10)['license_id']===100,$kind.' newer pending/rejected submission cannot replace approval');
            $check(count($model->licenseHistoryForRecycler(10))===3,$kind.' historical/rejected submissions remain readable');
            $exec("INSERT INTO recycler_license_scopes(license_id,category_id,activity_type,covers_high_risk,authorization_basis,waste_description,evidence_reference,licence_conditions,license_number,expiry_date,document_path,document_sha256,scope_status,proposed_by_admin_user_id,reviewed_by_admin_user_id,reviewed_at,review_note) VALUES(100,1,'RECYCLING',1,'SWML','Devices','Page 1','Conditions','FOUNDATION-CURRENT','2099-01-01',?,?,'VERIFIED',1,1,CURRENT_TIMESTAMP,'Test verification')",[$relative,hash_file('sha256',$path)]);
            $scope->requireApprovedScope(10,1,['RECYCLING'],true);$check(true,$kind.' current independently verified scope and approvals pass');
            foreach([
                ["UPDATE recycler_capabilities SET capability_status='PENDING' WHERE recycler_user_id=10 AND category_id=1", "UPDATE recycler_capabilities SET capability_status='APPROVED' WHERE recycler_user_id=10 AND category_id=1",'pending category'],
                ["UPDATE recycler_authorized_activities SET activity_status='PENDING' WHERE recycler_user_id=10 AND activity_type='RECYCLING'", "UPDATE recycler_authorized_activities SET activity_status='APPROVED' WHERE recycler_user_id=10 AND activity_type='RECYCLING'",'pending activity'],
                ["UPDATE users SET account_status='SUSPENDED' WHERE user_id=10", "UPDATE users SET account_status='ACTIVE' WHERE user_id=10",'inactive account'],
                ["UPDATE recycler_license_scopes SET scope_status='REJECTED'", "UPDATE recycler_license_scopes SET scope_status='VERIFIED'",'rejected scope'],
            ] as [$change,$restore,$why]) {
                $db->exec($change);$denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],false),$kind.' '.$why.' denies');$db->exec($restore);
            }

            foreach([
                [fn()=>$scope->requireApprovedScope(10,1,['RECYCLING','STORAGE'],false),'every activity must be covered'],
                [fn()=>$scope->requireApprovedScope(10,2,['RECYCLING'],false),'category must be covered'],
                [fn()=>$scope->requireApprovedScope(10,1,[],false),'empty activity policy denies'],
            ] as [$call,$why])$denied($call,$kind.' '.$why);
            $db->exec('UPDATE recycler_capabilities SET can_handle_high_risk=0 WHERE recycler_user_id=10');
            $denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],true),$kind.' scope alone cannot grant high-risk capability');
            $db->exec('UPDATE recycler_capabilities SET can_handle_high_risk=1 WHERE recycler_user_id=10');
            $db->exec("UPDATE recycler_licenses SET document_path=NULL WHERE license_id=100");
            $denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],false),$kind.' missing PDF fails closed');
            $exec('UPDATE recycler_licenses SET document_path=? WHERE license_id=100',[$relative]);
            file_put_contents($path,"%PDF-1.4\nChanged valid bytes\n%%EOF\n");
            $denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],false),$kind.' stale fingerprint fails closed');
            file_put_contents($path,"%PDF-1.4\nSynthetic foundation document\n%%EOF\n");
            $exec("INSERT INTO recycler_licenses(license_id,recycler_user_id,license_number,expiry_date,license_status,document_path,reviewed_by_admin_user_id,reviewed_at) VALUES(103,10,'REPLACEMENT-APPROVED','2099-01-01','VALID',?,1,CURRENT_TIMESTAMP)",[$relative]);
            $db->exec('UPDATE authorized_recyclers SET current_license_id=103 WHERE user_id=10');
            $denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],false),$kind.' old licence scope never grants replacement scope');
            $db->exec('UPDATE authorized_recyclers SET current_license_id=100 WHERE user_id=10');
            $db->exec("UPDATE recycler_licenses SET expiry_date='2000-01-01' WHERE license_id=100");
            $denied(fn()=>$scope->requireApprovedScope(10,1,['RECYCLING'],false),$kind.' expired selection cannot fall back to other VALID record');
            try{$db->exec('UPDATE authorized_recyclers SET current_license_id=200 WHERE user_id=10');throw new RuntimeException('Foreign pointer allowed');}catch(PDOException $e){$check((int)$e->errorInfo[1]===1452,$kind.' owner-bound selection FK');}
            try{$db->exec("UPDATE recycler_license_scopes SET authorization_basis='EPL' WHERE covers_high_risk=1");throw new RuntimeException('Invalid risk evidence allowed');}catch(PDOException $e){$check(in_array((int)$e->errorInfo[1],[4025,3819],true),$kind.' independent evidence constraint');}
            // Execute the actual seed qualification blocks only, with known synthetic owners.
            $seed=file_get_contents(APP_ROOT.'/database/seed.sql');$start=strpos($seed,'INSERT INTO `recycler_licenses`');$end=strpos($seed,'INSERT INTO `recycler_capabilities`',$start);
            $db->exec('SET @recycler_1_id=10,@recycler_2_id=20');
            // The seed number already exists: replay must not update its authentic fields.
            $db->exec("UPDATE recycler_licenses SET license_number='CEA-EW-2026-0042' WHERE license_id=100");
            $snapshot=$db->query('SELECT * FROM recycler_licenses WHERE license_id=100')->fetch();
            $db->exec(substr($seed,$start,$end-$start));
            $check($snapshot===$db->query('SELECT * FROM recycler_licenses WHERE license_id=100')->fetch(),$kind.' seed replay preserves actual existing licence');
            $db->exec('ALTER TABLE recycler_license_scopes DROP CONSTRAINT chk_scope_evidence');
            $db->exec("UPDATE recycler_license_scopes SET authorization_basis='EPL' WHERE covers_high_risk=1");
            $history=$db->query('SELECT * FROM recycler_license_scopes')->fetchAll();
            try{$db->exec($migration);throw new RuntimeException('Invalid legacy evidence silently accepted');}catch(PDOException $e){$check(in_array((int)$e->errorInfo[1],[4025,3819],true),$kind.' invalid pre-existing scope stops hardening');}
            $check($history===$db->query('SELECT * FROM recycler_license_scopes')->fetchAll(),$kind.' failed hardening preserves decisions');
            echo "PASS $kind foundation selection/scope/migration/history/seed parity\n";
        } finally { $db->exec("DROP DATABASE `$name`"); }
    }
    echo "PASS: $checks authorization foundation assertions\n";
} finally { foreach($files as $file)if(is_file($file))unlink($file); }
