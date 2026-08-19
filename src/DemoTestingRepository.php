<?php

declare(strict_types=1);

final class DemoTestingRepository
{
    public function __construct(private PDO $db) {}

    public function isDemo(int $organizationId): bool
    {
        $q=$this->db->prepare('SELECT COUNT(*) FROM organizations WHERE id=? AND name LIKE "Atlas Full Demo %"');$q->execute([$organizationId]);return (bool)$q->fetchColumn();
    }

    public function dashboard(int $organizationId): array
    {
        $q=$this->db->prepare('SELECT la.username,u.name,m.role,m.status,la.must_change_password FROM local_accounts la JOIN users u ON u.id=la.user_id JOIN memberships m ON m.user_id=u.id AND m.organization_id=la.organization_id WHERE la.organization_id=? ORDER BY FIELD(m.role,"owner","admin","scheduler","supervisor","member"),u.name');$q->execute([$organizationId]);$accounts=$q->fetchAll();
        $counts=[];foreach(['memberships','locations','departments','positions','shifts','time_off_requests','callouts','coverage_assignments','member_credentials','time_entries','message_threads','command_center_items'] as $table){$q=$this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE organization_id=?");$q->execute([$organizationId]);$counts[$table]=(int)$q->fetchColumn();}
        $q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE organization_id=? AND status="open"');$q->execute([$organizationId]);$counts['open_shifts']=(int)$q->fetchColumn();$q=$this->db->prepare('SELECT COUNT(*) FROM time_off_requests WHERE organization_id=? AND status="pending"');$q->execute([$organizationId]);$counts['pending_requests']=(int)$q->fetchColumn();
        return ['is_demo'=>$this->isDemo($organizationId),'accounts'=>$accounts,'counts'=>$counts];
    }

    public function addEdgeCases(int $organizationId,int $userId): int
    {
        if(!$this->isDemo($organizationId))throw new InvalidArgumentException('Edge-case fixtures can only be added to a full demo organization.');$created=0;
        $ids=[];foreach(['locations','departments','positions'] as $table){$q=$this->db->prepare("SELECT id FROM {$table} WHERE organization_id=? ORDER BY id LIMIT 1");$q->execute([$organizationId]);$ids[$table]=(int)$q->fetchColumn();}
        $q=$this->db->prepare('SELECT id FROM memberships WHERE organization_id=? AND status="active" ORDER BY id LIMIT 4');$q->execute([$organizationId]);$members=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));$q=$this->db->prepare('SELECT id FROM shifts WHERE organization_id=? AND status="open" ORDER BY shift_date LIMIT 1');$q->execute([$organizationId]);$openShift=(int)$q->fetchColumn();$q=$this->db->prepare('SELECT id FROM shifts WHERE organization_id=? AND assigned_membership_id IS NOT NULL ORDER BY shift_date LIMIT 2');$q->execute([$organizationId]);$assigned=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        if($openShift&&!empty($members[1])){$q=$this->db->prepare('SELECT COUNT(*) FROM shift_requests WHERE shift_id=? AND membership_id=?');$q->execute([$openShift,$members[1]]);if(!$q->fetchColumn()){$this->db->prepare('INSERT INTO shift_requests (organization_id,shift_id,membership_id,status,eligibility_result,eligibility_reasons) VALUES (?,?,?,"pending","approval",?)')->execute([$organizationId,$openShift,$members[1],json_encode(['Demo fixture: cross-department approval required.'])]);$created++;}}
        if(count($assigned)>=2&&count($members)>=3){$q=$this->db->prepare('SELECT COUNT(*) FROM shift_change_requests WHERE organization_id=? AND status="pending_manager"');$q->execute([$organizationId]);if(!$q->fetchColumn()){$this->db->prepare('INSERT INTO shift_change_requests (organization_id,requester_membership_id,offered_shift_id,recipient_membership_id,requested_shift_id,request_type,employee_note,status,eligibility_result,eligibility_reasons,expires_at) VALUES (?,?,?,?,?,"trade","Demo trade awaiting manager review","pending_manager","eligible",?,DATE_ADD(NOW(),INTERVAL 7 DAY))')->execute([$organizationId,$members[1],$assigned[0],$members[2],$assigned[1],json_encode(['Demo fixture: eligible trade.'])]);$created++;}}
        $q=$this->db->prepare('SELECT COUNT(*) FROM command_center_items WHERE organization_id=? AND title="Demo critical coverage gap"');$q->execute([$organizationId]);if(!$q->fetchColumn()){$this->db->prepare('INSERT INTO command_center_items (organization_id,item_type,title,urgency_score,owner_membership_id,due_at,status) VALUES (?,"coverage_gap","Demo critical coverage gap",95,?,DATE_ADD(NOW(),INTERVAL 2 HOUR),"open"),(?,"approval","Demo request awaiting review",70,?,DATE_ADD(NOW(),INTERVAL 1 DAY),"open")')->execute([$organizationId,$members[0]??null,$organizationId,$members[0]??null]);$created+=2;}
        if($ids['locations']&&$ids['departments']){$q=$this->db->prepare('SELECT COUNT(*) FROM coverage_demand_forecasts WHERE organization_id=? AND forecast_date=DATE_ADD(CURDATE(),INTERVAL 1 DAY)');$q->execute([$organizationId]);if(!$q->fetchColumn()){$this->db->prepare('INSERT INTO coverage_demand_forecasts (organization_id,location_id,department_id,forecast_date,starts_at,ends_at,required_count,required_skill_mix,float_pool_count,break_coverage_count,created_by) VALUES (?,?,?,DATE_ADD(CURDATE(),INTERVAL 1 DAY),"08:00","16:30",8,"2 clinical leads, 4 MAs, 2 flex",1,1,?)')->execute([$organizationId,$ids['locations'],$ids['departments'],$userId]);$created++;}}
        $q=$this->db->prepare('SELECT id FROM member_credentials WHERE organization_id=? ORDER BY id LIMIT 1');$q->execute([$organizationId]);$credential=(int)$q->fetchColumn();if($credential){$this->db->prepare('UPDATE member_credentials SET expires_on=DATE_SUB(CURDATE(),INTERVAL 5 DAY),status="expired" WHERE id=?')->execute([$credential]);}
        $q=$this->db->prepare('SELECT id FROM time_entries WHERE organization_id=? ORDER BY id LIMIT 1');$q->execute([$organizationId]);$entry=(int)$q->fetchColumn();if($entry){$this->db->prepare('UPDATE time_entries SET clocked_out_at=NULL,status="open",employee_note="Demo missed punch requiring review" WHERE id=?')->execute([$entry]);}
        $this->db->prepare('INSERT INTO audit_logs (organization_id,user_id,action,entity_type,entity_id,metadata_json) VALUES (?, ?, "demo.edge_cases_refreshed", "organization", ?, ?)')->execute([$organizationId,$userId,$organizationId,json_encode(['created'=>$created])]);return $created;
    }

    public function delete(int $organizationId,int $userId): void
    {
        if(!$this->isDemo($organizationId))throw new InvalidArgumentException('Only full demo organizations can be deleted here.');$q=$this->db->prepare('SELECT role FROM memberships WHERE organization_id=? AND user_id=? AND status="active"');$q->execute([$organizationId,$userId]);if($q->fetchColumn()!=='owner')throw new InvalidArgumentException('Only the demo organization owner can delete it.');$q=$this->db->prepare('SELECT user_id FROM local_accounts WHERE organization_id=?');$q->execute([$organizationId]);$localUsers=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));$this->db->beginTransaction();try{$this->db->prepare('DELETE FROM organizations WHERE id=?')->execute([$organizationId]);$delete=$this->db->prepare('DELETE FROM users WHERE id=? AND NOT EXISTS (SELECT 1 FROM memberships WHERE user_id=users.id)');foreach($localUsers as $localUser)$delete->execute([$localUser]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
}
