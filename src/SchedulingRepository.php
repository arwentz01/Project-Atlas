<?php
declare(strict_types=1);

final class SchedulingRepository
{
    public function __construct(private PDO $db) {}

    public function catalog(int $organizationId): array
    {
        $data=[];
        foreach(['providers','teams','work_functions','stations','qualifications','eligibility_groups'] as $table){
            $q=$this->db->prepare("SELECT * FROM {$table} WHERE organization_id=? AND active=1 ORDER BY name"); $q->execute([$organizationId]); $data[$table]=$q->fetchAll();
        }
        $q=$this->db->prepare('SELECT egp.eligibility_group_id,p.id,p.name FROM eligibility_group_positions egp JOIN positions p ON p.id=egp.position_id WHERE p.organization_id=? ORDER BY p.name'); $q->execute([$organizationId]);
        $data['group_positions']=[]; foreach($q->fetchAll() as $row) $data['group_positions'][$row['eligibility_group_id']][]=$row;
        return $data;
    }

    public function addCatalogItem(int $organizationId,int $userId,string $type,array $input): void
    {
        $name=trim((string)($input['name']??'')); if($name==='') throw new InvalidArgumentException('A name is required.');
        $department=$this->owned('departments',$organizationId,$input['department_id']??null);
        $location=$this->owned('locations',$organizationId,$input['location_id']??null);
        if($type==='provider'){$q=$this->db->prepare('INSERT INTO providers (organization_id,location_id,department_id,name,specialty) VALUES (?,?,?,?,?)');$q->execute([$organizationId,$location,$department,$name,trim((string)($input['specialty']??''))?:null]);}
        elseif($type==='team'){$q=$this->db->prepare('INSERT INTO teams (organization_id,department_id,name) VALUES (?,?,?)');$q->execute([$organizationId,$department,$name]);}
        elseif($type==='function'){$q=$this->db->prepare('INSERT INTO work_functions (organization_id,department_id,name,color) VALUES (?,?,?,?)');$q->execute([$organizationId,$department,$name,$input['color']??'#0d9a7c']);}
        elseif($type==='station'){$q=$this->db->prepare('INSERT INTO stations (organization_id,location_id,department_id,name) VALUES (?,?,?,?)');$q->execute([$organizationId,$location,$department,$name]);}
        elseif($type==='qualification'){$q=$this->db->prepare('INSERT INTO qualifications (organization_id,name) VALUES (?,?)');$q->execute([$organizationId,$name]);}
        elseif($type==='eligibility_group'){
            $this->db->beginTransaction(); try{$q=$this->db->prepare('INSERT INTO eligibility_groups (organization_id,name,description) VALUES (?,?,?)');$q->execute([$organizationId,$name,trim((string)($input['description']??''))?:null]);$id=(int)$this->db->lastInsertId();$insert=$this->db->prepare('INSERT INTO eligibility_group_positions (eligibility_group_id,position_id) VALUES (?,?)');foreach((array)($input['position_ids']??[]) as $pid){$owned=$this->owned('positions',$organizationId,$pid);if($owned)$insert->execute([$id,$owned]);}$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
        } else throw new InvalidArgumentException('Unknown catalog type.');
        $this->audit($organizationId,$userId,$type.'.created',$name);
    }

    public function updateStaff(int $organizationId,int $userId,array $input): void
    {
        $membership=$this->membership($organizationId,$input['membership_id']??null);
        $department=$this->owned('departments',$organizationId,$input['department_id']??null);
        $position=$this->owned('positions',$organizationId,$input['position_id']??null);
        $location=$this->owned('locations',$organizationId,$input['location_id']??null);
        $group=null;if($department){$q=$this->db->prepare('SELECT default_supervisor_group_id FROM departments WHERE id=? AND organization_id=?');$q->execute([$department,$organizationId]);$group=$q->fetchColumn()?:null;}
        $this->db->beginTransaction();try{
            $q=$this->db->prepare('INSERT INTO staff_assignments (organization_id,membership_id,location_id,department_id,position_id,supervisor_group_id,is_primary) VALUES (?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE location_id=VALUES(location_id),department_id=VALUES(department_id),position_id=VALUES(position_id),supervisor_group_id=VALUES(supervisor_group_id)');$q->execute([$organizationId,$membership,$location,$department,$position,$group]);
            $q=$this->db->prepare('INSERT INTO workforce_profiles (membership_id,organization_id,employment_type,expected_weekly_hours,flex_eligible,opening_eligible,closing_eligible) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE employment_type=VALUES(employment_type),expected_weekly_hours=VALUES(expected_weekly_hours),flex_eligible=VALUES(flex_eligible),opening_eligible=VALUES(opening_eligible),closing_eligible=VALUES(closing_eligible)');$q->execute([$membership,$organizationId,$input['employment_type']??'full_time',(float)($input['expected_weekly_hours']??0)?:null,!empty($input['flex_eligible']),!empty($input['opening_eligible']),!empty($input['closing_eligible'])]);
            $this->db->prepare('DELETE sq FROM staff_qualifications sq JOIN qualifications q ON q.id=sq.qualification_id WHERE sq.membership_id=? AND q.organization_id=?')->execute([$membership,$organizationId]);
            $qualificationInsert=$this->db->prepare('INSERT INTO staff_qualifications (membership_id,qualification_id,verified_at) VALUES (?,?,NOW())');
            foreach((array)($input['qualification_ids']??[]) as $qualificationId){$owned=$this->owned('qualifications',$organizationId,$qualificationId);if($owned)$qualificationInsert->execute([$membership,$owned]);}
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
        $this->audit($organizationId,$userId,'staff.updated',(string)$membership);
    }

    public function shifts(int $organizationId,?string $date=null): array
    {
        $sql='SELECT s.*,l.name location_name,d.name department_name,p.name exact_position_name,eg.name eligibility_group_name,u.name assigned_name FROM shifts s JOIN locations l ON l.id=s.location_id JOIN departments d ON d.id=s.department_id LEFT JOIN positions p ON p.id=s.exact_position_id LEFT JOIN eligibility_groups eg ON eg.id=s.eligibility_group_id LEFT JOIN memberships m ON m.id=s.assigned_membership_id LEFT JOIN users u ON u.id=m.user_id WHERE s.organization_id=?';$args=[$organizationId];if($date){$sql.=' AND s.shift_date=?';$args[]=$date;}$sql.=' ORDER BY s.shift_date,s.starts_at';$q=$this->db->prepare($sql);$q->execute($args);$rows=$q->fetchAll();
        $ids=array_column($rows,'id');if($ids){$marks=implode(',',array_fill(0,count($ids),'?'));$q=$this->db->prepare("SELECT sep.shift_id,p.name FROM shift_eligible_positions sep JOIN positions p ON p.id=sep.position_id WHERE sep.shift_id IN ({$marks}) ORDER BY p.name");$q->execute($ids);$selected=[];foreach($q->fetchAll() as $r)$selected[$r['shift_id']][]=$r['name'];foreach($rows as &$r)$r['selected_position_names']=$selected[$r['id']]??[];}
        return $rows;
    }

    public function createShift(int $organizationId,int $userId,array $input): int
    {
        $location=$this->owned('locations',$organizationId,$input['location_id']??null,true);$department=$this->owned('departments',$organizationId,$input['department_id']??null,true);$mode=$input['eligibility_mode']??'exact';if(!in_array($mode,['exact','selected','group'],true))throw new InvalidArgumentException('Choose a valid eligibility mode.');
        $exact=$mode==='exact'?$this->owned('positions',$organizationId,$input['exact_position_id']??null,true):null;$group=$mode==='group'?$this->owned('eligibility_groups',$organizationId,$input['eligibility_group_id']??null,true):null;
        if(empty($input['shift_date'])||empty($input['starts_at'])||empty($input['ends_at']))throw new InvalidArgumentException('Shift date, start time, and end time are required.');
        $this->db->beginTransaction();try{$q=$this->db->prepare('INSERT INTO shifts (organization_id,location_id,department_id,shift_date,starts_at,ends_at,status,eligibility_mode,exact_position_id,eligibility_group_id,cross_department_mode,notes,created_by) VALUES (?,?,?,?,?,?,"open",?,?,?,?,?,?)');$q->execute([$organizationId,$location,$department,$input['shift_date'],$input['starts_at'],$input['ends_at'],$mode,$exact,$group,$input['cross_department_mode']??'prohibited',trim((string)($input['notes']??''))?:null,$userId]);$id=(int)$this->db->lastInsertId();if($mode==='selected'){$insert=$this->db->prepare('INSERT INTO shift_eligible_positions (shift_id,position_id) VALUES (?,?)');$count=0;foreach((array)($input['position_ids']??[]) as $pid){$owned=$this->owned('positions',$organizationId,$pid);if($owned){$insert->execute([$id,$owned]);$count++;}}if(!$count)throw new InvalidArgumentException('Select at least one eligible position.');}foreach((array)($input['qualification_ids']??[]) as $qid){$owned=$this->owned('qualifications',$organizationId,$qid);if($owned)$this->db->prepare('INSERT INTO shift_required_qualifications (shift_id,qualification_id) VALUES (?,?)')->execute([$id,$owned]);}$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}$this->audit($organizationId,$userId,'shift.created',(string)$id);return $id;
    }

    public function eligibility(int $organizationId,int $membershipId,int $shiftId): array
    {
        $q=$this->db->prepare('SELECT s.*,sa.position_id,sa.department_id staff_department_id,sa.location_id staff_location_id FROM shifts s JOIN memberships m ON m.id=? AND m.organization_id=s.organization_id AND m.status="active" LEFT JOIN staff_assignments sa ON sa.membership_id=m.id AND sa.is_primary=1 WHERE s.id=? AND s.organization_id=?');$q->execute([$membershipId,$shiftId,$organizationId]);$x=$q->fetch();if(!$x)return ['result'=>'ineligible','reasons'=>['Membership or shift unavailable.']];$reasons=[];$approval=false;
        $eligiblePosition=false;if($x['eligibility_mode']==='exact')$eligiblePosition=(int)$x['position_id']===(int)$x['exact_position_id'];elseif($x['eligibility_mode']==='selected'){$q=$this->db->prepare('SELECT COUNT(*) FROM shift_eligible_positions WHERE shift_id=? AND position_id=?');$q->execute([$shiftId,$x['position_id']]);$eligiblePosition=(bool)$q->fetchColumn();}else{$q=$this->db->prepare('SELECT COUNT(*) FROM eligibility_group_positions WHERE eligibility_group_id=? AND position_id=?');$q->execute([$x['eligibility_group_id'],$x['position_id']]);$eligiblePosition=(bool)$q->fetchColumn();}if(!$eligiblePosition)$reasons[]='Your position is not eligible for this shift.';
        if((int)$x['staff_location_id']!==(int)$x['location_id'])$reasons[]='You are not assigned to this location.';
        if((int)$x['staff_department_id']!==(int)$x['department_id']){if($x['cross_department_mode']==='prohibited')$reasons[]='Cross-department coverage is not allowed.';elseif($x['cross_department_mode']==='approval'){$approval=true;$reasons[]='Supervisor approval is required for cross-department coverage.';}}
        $q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE organization_id=? AND assigned_membership_id=? AND shift_date=? AND status IN ("assigned","filled") AND starts_at < ? AND ends_at > ?');$q->execute([$organizationId,$membershipId,$x['shift_date'],$x['ends_at'],$x['starts_at']]);if($q->fetchColumn())$reasons[]='This shift overlaps your existing schedule.';
        $q=$this->db->prepare('SELECT q.name FROM shift_required_qualifications srq JOIN qualifications q ON q.id=srq.qualification_id LEFT JOIN staff_qualifications sq ON sq.qualification_id=q.id AND sq.membership_id=? AND (sq.expires_on IS NULL OR sq.expires_on>=?) WHERE srq.shift_id=? AND sq.membership_id IS NULL');$q->execute([$membershipId,$x['shift_date'],$shiftId]);$missing=$q->fetchAll(PDO::FETCH_COLUMN);if($missing)$reasons[]='Missing qualification: '.implode(', ',$missing).'.';
        $hard=array_filter($reasons,fn($r)=>!str_contains($r,'approval'));return ['result'=>$hard?'ineligible':($approval?'approval':'eligible'),'reasons'=>$reasons?:['You meet the configured requirements.']];
    }

    public function requestShift(int $organizationId,int $membershipId,int $shiftId): array
    {
        $result=$this->eligibility($organizationId,$membershipId,$shiftId);if($result['result']==='ineligible')throw new InvalidArgumentException(implode(' ',$result['reasons']));$q=$this->db->prepare('INSERT INTO shift_requests (organization_id,shift_id,membership_id,eligibility_result,eligibility_reasons) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE status="pending",eligibility_result=VALUES(eligibility_result),eligibility_reasons=VALUES(eligibility_reasons)');$q->execute([$organizationId,$shiftId,$membershipId,$result['result'],json_encode($result['reasons'])]);return $result;
    }

    public function addCoverage(int $organizationId,int $userId,array $input): void
    {
        $shift=$this->owned('shifts',$organizationId,$input['shift_id']??null,true);$member=$this->membership($organizationId,$input['membership_id']??null);$provider=$this->owned('providers',$organizationId,$input['provider_id']??null);$station=$this->owned('stations',$organizationId,$input['station_id']??null);$function=$this->owned('work_functions',$organizationId,$input['work_function_id']??null);if(!$provider&&!$station&&!$function)throw new InvalidArgumentException('Select a provider, station, or work function.');$q=$this->db->prepare('INSERT INTO coverage_assignments (organization_id,shift_id,membership_id,provider_id,station_id,work_function_id,coverage_type,starts_at,ends_at,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');$q->execute([$organizationId,$shift,$member,$provider,$station,$function,$input['coverage_type']??'primary',!empty($input['starts_at'])?$input['starts_at']:null,!empty($input['ends_at'])?$input['ends_at']:null,$userId]);$this->db->prepare('UPDATE shifts SET assigned_membership_id=?,status="filled" WHERE id=? AND organization_id=?')->execute([$member,$shift,$organizationId]);$this->audit($organizationId,$userId,'coverage.created',(string)$shift);
    }

    public function coverage(int $organizationId,string $date): array
    {
        $q=$this->db->prepare('SELECT ca.*,s.shift_date,s.starts_at shift_start,s.ends_at shift_end,u.name staff_name,p.name provider_name,st.name station_name,wf.name function_name,d.name department_name FROM coverage_assignments ca JOIN shifts s ON s.id=ca.shift_id JOIN memberships m ON m.id=ca.membership_id JOIN users u ON u.id=m.user_id JOIN departments d ON d.id=s.department_id LEFT JOIN providers p ON p.id=ca.provider_id LEFT JOIN stations st ON st.id=ca.station_id LEFT JOIN work_functions wf ON wf.id=ca.work_function_id WHERE ca.organization_id=? AND s.shift_date=? ORDER BY s.starts_at,u.name');$q->execute([$organizationId,$date]);return $q->fetchAll();
    }

    private function membership(int $organizationId,mixed $id): int{$q=$this->db->prepare('SELECT id FROM memberships WHERE id=? AND organization_id=? AND status="active"');$q->execute([(int)$id,$organizationId]);$found=$q->fetchColumn();if(!$found)throw new InvalidArgumentException('Staff member unavailable.');return (int)$found;}
    private function owned(string $table,int $organizationId,mixed $id,bool $required=false): ?int{if(!$id){if($required)throw new InvalidArgumentException('A required organization resource is missing.');return null;}$allowed=['locations','departments','positions','providers','stations','work_functions','qualifications','eligibility_groups','shifts'];if(!in_array($table,$allowed,true))throw new LogicException('Invalid resource type.');$q=$this->db->prepare("SELECT id FROM {$table} WHERE id=? AND organization_id=?".($table!=='shifts'?' AND active=1':''));$q->execute([(int)$id,$organizationId]);$found=$q->fetchColumn();if(!$found)throw new InvalidArgumentException('A selected organization resource is unavailable.');return (int)$found;}
    private function audit(int $oid,int $uid,string $action,string $value):void{$q=$this->db->prepare('INSERT INTO audit_logs (organization_id,user_id,action,entity_type,metadata_json,ip_address) VALUES (?, ?, ?, "scheduling", ?, ?)');$q->execute([$oid,$uid,$action,json_encode(['value'=>$value]),$_SERVER['REMOTE_ADDR']??null]);}
}
