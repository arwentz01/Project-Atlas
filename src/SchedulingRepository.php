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

    public function eligibility(int $organizationId,int $membershipId,int $shiftId,?int $ignoreShiftId=null): array
    {
        $q=$this->db->prepare('SELECT s.*,sa.position_id,sa.department_id staff_department_id,sa.location_id staff_location_id FROM shifts s JOIN memberships m ON m.id=? AND m.organization_id=s.organization_id AND m.status="active" LEFT JOIN staff_assignments sa ON sa.membership_id=m.id AND sa.is_primary=1 WHERE s.id=? AND s.organization_id=?');$q->execute([$membershipId,$shiftId,$organizationId]);$x=$q->fetch();if(!$x)return ['result'=>'ineligible','reasons'=>['Membership or shift unavailable.']];$reasons=[];$approval=false;
        $eligiblePosition=false;if($x['eligibility_mode']==='exact')$eligiblePosition=(int)$x['position_id']===(int)$x['exact_position_id'];elseif($x['eligibility_mode']==='selected'){$q=$this->db->prepare('SELECT COUNT(*) FROM shift_eligible_positions WHERE shift_id=? AND position_id=?');$q->execute([$shiftId,$x['position_id']]);$eligiblePosition=(bool)$q->fetchColumn();}else{$q=$this->db->prepare('SELECT COUNT(*) FROM eligibility_group_positions WHERE eligibility_group_id=? AND position_id=?');$q->execute([$x['eligibility_group_id'],$x['position_id']]);$eligiblePosition=(bool)$q->fetchColumn();}if(!$eligiblePosition)$reasons[]='Your position is not eligible for this shift.';
        if((int)$x['staff_location_id']!==(int)$x['location_id'])$reasons[]='You are not assigned to this location.';
        if((int)$x['staff_department_id']!==(int)$x['department_id']){if($x['cross_department_mode']==='prohibited')$reasons[]='Cross-department coverage is not allowed.';elseif($x['cross_department_mode']==='approval'){$approval=true;$reasons[]='Supervisor approval is required for cross-department coverage.';}}
        $q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE organization_id=? AND assigned_membership_id=? AND shift_date=? AND status IN ("assigned","filled") AND starts_at < ? AND ends_at > ? AND id<>?');$q->execute([$organizationId,$membershipId,$x['shift_date'],$x['ends_at'],$x['starts_at'],$ignoreShiftId??0]);if($q->fetchColumn())$reasons[]='This shift overlaps your existing schedule.';
        $weekday=(int)(new DateTimeImmutable($x['shift_date']))->format('N');$q=$this->db->prepare('SELECT COUNT(*) FROM availability_entries WHERE organization_id=? AND membership_id=? AND status="approved" AND availability="unavailable" AND ((entry_type="date_exception" AND applies_on=?) OR (entry_type="recurring" AND weekday=?)) AND (starts_at IS NULL OR starts_at < ?) AND (ends_at IS NULL OR ends_at > ?)');$q->execute([$organizationId,$membershipId,$x['shift_date'],$weekday,$x['ends_at'],$x['starts_at']]);if($q->fetchColumn())$reasons[]='This shift conflicts with your approved availability.';
        $q=$this->db->prepare('SELECT COUNT(*) FROM time_off_requests WHERE organization_id=? AND membership_id=? AND status="approved" AND ? BETWEEN starts_on AND ends_on AND (starts_at IS NULL OR (starts_at < ? AND ends_at > ?))');$q->execute([$organizationId,$membershipId,$x['shift_date'],$x['ends_at'],$x['starts_at']]);if($q->fetchColumn())$reasons[]='This shift conflicts with approved time off.';
        $q=$this->db->prepare('SELECT maximum_weekly_hours FROM workforce_preferences WHERE organization_id=? AND membership_id=?');$q->execute([$organizationId,$membershipId]);$maximum=(float)($q->fetchColumn()?:0);if($maximum>0){$monday=(new DateTimeImmutable($x['shift_date']))->modify('monday this week')->format('Y-m-d');$q=$this->db->prepare('SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(ends_at,starts_at))/3600),0) FROM shifts WHERE organization_id=? AND assigned_membership_id=? AND shift_date BETWEEN ? AND DATE_ADD(?,INTERVAL 6 DAY) AND status IN ("assigned","filled")');$q->execute([$organizationId,$membershipId,$monday,$monday]);$scheduled=(float)$q->fetchColumn();$duration=((strtotime($x['ends_at'])-strtotime($x['starts_at']))/3600);if($scheduled+$duration>$maximum){$approval=true;$reasons[]='Supervisor approval is required because this exceeds your preferred weekly maximum.';}}
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

    public function periods(int $oid): array
    {
        $q=$this->db->prepare('SELECT sp.*,(SELECT COUNT(*) FROM shifts s WHERE s.schedule_period_id=sp.id AND s.status<>"cancelled") shift_count FROM schedule_periods sp WHERE sp.organization_id=? ORDER BY sp.starts_on DESC');$q->execute([$oid]);return $q->fetchAll();
    }

    public function createPeriod(int $oid,int $uid,array $in): void
    {
        $name=trim((string)($in['name']??''));$start=(string)($in['starts_on']??'');$end=(string)($in['ends_on']??'');if($name===''||!$start||!$end||$end<$start)throw new InvalidArgumentException('Enter a name and a valid date range.');$q=$this->db->prepare('INSERT INTO schedule_periods (organization_id,name,starts_on,ends_on,status,created_by) VALUES (?,?,?,?,"draft",?)');$q->execute([$oid,$name,$start,$end,$uid]);$this->audit($oid,$uid,'period.created',(string)$this->db->lastInsertId());
    }

    public function setPeriodStatus(int $oid,int $uid,int $id,string $status): void
    {
        if(!in_array($status,['draft','open','review','published','archived'],true))throw new InvalidArgumentException('Invalid schedule status.');$q=$this->db->prepare('UPDATE schedule_periods SET status=? WHERE id=? AND organization_id=?');$q->execute([$status,$id,$oid]);if(!$q->rowCount())throw new InvalidArgumentException('Schedule period unavailable.');if($status==='published')$this->db->prepare('UPDATE shifts SET status=IF(assigned_membership_id IS NULL,"open","assigned") WHERE schedule_period_id=? AND organization_id=? AND status<>"cancelled"')->execute([$id,$oid]);$this->audit($oid,$uid,'period.'.$status,(string)$id);
    }

    public function assignShift(int $oid,int $uid,int $shiftId,int $memberId,bool $override,string $reason=''): void
    {
        $shift=$this->owned('shifts',$oid,$shiftId,true);$member=$this->membership($oid,$memberId);$elig=$this->eligibility($oid,$member,$shift);if($elig['result']!=='eligible'&&!$override)throw new InvalidArgumentException(implode(' ',$elig['reasons']).' A manager override is required.');if($override&&trim($reason)==='')throw new InvalidArgumentException('Enter a reason for the manager override.');$this->db->prepare('UPDATE shifts SET assigned_membership_id=?,status="assigned" WHERE id=? AND organization_id=?')->execute([$member,$shift,$oid]);$this->audit($oid,$uid,$override?'shift.override_assigned':'shift.assigned',$shift.($reason?': '.$reason:''));
    }

    public function cancelShift(int $oid,int $uid,int $shiftId): void
    {
        $shift=$this->owned('shifts',$oid,$shiftId,true);$this->db->prepare('UPDATE shifts SET status="cancelled" WHERE id=? AND organization_id=?')->execute([$shift,$oid]);$this->audit($oid,$uid,'shift.cancelled',(string)$shift);
    }

    public function copyWeek(int $oid,int $uid,string $source,string $target): int
    {
        if(!$source||!$target)throw new InvalidArgumentException('Choose source and destination weeks.');$days=(int)(new DateTimeImmutable($source))->diff(new DateTimeImmutable($target))->format('%r%a');$q=$this->db->prepare('SELECT * FROM shifts WHERE organization_id=? AND shift_date BETWEEN ? AND DATE_ADD(?,INTERVAL 6 DAY) AND status<>"cancelled"');$q->execute([$oid,$source,$source]);$count=0;$this->db->beginTransaction();try{foreach($q->fetchAll() as $r){$date=(new DateTimeImmutable($r['shift_date']))->modify(($days>=0?'+':'').$days.' days')->format('Y-m-d');$i=$this->db->prepare('INSERT INTO shifts (organization_id,location_id,department_id,assigned_membership_id,shift_date,starts_at,ends_at,status,eligibility_mode,exact_position_id,eligibility_group_id,cross_department_mode,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$i->execute([$oid,$r['location_id'],$r['department_id'],$r['assigned_membership_id'],$date,$r['starts_at'],$r['ends_at'],$r['assigned_membership_id']?'assigned':'open',$r['eligibility_mode'],$r['exact_position_id'],$r['eligibility_group_id'],$r['cross_department_mode'],$r['notes'],$uid]);$new=(int)$this->db->lastInsertId();$this->db->prepare('INSERT INTO shift_eligible_positions SELECT ?,position_id FROM shift_eligible_positions WHERE shift_id=?')->execute([$new,$r['id']]);$this->db->prepare('INSERT INTO shift_required_qualifications SELECT ?,qualification_id FROM shift_required_qualifications WHERE shift_id=?')->execute([$new,$r['id']]);$count++;}$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}$this->audit($oid,$uid,'week.copied',(string)$count);return $count;
    }

    public function requests(int $oid): array
    {
        $q=$this->db->prepare('SELECT sr.*,u.name staff_name,s.shift_date,s.starts_at,s.ends_at,d.name department_name FROM shift_requests sr JOIN memberships m ON m.id=sr.membership_id JOIN users u ON u.id=m.user_id JOIN shifts s ON s.id=sr.shift_id JOIN departments d ON d.id=s.department_id WHERE sr.organization_id=? ORDER BY FIELD(sr.status,"pending","approved","denied","withdrawn"),sr.created_at DESC');$q->execute([$oid]);return $q->fetchAll();
    }

    public function reviewRequest(int $oid,int $uid,int $requestId,string $decision): void
    {
        if(!in_array($decision,['approved','denied'],true))throw new InvalidArgumentException('Invalid review decision.');$q=$this->db->prepare('SELECT * FROM shift_requests WHERE id=? AND organization_id=? AND status="pending"');$q->execute([$requestId,$oid]);$r=$q->fetch();if(!$r)throw new InvalidArgumentException('Pending request unavailable.');$this->db->beginTransaction();try{$this->db->prepare('UPDATE shift_requests SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$decision,$uid,$requestId]);if($decision==='approved')$this->db->prepare('UPDATE shifts SET assigned_membership_id=?,status="assigned" WHERE id=? AND organization_id=?')->execute([$r['membership_id'],$r['shift_id'],$oid]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}$this->audit($oid,$uid,'request.'.$decision,(string)$requestId);
    }

    public function memberSchedule(int $oid,int $memberId): array
    {
        $q=$this->db->prepare('SELECT s.*,l.name location_name,d.name department_name FROM shifts s JOIN locations l ON l.id=s.location_id JOIN departments d ON d.id=s.department_id LEFT JOIN master_schedule_generation_items mgi ON mgi.shift_id=s.id LEFT JOIN master_generation_publications mgp ON mgp.generation_id=mgi.generation_id WHERE s.organization_id=? AND s.assigned_membership_id=? AND s.status IN ("assigned","filled") AND (mgi.id IS NULL OR mgp.status="published") ORDER BY s.shift_date,s.starts_at');$q->execute([$oid,$memberId]);return $q->fetchAll();
    }

    public function addProviderSession(int $oid,int $uid,array $in): void
    {
        $provider=$this->owned('providers',$oid,$in['provider_id']??null,true);$location=$this->owned('locations',$oid,$in['location_id']??null,true);$department=$this->owned('departments',$oid,$in['department_id']??null,true);if(empty($in['session_date'])||empty($in['starts_at'])||empty($in['ends_at']))throw new InvalidArgumentException('Session date and times are required.');$q=$this->db->prepare('INSERT INTO provider_sessions (organization_id,provider_id,location_id,department_id,session_date,starts_at,ends_at,support_count,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');$q->execute([$oid,$provider,$location,$department,$in['session_date'],$in['starts_at'],$in['ends_at'],max(1,(int)($in['support_count']??1)),trim((string)($in['notes']??''))?:null,$uid]);$this->audit($oid,$uid,'provider_session.created',(string)$this->db->lastInsertId());
    }

    public function providerSessions(int $oid,?string $date=null): array
    {
        $sql='SELECT ps.*,p.name provider_name,l.name location_name,d.name department_name,(SELECT COUNT(*) FROM coverage_assignments ca JOIN shifts s ON s.id=ca.shift_id WHERE ca.provider_id=ps.provider_id AND s.shift_date=ps.session_date) assigned_support FROM provider_sessions ps JOIN providers p ON p.id=ps.provider_id JOIN locations l ON l.id=ps.location_id JOIN departments d ON d.id=ps.department_id WHERE ps.organization_id=?';$args=[$oid];if($date){$sql.=' AND ps.session_date=?';$args[]=$date;}$sql.=' ORDER BY ps.session_date,ps.starts_at';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function createRotation(int $oid,int $uid,array $in): void
    {
        $member=!empty($in['membership_id'])?$this->membership($oid,$in['membership_id']):null;$location=$this->owned('locations',$oid,$in['location_id']??null,true);$department=$this->owned('departments',$oid,$in['department_id']??null,true);$position=$this->owned('positions',$oid,$in['position_id']??null,true);$days=array_values(array_intersect(array_map('intval',(array)($in['weekdays']??[])),range(1,7)));if(trim((string)($in['name']??''))===''||!$days)throw new InvalidArgumentException('Name the rotation and select at least one weekday.');$q=$this->db->prepare('INSERT INTO rotations (organization_id,name,membership_id,location_id,department_id,position_id,weekdays,starts_at,ends_at,effective_from,effective_to,week_interval,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$oid,trim((string)$in['name']),$member,$location,$department,$position,implode(',',$days),$in['starts_at'],$in['ends_at'],$in['effective_from'],!empty($in['effective_to'])?$in['effective_to']:null,max(1,(int)($in['week_interval']??1)),$uid]);$this->audit($oid,$uid,'rotation.created',(string)$this->db->lastInsertId());
    }

    public function rotations(int $oid): array
    {
        $q=$this->db->prepare('SELECT r.*,u.name staff_name,l.name location_name,d.name department_name,p.name position_name FROM rotations r LEFT JOIN memberships m ON m.id=r.membership_id LEFT JOIN users u ON u.id=m.user_id JOIN locations l ON l.id=r.location_id JOIN departments d ON d.id=r.department_id JOIN positions p ON p.id=r.position_id WHERE r.organization_id=? AND r.active=1 ORDER BY r.name');$q->execute([$oid]);return $q->fetchAll();
    }

    public function generateRotation(int $oid,int $uid,int $rotationId,string $through): int
    {
        $q=$this->db->prepare('SELECT * FROM rotations WHERE id=? AND organization_id=? AND active=1');$q->execute([$rotationId,$oid]);$r=$q->fetch();if(!$r)throw new InvalidArgumentException('Rotation unavailable.');$end=min($through,$r['effective_to']?:$through);$date=new DateTimeImmutable($r['effective_from']);$limit=new DateTimeImmutable($end);$days=array_map('intval',explode(',',$r['weekdays']));$count=0;$insert=$this->db->prepare('INSERT INTO shifts (organization_id,location_id,department_id,assigned_membership_id,shift_date,starts_at,ends_at,status,eligibility_mode,exact_position_id,cross_department_mode,notes,created_by) VALUES (?,?,?,?,?,?,?,?,"exact",?,"prohibited",?,?)');while($date<=$limit){$weeks=intdiv((int)(new DateTimeImmutable($r['effective_from']))->diff($date)->days,7);if(in_array((int)$date->format('N'),$days,true)&&$weeks%(int)$r['week_interval']===0){$check=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE organization_id=? AND assigned_membership_id <=> ? AND shift_date=? AND starts_at=? AND department_id=?');$check->execute([$oid,$r['membership_id'],$date->format('Y-m-d'),$r['starts_at'],$r['department_id']]);if(!$check->fetchColumn()){$insert->execute([$oid,$r['location_id'],$r['department_id'],$r['membership_id'],$date->format('Y-m-d'),$r['starts_at'],$r['ends_at'],$r['membership_id']?'assigned':'open',$r['position_id'],'Generated from '.$r['name'],$uid]);$sid=(int)$this->db->lastInsertId();$this->db->prepare('INSERT INTO rotation_generated_shifts (rotation_id,shift_id) VALUES (?,?)')->execute([$rotationId,$sid]);$count++;}}$date=$date->modify('+1 day');}$this->audit($oid,$uid,'rotation.generated',(string)$count);return $count;
    }

    public function availability(int $oid,int $memberId): array
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT * FROM workforce_preferences WHERE organization_id=? AND membership_id=?');$q->execute([$oid,$member]);$preferences=$q->fetch()?:[];$q=$this->db->prepare('SELECT ae.*,u.name reviewer_name FROM availability_entries ae LEFT JOIN users u ON u.id=ae.reviewed_by WHERE ae.organization_id=? AND ae.membership_id=? ORDER BY ae.entry_type,ae.weekday,ae.applies_on');$q->execute([$oid,$member]);return ['preferences'=>$preferences,'entries'=>$q->fetchAll()];
    }

    public function pendingAvailability(int $oid): array
    {
        $q=$this->db->prepare('SELECT ae.*,u.name staff_name FROM availability_entries ae JOIN memberships m ON m.id=ae.membership_id JOIN users u ON u.id=m.user_id WHERE ae.organization_id=? AND ae.status="pending" ORDER BY ae.created_at');$q->execute([$oid]);return $q->fetchAll();
    }

    public function savePreferences(int $oid,int $memberId,array $in): void
    {
        $member=$this->membership($oid,$memberId);$location=$this->owned('locations',$oid,$in['preferred_location_id']??null);$department=$this->owned('departments',$oid,$in['preferred_department_id']??null);$opening=in_array($in['opening_preference']??'', ['prefer','available','avoid'],true)?$in['opening_preference']:'available';$closing=in_array($in['closing_preference']??'', ['prefer','available','avoid'],true)?$in['closing_preference']:'available';$q=$this->db->prepare('INSERT INTO workforce_preferences (membership_id,organization_id,preferred_start,preferred_end,maximum_weekly_hours,preferred_location_id,preferred_department_id,opening_preference,closing_preference,notes) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE preferred_start=VALUES(preferred_start),preferred_end=VALUES(preferred_end),maximum_weekly_hours=VALUES(maximum_weekly_hours),preferred_location_id=VALUES(preferred_location_id),preferred_department_id=VALUES(preferred_department_id),opening_preference=VALUES(opening_preference),closing_preference=VALUES(closing_preference),notes=VALUES(notes)');$q->execute([$member,$oid,$in['preferred_start']?:null,$in['preferred_end']?:null,($in['maximum_weekly_hours']??'')!==''?(float)$in['maximum_weekly_hours']:null,$location,$department,$opening,$closing,trim((string)($in['notes']??''))?:null]);
    }

    public function addAvailability(int $oid,int $memberId,array $in,bool $autoApprove): void
    {
        $member=$this->membership($oid,$memberId);$type=in_array($in['entry_type']??'', ['recurring','date_exception'],true)?$in['entry_type']:'recurring';$availability=in_array($in['availability']??'', ['available','preferred','unavailable'],true)?$in['availability']:'available';$weekday=$type==='recurring'?(int)($in['weekday']??0):null;$date=$type==='date_exception'?(string)($in['applies_on']??''):null;if(($type==='recurring'&&($weekday<1||$weekday>7))||($type==='date_exception'&&!$date))throw new InvalidArgumentException('Choose a weekday or exception date.');$start=!empty($in['starts_at'])?$in['starts_at']:null;$end=!empty($in['ends_at'])?$in['ends_at']:null;if($start&&$end&&$end<=$start)throw new InvalidArgumentException('Availability end time must be after its start time.');$q=$this->db->prepare('INSERT INTO availability_entries (organization_id,membership_id,entry_type,weekday,applies_on,availability,starts_at,ends_at,status,reviewed_at) VALUES (?,?,?,?,?,?,?,?,?,?)');$q->execute([$oid,$member,$type,$weekday,$date?:null,$availability,$start,$end,$autoApprove?'approved':'pending',$autoApprove?date('Y-m-d H:i:s'):null]);
    }

    public function reviewAvailability(int $oid,int $uid,int $entryId,string $decision): void
    {
        if(!in_array($decision,['approved','denied'],true))throw new InvalidArgumentException('Invalid availability decision.');$q=$this->db->prepare('UPDATE availability_entries SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND organization_id=? AND status="pending"');$q->execute([$decision,$uid,$entryId,$oid]);if(!$q->rowCount())throw new InvalidArgumentException('Pending availability request unavailable.');$this->audit($oid,$uid,'availability.'.$decision,(string)$entryId);
    }

    public function deleteAvailability(int $oid,int $memberId,int $entryId): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('DELETE FROM availability_entries WHERE id=? AND organization_id=? AND membership_id=?');$q->execute([$entryId,$oid,$member]);if(!$q->rowCount())throw new InvalidArgumentException('Availability entry unavailable.');
    }

    public function requestTypes(int $oid): array
    {
        $q=$this->db->prepare('SELECT * FROM request_types WHERE organization_id=? AND active=1 ORDER BY name');$q->execute([$oid]);return $q->fetchAll();
    }

    public function addRequestType(int $oid,string $name,bool $paid): void
    {
        $name=trim($name);if($name==='')throw new InvalidArgumentException('Request type name is required.');$q=$this->db->prepare('INSERT INTO request_types (organization_id,name,paid) VALUES (?,?,?)');$q->execute([$oid,$name,$paid]);
    }

    public function createTimeOff(int $oid,int $memberId,array $in): void
    {
        $member=$this->membership($oid,$memberId);$type=null;if(!empty($in['request_type_id'])){$q=$this->db->prepare('SELECT id FROM request_types WHERE id=? AND organization_id=? AND active=1');$q->execute([(int)$in['request_type_id'],$oid]);$type=$q->fetchColumn()?:null;}$start=(string)($in['starts_on']??'');$end=(string)($in['ends_on']??'');if(!$start||!$end||$end<$start)throw new InvalidArgumentException('Enter a valid start and end date.');$startTime=!empty($in['starts_at'])?$in['starts_at']:null;$endTime=!empty($in['ends_at'])?$in['ends_at']:null;if(($startTime&&!$endTime)||(!$startTime&&$endTime)||($startTime&&$endTime&&$endTime<=$startTime))throw new InvalidArgumentException('Enter both partial-day times in a valid range.');$q=$this->db->prepare('INSERT INTO time_off_requests (organization_id,membership_id,request_type_id,starts_on,ends_on,starts_at,ends_at,employee_note) VALUES (?,?,?,?,?,?,?,?)');$q->execute([$oid,$member,$type,$start,$end,$startTime,$endTime,trim((string)($in['employee_note']??''))?:null]);
    }

    public function timeOff(int $oid,?int $memberId=null): array
    {
        $sql='SELECT tor.*,rt.name type_name,rt.paid,u.name staff_name,(SELECT COUNT(*) FROM shifts s WHERE s.organization_id=tor.organization_id AND s.assigned_membership_id=tor.membership_id AND s.shift_date BETWEEN tor.starts_on AND tor.ends_on AND s.status IN ("assigned","filled") AND (tor.starts_at IS NULL OR (s.starts_at<tor.ends_at AND s.ends_at>tor.starts_at))) conflict_count FROM time_off_requests tor JOIN memberships m ON m.id=tor.membership_id JOIN users u ON u.id=m.user_id LEFT JOIN request_types rt ON rt.id=tor.request_type_id WHERE tor.organization_id=?';$args=[$oid];if($memberId){$sql.=' AND tor.membership_id=?';$args[]=$this->membership($oid,$memberId);}$sql.=' ORDER BY FIELD(tor.status,"pending","approved","denied","cancelled"),tor.starts_on DESC';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function reviewTimeOff(int $oid,int $uid,int $requestId,string $decision,string $note=''): void
    {
        if(!in_array($decision,['approved','denied'],true))throw new InvalidArgumentException('Invalid request decision.');$q=$this->db->prepare('UPDATE time_off_requests SET status=?,manager_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND organization_id=? AND status="pending"');$q->execute([$decision,trim($note)?:null,$uid,$requestId,$oid]);if(!$q->rowCount())throw new InvalidArgumentException('Pending time-off request unavailable.');$this->audit($oid,$uid,'time_off.'.$decision,(string)$requestId);
    }

    public function cancelTimeOff(int $oid,int $memberId,int $requestId): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('UPDATE time_off_requests SET status="cancelled" WHERE id=? AND organization_id=? AND membership_id=? AND status IN ("pending","approved")');$q->execute([$requestId,$oid,$member]);if(!$q->rowCount())throw new InvalidArgumentException('Time-off request cannot be cancelled.');
    }

    public function createShiftChange(int $oid,int $memberId,array $in): void
    {
        $requester=$this->membership($oid,$memberId);$type=in_array($in['request_type']??'', ['giveaway','trade','partial'],true)?$in['request_type']:'giveaway';$offered=$this->owned('shifts',$oid,$in['offered_shift_id']??null,true);$recipient=$this->membership($oid,$in['recipient_membership_id']??null);$q=$this->db->prepare('SELECT * FROM shifts WHERE id=? AND organization_id=? AND assigned_membership_id=? AND status IN ("assigned","filled")');$q->execute([$offered,$oid,$requester]);$shift=$q->fetch();if(!$shift)throw new InvalidArgumentException('You can only offer one of your assigned shifts.');if($recipient===$requester)throw new InvalidArgumentException('Choose another team member.');$requested=null;if($type==='trade'){$requested=$this->owned('shifts',$oid,$in['requested_shift_id']??null,true);$q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE id=? AND organization_id=? AND assigned_membership_id=? AND status IN ("assigned","filled")');$q->execute([$requested,$oid,$recipient]);if(!$q->fetchColumn())throw new InvalidArgumentException('The requested trade shift is not assigned to that employee.');}$partialStart=$type==='partial'?($in['partial_starts_at']??null):null;$partialEnd=$type==='partial'?($in['partial_ends_at']??null):null;if($type==='partial'&&(!$partialStart||!$partialEnd||$partialStart<$shift['starts_at']||$partialEnd>$shift['ends_at']||$partialEnd<=$partialStart))throw new InvalidArgumentException('Partial coverage must fall within the offered shift.');$elig=$this->eligibility($oid,$recipient,$offered,$type==='trade'?$requested:null);if($elig['result']==='ineligible')throw new InvalidArgumentException(implode(' ',$elig['reasons']));if($type==='trade'){$reverse=$this->eligibility($oid,$requester,$requested,$offered);if($reverse['result']==='ineligible')throw new InvalidArgumentException('You are not eligible for the requested shift: '.implode(' ',$reverse['reasons']));if($reverse['result']==='approval')$elig=['result'=>'approval','reasons'=>array_merge($elig['reasons'],$reverse['reasons'])];}$q=$this->db->prepare('INSERT INTO shift_change_requests (organization_id,requester_membership_id,offered_shift_id,recipient_membership_id,requested_shift_id,request_type,partial_starts_at,partial_ends_at,employee_note,status,eligibility_result,eligibility_reasons,expires_at) VALUES (?,?,?,?,?,?,?,?,?,"pending_recipient",?,?,DATE_ADD(NOW(),INTERVAL 7 DAY))');$q->execute([$oid,$requester,$offered,$recipient,$requested,$type,$partialStart,$partialEnd,trim((string)($in['employee_note']??''))?:null,$elig['result'],json_encode($elig['reasons'])]);
    }

    public function shiftChanges(int $oid,?int $memberId=null): array
    {
        $sql='SELECT scr.*,requester.name requester_name,recipient.name recipient_name,os.shift_date offered_date,os.starts_at offered_start,os.ends_at offered_end,od.name offered_department,rs.shift_date requested_date,rs.starts_at requested_start,rs.ends_at requested_end FROM shift_change_requests scr JOIN memberships rm ON rm.id=scr.requester_membership_id JOIN users requester ON requester.id=rm.user_id LEFT JOIN memberships recm ON recm.id=scr.recipient_membership_id LEFT JOIN users recipient ON recipient.id=recm.user_id JOIN shifts os ON os.id=scr.offered_shift_id JOIN departments od ON od.id=os.department_id LEFT JOIN shifts rs ON rs.id=scr.requested_shift_id WHERE scr.organization_id=?';$args=[$oid];if($memberId){$member=$this->membership($oid,$memberId);$sql.=' AND (scr.requester_membership_id=? OR scr.recipient_membership_id=?)';$args[]=$member;$args[]=$member;}$sql.=' ORDER BY FIELD(scr.status,"pending_recipient","pending_manager","approved","denied","withdrawn","expired"),scr.created_at DESC';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function respondShiftChange(int $oid,int $memberId,int $requestId,string $response): void
    {
        $member=$this->membership($oid,$memberId);if(!in_array($response,['accept','deny'],true))throw new InvalidArgumentException('Invalid response.');$q=$this->db->prepare('SELECT offered_shift_id,requested_shift_id FROM shift_change_requests WHERE id=? AND organization_id=? AND recipient_membership_id=? AND status="pending_recipient" AND expires_at>NOW()');$q->execute([$requestId,$oid,$member]);$change=$q->fetch();if(!$change)throw new InvalidArgumentException('Shift request is unavailable.');if($response==='accept'){$elig=$this->eligibility($oid,$member,(int)$change['offered_shift_id'],!empty($change['requested_shift_id'])?(int)$change['requested_shift_id']:null);if($elig['result']==='ineligible')throw new InvalidArgumentException(implode(' ',$elig['reasons']));$this->db->prepare('UPDATE shift_change_requests SET status="pending_manager",eligibility_result=?,eligibility_reasons=? WHERE id=?')->execute([$elig['result'],json_encode($elig['reasons']),$requestId]);}else{$this->db->prepare('UPDATE shift_change_requests SET status="denied" WHERE id=?')->execute([$requestId]);}
    }

    public function reviewShiftChange(int $oid,int $uid,int $requestId,string $decision,string $note=''): void
    {
        if(!in_array($decision,['approved','denied'],true))throw new InvalidArgumentException('Invalid decision.');$q=$this->db->prepare('SELECT * FROM shift_change_requests WHERE id=? AND organization_id=? AND status="pending_manager"');$q->execute([$requestId,$oid]);$r=$q->fetch();if(!$r)throw new InvalidArgumentException('Pending shift request unavailable.');$this->db->beginTransaction();try{if($decision==='approved'){if($r['request_type']==='trade'){$this->db->prepare('UPDATE shifts SET assigned_membership_id=? WHERE id=? AND organization_id=?')->execute([$r['recipient_membership_id'],$r['offered_shift_id'],$oid]);$this->db->prepare('UPDATE shifts SET assigned_membership_id=? WHERE id=? AND organization_id=?')->execute([$r['requester_membership_id'],$r['requested_shift_id'],$oid]);}elseif($r['request_type']==='giveaway'){$this->db->prepare('UPDATE shifts SET assigned_membership_id=?,status="assigned" WHERE id=? AND organization_id=?')->execute([$r['recipient_membership_id'],$r['offered_shift_id'],$oid]);}else{$this->db->prepare('INSERT INTO shift_relief_assignments (organization_id,shift_id,membership_id,starts_at,ends_at,source_request_id,created_by) VALUES (?,?,?,?,?,?,?)')->execute([$oid,$r['offered_shift_id'],$r['recipient_membership_id'],$r['partial_starts_at'],$r['partial_ends_at'],$r['id'],$uid]);}}$this->db->prepare('UPDATE shift_change_requests SET status=?,manager_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$decision,trim($note)?:null,$uid,$requestId]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}$this->audit($oid,$uid,'shift_change.'.$decision,(string)$requestId);
    }

    public function withdrawShiftChange(int $oid,int $memberId,int $requestId): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('UPDATE shift_change_requests SET status="withdrawn" WHERE id=? AND organization_id=? AND requester_membership_id=? AND status IN ("pending_recipient","pending_manager")');$q->execute([$requestId,$oid,$member]);if(!$q->rowCount())throw new InvalidArgumentException('Shift request cannot be withdrawn.');
    }

    public function reportCallout(int $oid,int $memberId,array $in): void
    {
        $member=$this->membership($oid,$memberId);$shift=$this->owned('shifts',$oid,$in['shift_id']??null,true);$q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE id=? AND organization_id=? AND assigned_membership_id=? AND status IN ("assigned","filled")');$q->execute([$shift,$oid,$member]);if(!$q->fetchColumn())throw new InvalidArgumentException('You can only call out from one of your assigned shifts.');$category=in_array($in['reason_category']??'', ['illness','family_emergency','transportation','weather','other'],true)?$in['reason_category']:'other';$q=$this->db->prepare('INSERT INTO callouts (organization_id,shift_id,membership_id,reason_category,employee_note,status) VALUES (?,?,?,?,?,"replacement_open")');$q->execute([$oid,$shift,$member,$category,trim((string)($in['employee_note']??''))?:null]);$this->db->prepare('UPDATE shifts SET assigned_membership_id=NULL,status="open" WHERE id=? AND organization_id=?')->execute([$shift,$oid]);
    }

    public function callouts(int $oid,?int $memberId=null): array
    {
        $sql='SELECT c.*,s.shift_date,s.starts_at,s.ends_at,d.name department_name,u.name staff_name,ru.name replacement_name FROM callouts c JOIN shifts s ON s.id=c.shift_id JOIN departments d ON d.id=s.department_id JOIN memberships m ON m.id=c.membership_id JOIN users u ON u.id=m.user_id LEFT JOIN memberships rm ON rm.id=c.replacement_membership_id LEFT JOIN users ru ON ru.id=rm.user_id WHERE c.organization_id=?';$args=[$oid];if($memberId){$sql.=' AND (c.membership_id=? OR c.replacement_membership_id=?)';$args[]=$memberId;$args[]=$memberId;}$sql.=' ORDER BY FIELD(c.status,"replacement_open","reported","covered","closed","cancelled"),s.shift_date,s.starts_at';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function offerCallout(int $oid,int $uid,int $calloutId,int $memberId): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT shift_id FROM callouts WHERE id=? AND organization_id=? AND status="replacement_open"');$q->execute([$calloutId,$oid]);$shift=(int)$q->fetchColumn();if(!$shift)throw new InvalidArgumentException('Callout is no longer open.');$elig=$this->eligibility($oid,$member,$shift);if($elig['result']==='ineligible')throw new InvalidArgumentException(implode(' ',$elig['reasons']));$q=$this->db->prepare('INSERT INTO callout_offers (callout_id,membership_id,eligibility_result,eligibility_reasons) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE status="offered",eligibility_result=VALUES(eligibility_result),eligibility_reasons=VALUES(eligibility_reasons)');$q->execute([$calloutId,$member,$elig['result'],json_encode($elig['reasons'])]);$this->audit($oid,$uid,'callout.offer',(string)$calloutId);
    }

    public function respondCalloutOffer(int $oid,int $memberId,int $calloutId,string $response): void
    {
        if(!in_array($response,['accepted','declined'],true))throw new InvalidArgumentException('Invalid response.');$member=$this->membership($oid,$memberId);$q=$this->db->prepare('UPDATE callout_offers co JOIN callouts c ON c.id=co.callout_id SET co.status=?,co.responded_at=NOW() WHERE co.callout_id=? AND co.membership_id=? AND c.organization_id=? AND c.status="replacement_open" AND co.status="offered"');$q->execute([$response,$calloutId,$member,$oid]);if(!$q->rowCount())throw new InvalidArgumentException('Coverage offer is unavailable.');
    }

    public function selectCalloutReplacement(int $oid,int $uid,int $calloutId,int $memberId,string $note=''): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT c.shift_id FROM callouts c JOIN callout_offers co ON co.callout_id=c.id AND co.membership_id=? AND co.status="accepted" WHERE c.id=? AND c.organization_id=? AND c.status="replacement_open"');$q->execute([$member,$calloutId,$oid]);$shift=(int)$q->fetchColumn();if(!$shift)throw new InvalidArgumentException('Select an employee who accepted this callout.');$this->db->beginTransaction();try{$this->db->prepare('UPDATE shifts SET assigned_membership_id=?,status="assigned" WHERE id=? AND organization_id=?')->execute([$member,$shift,$oid]);$this->db->prepare('UPDATE callouts SET replacement_membership_id=?,status="covered",manager_note=?,acknowledged_by=?,acknowledged_at=NOW(),resolved_at=NOW() WHERE id=?')->execute([$member,trim($note)?:null,$uid,$calloutId]);$this->db->prepare('UPDATE callout_offers SET status=IF(membership_id=?,"selected",IF(status="accepted","expired",status)) WHERE callout_id=?')->execute([$member,$calloutId]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}$this->audit($oid,$uid,'callout.covered',(string)$calloutId);
    }

    public function calloutOffers(int $oid,?int $memberId=null): array
    {
        $sql='SELECT co.*,c.shift_id,s.shift_date,s.starts_at,s.ends_at,d.name department_name,u.name staff_name FROM callout_offers co JOIN callouts c ON c.id=co.callout_id JOIN shifts s ON s.id=c.shift_id JOIN departments d ON d.id=s.department_id JOIN memberships m ON m.id=co.membership_id JOIN users u ON u.id=m.user_id WHERE c.organization_id=?';$args=[$oid];if($memberId){$sql.=' AND co.membership_id=?';$args[]=$memberId;}$sql.=' ORDER BY co.created_at DESC';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function createThread(int $oid,int $memberId,array $in): int
    {
        $sender=$this->membership($oid,$memberId);$subject=trim((string)($in['subject']??''));$body=trim((string)($in['body']??''));if($subject===''||$body==='')throw new InvalidArgumentException('Subject and message are required.');$recipients=array_values(array_unique(array_map('intval',(array)($in['membership_ids']??[]))));if(!$recipients)throw new InvalidArgumentException('Choose at least one recipient.');$this->db->beginTransaction();try{$q=$this->db->prepare('INSERT INTO message_threads (organization_id,subject,thread_type,created_by) VALUES (?,?,?,?)');$q->execute([$oid,$subject,count($recipients)>1?'group':'direct',$this->userForMembership($oid,$sender)]);$thread=(int)$this->db->lastInsertId();$add=$this->db->prepare('INSERT INTO message_thread_members (thread_id,membership_id,last_read_at) VALUES (?,?,?)');$add->execute([$thread,$sender,date('Y-m-d H:i:s')]);foreach($recipients as $recipient){$recipient=$this->membership($oid,$recipient);if($recipient===$sender)continue;$add->execute([$thread,$recipient,null]);$this->db->prepare('INSERT INTO notifications (organization_id,membership_id,notification_type,title,body,action_route) VALUES (?,?,' . "'message'" . ',?,?,"messages")')->execute([$oid,$recipient,'New message: '.$subject,mb_substr($body,0,500)]);}$this->db->prepare('INSERT INTO messages (thread_id,sender_membership_id,body) VALUES (?,?,?)')->execute([$thread,$sender,$body]);$this->db->commit();return $thread;}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function sendMessage(int $oid,int $memberId,int $threadId,string $body): void
    {
        $member=$this->membership($oid,$memberId);$body=trim($body);if($body==='')throw new InvalidArgumentException('Message cannot be empty.');$q=$this->db->prepare('SELECT COUNT(*) FROM message_threads t JOIN message_thread_members tm ON tm.thread_id=t.id WHERE t.id=? AND t.organization_id=? AND tm.membership_id=?');$q->execute([$threadId,$oid,$member]);if(!$q->fetchColumn())throw new InvalidArgumentException('Conversation unavailable.');$this->db->prepare('INSERT INTO messages (thread_id,sender_membership_id,body) VALUES (?,?,?)')->execute([$threadId,$member,$body]);$this->db->prepare('UPDATE message_threads SET updated_at=NOW() WHERE id=?')->execute([$threadId]);$this->db->prepare('UPDATE message_thread_members SET last_read_at=NOW() WHERE thread_id=? AND membership_id=?')->execute([$threadId,$member]);$q=$this->db->prepare('SELECT membership_id FROM message_thread_members WHERE thread_id=? AND membership_id<>?');$q->execute([$threadId,$member]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $recipient)$this->db->prepare('INSERT INTO notifications (organization_id,membership_id,notification_type,title,body,action_route) SELECT ?,?,"message",subject,?,"messages" FROM message_threads WHERE id=?')->execute([$oid,$recipient,mb_substr($body,0,500),$threadId]);
    }

    public function threads(int $oid,int $memberId): array
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT t.*,MAX(m.created_at) last_message_at,SUBSTRING_INDEX(GROUP_CONCAT(m.body ORDER BY m.created_at DESC SEPARATOR "||"),"||",1) preview,SUM(m.created_at>COALESCE(tm.last_read_at,"1970-01-01")) unread_count FROM message_threads t JOIN message_thread_members tm ON tm.thread_id=t.id AND tm.membership_id=? LEFT JOIN messages m ON m.thread_id=t.id WHERE t.organization_id=? GROUP BY t.id,tm.last_read_at ORDER BY COALESCE(MAX(m.created_at),t.created_at) DESC');$q->execute([$member,$oid]);return $q->fetchAll();
    }

    public function threadMessages(int $oid,int $memberId,?int $threadId): array
    {
        if(!$threadId)return [];$member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT m.*,u.name sender_name FROM messages m JOIN message_threads t ON t.id=m.thread_id JOIN message_thread_members tm ON tm.thread_id=t.id AND tm.membership_id=? JOIN memberships sm ON sm.id=m.sender_membership_id JOIN users u ON u.id=sm.user_id WHERE t.id=? AND t.organization_id=? ORDER BY m.created_at');$q->execute([$member,$threadId,$oid]);$rows=$q->fetchAll();if($rows)$this->db->prepare('UPDATE message_thread_members SET last_read_at=NOW() WHERE thread_id=? AND membership_id=?')->execute([$threadId,$member]);return $rows;
    }

    public function notifications(int $oid,int $memberId): array
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT * FROM notifications WHERE organization_id=? AND membership_id=? ORDER BY created_at DESC LIMIT 50');$q->execute([$oid,$member]);return $q->fetchAll();
    }

    public function refreshOperationalNotifications(int $oid,int $memberId): void
    {
        $member=$this->membership($oid,$memberId);$insert=$this->db->prepare('INSERT INTO notifications (organization_id,membership_id,notification_type,title,body,action_route) SELECT ?,?,?,?,?,? WHERE NOT EXISTS (SELECT 1 FROM notifications WHERE organization_id=? AND membership_id=? AND notification_type=? AND title=? AND created_at>DATE_SUB(NOW(),INTERVAL 24 HOUR))');$notify=function(int $recipient,string $type,string $title,string $body,string $route)use($insert,$oid){$insert->execute([$oid,$recipient,$type,$title,$body,$route,$oid,$recipient,$type,$title]);};$q=$this->db->prepare('SELECT role FROM memberships WHERE id=? AND organization_id=?');$q->execute([$member,$oid]);$role=(string)$q->fetchColumn();if(in_array($role,['owner','admin'],true)){$q=$this->db->prepare('SELECT COUNT(*) FROM callouts WHERE organization_id=? AND status="replacement_open"');$q->execute([$oid]);$count=(int)$q->fetchColumn();if($count)$notify($member,'callout','Urgent callouts need coverage',$count.' callout'.($count===1?' is':'s are').' awaiting replacement.','callouts');$q=$this->db->prepare('SELECT (SELECT COUNT(*) FROM time_off_requests WHERE organization_id=? AND status="pending")+(SELECT COUNT(*) FROM shift_change_requests WHERE organization_id=? AND status="pending_manager")');$q->execute([$oid,$oid]);$count=(int)$q->fetchColumn();if($count)$notify($member,'approval','Scheduling approvals are waiting',$count.' request'.($count===1?' needs':'s need').' a decision.','planning');$q=$this->db->prepare('SELECT COUNT(*) FROM time_entries WHERE organization_id=? AND status="submitted"');$q->execute([$oid]);$count=(int)$q->fetchColumn();if($count)$notify($member,'timesheet','Timesheets are ready for review',$count.' submitted time entr'.($count===1?'y is':'ies are').' waiting.','time-clock');}$q=$this->db->prepare('SELECT ct.name,DATEDIFF(mc.expires_on,CURDATE()) days_remaining FROM member_credentials mc JOIN credential_types ct ON ct.id=mc.credential_type_id WHERE mc.organization_id=? AND mc.membership_id=? AND mc.status="verified" AND mc.expires_on IS NOT NULL AND DATEDIFF(mc.expires_on,CURDATE()) BETWEEN 0 AND ct.warning_days');$q->execute([$oid,$member]);foreach($q->fetchAll() as $credential)$notify($member,'credential',$credential['name'].' expires soon','Expires in '.$credential['days_remaining'].' days.','notifications');
    }

    public function markNotificationsRead(int $oid,int $memberId): void
    {
        $member=$this->membership($oid,$memberId);$this->db->prepare('UPDATE notifications SET read_at=NOW() WHERE organization_id=? AND membership_id=? AND read_at IS NULL')->execute([$oid,$member]);
    }

    public function addCoverageRequirement(int $oid,int $uid,array $in): void
    {
        $location=$this->owned('locations',$oid,$in['location_id']??null,true);$department=$this->owned('departments',$oid,$in['department_id']??null,true);$provider=$this->owned('providers',$oid,$in['provider_id']??null);$station=$this->owned('stations',$oid,$in['station_id']??null);$function=$this->owned('work_functions',$oid,$in['work_function_id']??null);if(!$provider&&!$station&&!$function)throw new InvalidArgumentException('Choose a provider, station, or work function.');$weekday=(int)($in['weekday']??0);$start=(string)($in['starts_at']??'');$end=(string)($in['ends_at']??'');if($weekday<1||$weekday>7||!$start||!$end||$end<=$start)throw new InvalidArgumentException('Enter a valid weekday and coverage window.');$count=max(1,min(50,(int)($in['required_count']??1)));$priority=in_array($in['priority']??'', ['standard','important','critical'],true)?$in['priority']:'standard';$q=$this->db->prepare('INSERT INTO coverage_requirements (organization_id,location_id,department_id,provider_id,station_id,work_function_id,weekday,starts_at,ends_at,required_count,priority,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$oid,$location,$department,$provider,$station,$function,$weekday,$start,$end,$count,$priority,$uid]);$this->audit($oid,$uid,'coverage_requirement.created',(string)$this->db->lastInsertId());
    }

    public function coverageRequirements(int $oid,string $date): array
    {
        $weekday=(int)(new DateTimeImmutable($date))->format('N');$q=$this->db->prepare('SELECT cr.*,l.name location_name,d.name department_name,p.name provider_name,st.name station_name,wf.name function_name,(SELECT COUNT(*) FROM coverage_assignments ca JOIN shifts s ON s.id=ca.shift_id WHERE ca.organization_id=cr.organization_id AND s.shift_date=? AND s.status<>"cancelled" AND (cr.provider_id IS NULL OR ca.provider_id=cr.provider_id) AND (cr.station_id IS NULL OR ca.station_id=cr.station_id) AND (cr.work_function_id IS NULL OR ca.work_function_id=cr.work_function_id) AND COALESCE(ca.starts_at,s.starts_at)<cr.ends_at AND COALESCE(ca.ends_at,s.ends_at)>cr.starts_at) assigned_count FROM coverage_requirements cr JOIN locations l ON l.id=cr.location_id JOIN departments d ON d.id=cr.department_id LEFT JOIN providers p ON p.id=cr.provider_id LEFT JOIN stations st ON st.id=cr.station_id LEFT JOIN work_functions wf ON wf.id=cr.work_function_id WHERE cr.organization_id=? AND cr.weekday=? AND cr.active=1 ORDER BY FIELD(cr.priority,"critical","important","standard"),cr.starts_at');$q->execute([$date,$oid,$weekday]);return $q->fetchAll();
    }

    public function createShiftTemplate(int $oid,int $uid,array $in): void
    {
        $name=trim((string)($in['name']??''));if($name==='')throw new InvalidArgumentException('Template name is required.');$location=$this->owned('locations',$oid,$in['location_id']??null,true);$department=$this->owned('departments',$oid,$in['department_id']??null,true);$position=$this->owned('positions',$oid,$in['position_id']??null,true);$start=(string)($in['starts_at']??'');$end=(string)($in['ends_at']??'');if(!$start||!$end||$end<=$start)throw new InvalidArgumentException('Enter a valid template time range.');$mode=in_array($in['cross_department_mode']??'', ['prohibited','approval','allowed'],true)?$in['cross_department_mode']:'prohibited';$q=$this->db->prepare('INSERT INTO shift_templates (organization_id,name,location_id,department_id,position_id,starts_at,ends_at,cross_department_mode,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)');$q->execute([$oid,$name,$location,$department,$position,$start,$end,$mode,trim((string)($in['notes']??''))?:null,$uid]);$this->audit($oid,$uid,'shift_template.created',(string)$this->db->lastInsertId());
    }

    public function shiftTemplates(int $oid): array
    {
        $q=$this->db->prepare('SELECT st.*,l.name location_name,d.name department_name,p.name position_name FROM shift_templates st JOIN locations l ON l.id=st.location_id JOIN departments d ON d.id=st.department_id JOIN positions p ON p.id=st.position_id WHERE st.organization_id=? AND st.active=1 ORDER BY st.name');$q->execute([$oid]);return $q->fetchAll();
    }

    public function applyShiftTemplate(int $oid,int $uid,int $templateId,string $date,?int $memberId=null): int
    {
        if(!$date)throw new InvalidArgumentException('Choose a shift date.');$q=$this->db->prepare('SELECT * FROM shift_templates WHERE id=? AND organization_id=? AND active=1');$q->execute([$templateId,$oid]);$t=$q->fetch();if(!$t)throw new InvalidArgumentException('Template unavailable.');$member=$memberId?$this->membership($oid,$memberId):null;$status=$member?'assigned':'open';$q=$this->db->prepare('INSERT INTO shifts (organization_id,location_id,department_id,assigned_membership_id,shift_date,starts_at,ends_at,status,eligibility_mode,exact_position_id,cross_department_mode,notes,created_by) VALUES (?,?,?,?,?,?,?, ?,"exact",?,?,?,?)');$q->execute([$oid,$t['location_id'],$t['department_id'],$member,$date,$t['starts_at'],$t['ends_at'],$status,$t['position_id'],$t['cross_department_mode'],$t['notes'],$uid]);$id=(int)$this->db->lastInsertId();if($member){$elig=$this->eligibility($oid,$member,$id);if($elig['result']==='ineligible'){$this->db->prepare('DELETE FROM shifts WHERE id=?')->execute([$id]);throw new InvalidArgumentException(implode(' ',$elig['reasons']));}}$this->audit($oid,$uid,'shift_template.applied',(string)$id);return $id;
    }

    public function fairnessMetrics(int $oid,string $from,string $through): array
    {
        if(!$from||!$through||$through<$from)throw new InvalidArgumentException('Choose a valid fairness review period.');$q=$this->db->prepare('SELECT m.id membership_id,u.name,sa.department_id,d.name department_name,p.name position_name,COALESCE(SUM(CASE WHEN s.status IN ("assigned","filled") THEN TIME_TO_SEC(TIMEDIFF(s.ends_at,s.starts_at))/3600 ELSE 0 END),0) scheduled_hours,COUNT(DISTINCT CASE WHEN s.status IN ("assigned","filled") THEN s.id END) shift_count,COUNT(DISTINCT CASE WHEN s.status IN ("assigned","filled") AND s.starts_at<="07:00:00" THEN s.id END) opening_count,COUNT(DISTINCT CASE WHEN s.status IN ("assigned","filled") AND s.ends_at>="18:00:00" THEN s.id END) closing_count,COUNT(DISTINCT CASE WHEN DAYOFWEEK(s.shift_date) IN (1,7) AND s.status IN ("assigned","filled") THEN s.id END) weekend_count,COALESCE(wp.expected_weekly_hours,0) expected_weekly_hours,COALESCE(pref.maximum_weekly_hours,0) maximum_weekly_hours FROM memberships m JOIN users u ON u.id=m.user_id LEFT JOIN staff_assignments sa ON sa.membership_id=m.id AND sa.is_primary=1 LEFT JOIN departments d ON d.id=sa.department_id LEFT JOIN positions p ON p.id=sa.position_id LEFT JOIN workforce_profiles wp ON wp.membership_id=m.id LEFT JOIN workforce_preferences pref ON pref.membership_id=m.id LEFT JOIN shifts s ON s.assigned_membership_id=m.id AND s.organization_id=m.organization_id AND s.shift_date BETWEEN ? AND ? WHERE m.organization_id=? AND m.status="active" GROUP BY m.id,u.name,sa.department_id,d.name,p.name,wp.expected_weekly_hours,pref.maximum_weekly_hours ORDER BY scheduled_hours,u.name');$q->execute([$from,$through,$oid]);$rows=$q->fetchAll();$weeks=max(1,((new DateTimeImmutable($from))->diff(new DateTimeImmutable($through))->days+1)/7);foreach($rows as &$r){$target=(float)$r['expected_weekly_hours']*$weeks;$r['target_hours']=round($target,2);$r['variance_hours']=round((float)$r['scheduled_hours']-$target,2);$r['burden_score']=(int)$r['opening_count']+(int)$r['closing_count']+(int)$r['weekend_count'];}return $rows;
    }

    public function fairCandidates(int $oid,int $shiftId,string $from,string $through): array
    {
        $metrics=$this->fairnessMetrics($oid,$from,$through);$out=[];foreach($metrics as $row){$elig=$this->eligibility($oid,(int)$row['membership_id'],$shiftId);if($elig['result']==='ineligible')continue;$row['eligibility']=$elig['result'];$row['eligibility_reasons']=$elig['reasons'];$row['guidance_score']=round((float)$row['scheduled_hours']+((int)$row['burden_score']*2)+($elig['result']==='approval'?10:0),2);$out[]=$row;}usort($out,fn($a,$b)=>$a['guidance_score']<=>$b['guidance_score']);return $out;
    }

    public function reportSummary(int $oid,string $from,string $through): array
    {
        if(!$from||!$through||$through<$from)throw new InvalidArgumentException('Choose a valid report period.');$q=$this->db->prepare('SELECT COUNT(*) shifts,COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(ends_at,starts_at))/3600),0) scheduled_hours,SUM(status="open") open_shifts,SUM(status="cancelled") cancelled_shifts FROM shifts WHERE organization_id=? AND shift_date BETWEEN ? AND ?');$q->execute([$oid,$from,$through]);$summary=$q->fetch();$q=$this->db->prepare('SELECT COUNT(*) total,SUM(status="approved") approved,SUM(status="denied") denied,SUM(status="pending") pending FROM time_off_requests WHERE organization_id=? AND starts_on<=? AND ends_on>=?');$q->execute([$oid,$through,$from]);$summary['time_off']=$q->fetch();$q=$this->db->prepare('SELECT COUNT(*) total,SUM(c.status="covered") covered,SUM(c.status="replacement_open") open FROM callouts c JOIN shifts s ON s.id=c.shift_id WHERE c.organization_id=? AND s.shift_date BETWEEN ? AND ?');$q->execute([$oid,$from,$through]);$summary['callouts']=$q->fetch();$q=$this->db->prepare('SELECT COALESCE(SUM(cr.required_count),0) required,COALESCE(SUM((SELECT COUNT(*) FROM coverage_assignments ca JOIN shifts s ON s.id=ca.shift_id WHERE ca.organization_id=cr.organization_id AND s.shift_date BETWEEN ? AND ? AND (cr.provider_id IS NULL OR ca.provider_id=cr.provider_id) AND (cr.station_id IS NULL OR ca.station_id=cr.station_id) AND (cr.work_function_id IS NULL OR ca.work_function_id=cr.work_function_id))),0) assigned FROM coverage_requirements cr WHERE cr.organization_id=? AND cr.active=1');$q->execute([$from,$through,$oid]);$summary['coverage']=$q->fetch();return $summary;
    }

    public function auditTrail(int $oid,int $limit=100): array
    {
        $limit=max(1,min(500,$limit));$q=$this->db->prepare('SELECT al.*,u.name user_name FROM audit_logs al LEFT JOIN users u ON u.id=al.user_id WHERE al.organization_id=? ORDER BY al.created_at DESC LIMIT '.$limit);$q->execute([$oid]);return $q->fetchAll();
    }

    public function addCredentialType(int $oid,array $in): void
    {
        $name=trim((string)($in['name']??''));if($name==='')throw new InvalidArgumentException('Credential name is required.');$q=$this->db->prepare('INSERT INTO credential_types (organization_id,name,issuing_authority,renewal_days,warning_days) VALUES (?,?,?,?,?)');$q->execute([$oid,$name,trim((string)($in['issuing_authority']??''))?:null,!empty($in['renewal_days'])?(int)$in['renewal_days']:null,max(1,(int)($in['warning_days']??60))]);
    }

    public function addMemberCredential(int $oid,int $uid,array $in): void
    {
        $member=$this->membership($oid,$in['membership_id']??null);$type=(int)($in['credential_type_id']??0);$q=$this->db->prepare('SELECT COUNT(*) FROM credential_types WHERE id=? AND organization_id=? AND active=1');$q->execute([$type,$oid]);if(!$q->fetchColumn())throw new InvalidArgumentException('Credential type unavailable.');$status=!empty($in['verified'])?'verified':'pending';$q=$this->db->prepare('INSERT INTO member_credentials (organization_id,membership_id,credential_type_id,credential_number,issued_on,expires_on,status,verified_by,verified_at,notes) VALUES (?,?,?,?,?,?,?,?,?,?)');$q->execute([$oid,$member,$type,trim((string)($in['credential_number']??''))?:null,!empty($in['issued_on'])?$in['issued_on']:null,!empty($in['expires_on'])?$in['expires_on']:null,$status,$status==='verified'?$uid:null,$status==='verified'?date('Y-m-d H:i:s'):null,trim((string)($in['notes']??''))?:null]);$this->audit($oid,$uid,'credential.created',(string)$this->db->lastInsertId());
    }

    public function credentialTypes(int $oid): array
    {
        $q=$this->db->prepare('SELECT * FROM credential_types WHERE organization_id=? AND active=1 ORDER BY name');$q->execute([$oid]);return $q->fetchAll();
    }

    public function credentials(int $oid): array
    {
        $this->db->prepare('UPDATE member_credentials SET status="expired" WHERE organization_id=? AND status="verified" AND expires_on<CURDATE()')->execute([$oid]);$q=$this->db->prepare('SELECT mc.*,ct.name credential_name,ct.warning_days,u.name staff_name,DATEDIFF(mc.expires_on,CURDATE()) days_remaining FROM member_credentials mc JOIN credential_types ct ON ct.id=mc.credential_type_id JOIN memberships m ON m.id=mc.membership_id JOIN users u ON u.id=m.user_id WHERE mc.organization_id=? ORDER BY FIELD(mc.status,"expired","pending","rejected","verified"),mc.expires_on,u.name');$q->execute([$oid]);return $q->fetchAll();
    }

    public function clockIn(int $oid,int $memberId,?int $shiftId=null): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT COUNT(*) FROM time_entries WHERE organization_id=? AND membership_id=? AND status="open"');$q->execute([$oid,$member]);if($q->fetchColumn())throw new InvalidArgumentException('You are already clocked in.');$shift=$shiftId?$this->owned('shifts',$oid,$shiftId,true):null;$this->db->prepare('INSERT INTO time_entries (organization_id,membership_id,shift_id,clocked_in_at) VALUES (?,?,?,NOW())')->execute([$oid,$member,$shift]);
    }

    public function clockOut(int $oid,int $memberId,int $breakMinutes=0,string $note=''): void
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('UPDATE time_entries SET clocked_out_at=NOW(),break_minutes=?,employee_note=?,status="submitted" WHERE organization_id=? AND membership_id=? AND status="open"');$q->execute([max(0,min(600,$breakMinutes)),trim($note)?:null,$oid,$member]);if(!$q->rowCount())throw new InvalidArgumentException('No open time entry was found.');
    }

    public function timeEntries(int $oid,?int $memberId=null): array
    {
        $sql='SELECT te.*,u.name staff_name,s.shift_date,TIMESTAMPDIFF(MINUTE,te.clocked_in_at,COALESCE(te.clocked_out_at,NOW()))-te.break_minutes worked_minutes FROM time_entries te JOIN memberships m ON m.id=te.membership_id JOIN users u ON u.id=m.user_id LEFT JOIN shifts s ON s.id=te.shift_id WHERE te.organization_id=?';$args=[$oid];if($memberId){$sql.=' AND te.membership_id=?';$args[]=$memberId;}$sql.=' ORDER BY te.clocked_in_at DESC LIMIT 200';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function reviewTimeEntry(int $oid,int $uid,int $entryId,string $decision,string $note=''): void
    {
        if(!in_array($decision,['approved','rejected'],true))throw new InvalidArgumentException('Invalid time-entry decision.');$q=$this->db->prepare('UPDATE time_entries SET status=?,manager_note=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND organization_id=? AND status="submitted"');$q->execute([$decision,trim($note)?:null,$uid,$entryId,$oid]);if(!$q->rowCount())throw new InvalidArgumentException('Submitted time entry unavailable.');$this->audit($oid,$uid,'time_entry.'.$decision,(string)$entryId);
    }

    public function savePayProfile(int $oid,int $uid,array $in): void
    {
        $member=$this->membership($oid,$in['membership_id']??null);$type=in_array($in['pay_type']??'', ['hourly','salary'],true)?$in['pay_type']:'hourly';$hourly=$type==='hourly'?(float)($in['hourly_rate']??0):null;$salary=$type==='salary'?(float)($in['annual_salary']??0):null;if(($type==='hourly'&&$hourly<=0)||($type==='salary'&&$salary<=0))throw new InvalidArgumentException('Enter a valid pay amount.');$q=$this->db->prepare('INSERT INTO pay_profiles (membership_id,organization_id,pay_type,hourly_rate,annual_salary,overtime_multiplier,overtime_weekly_hours,updated_by) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE pay_type=VALUES(pay_type),hourly_rate=VALUES(hourly_rate),annual_salary=VALUES(annual_salary),overtime_multiplier=VALUES(overtime_multiplier),overtime_weekly_hours=VALUES(overtime_weekly_hours),updated_by=VALUES(updated_by)');$q->execute([$member,$oid,$type,$hourly,$salary,max(1,(float)($in['overtime_multiplier']??1.5)),max(1,(float)($in['overtime_weekly_hours']??40)),$uid]);$this->audit($oid,$uid,'pay_profile.updated',(string)$member);
    }

    public function payrollPreview(int $oid,string $from,string $through): array
    {
        if(!$from||!$through||$through<$from)throw new InvalidArgumentException('Choose a valid payroll period.');$q=$this->db->prepare('SELECT m.id membership_id,u.name,pp.pay_type,pp.hourly_rate,pp.annual_salary,pp.overtime_multiplier,pp.overtime_weekly_hours,COALESCE(SUM((TIMESTAMPDIFF(MINUTE,te.clocked_in_at,te.clocked_out_at)-te.break_minutes)/60),0) approved_hours FROM memberships m JOIN users u ON u.id=m.user_id LEFT JOIN pay_profiles pp ON pp.membership_id=m.id LEFT JOIN time_entries te ON te.membership_id=m.id AND te.organization_id=m.organization_id AND te.status="approved" AND DATE(te.clocked_in_at) BETWEEN ? AND ? WHERE m.organization_id=? AND m.status="active" GROUP BY m.id,u.name,pp.pay_type,pp.hourly_rate,pp.annual_salary,pp.overtime_multiplier,pp.overtime_weekly_hours ORDER BY u.name');$q->execute([$from,$through,$oid]);$rows=$q->fetchAll();$weeks=max(1,((new DateTimeImmutable($from))->diff(new DateTimeImmutable($through))->days+1)/7);foreach($rows as &$r){$hours=(float)$r['approved_hours'];if($r['pay_type']==='hourly'){$threshold=(float)$r['overtime_weekly_hours']*$weeks;$regular=min($hours,$threshold);$overtime=max(0,$hours-$threshold);$r['regular_hours']=round($regular,2);$r['overtime_hours']=round($overtime,2);$r['estimated_gross']=round(($regular*(float)$r['hourly_rate'])+($overtime*(float)$r['hourly_rate']*(float)$r['overtime_multiplier']),2);}elseif($r['pay_type']==='salary'){$r['regular_hours']=round($hours,2);$r['overtime_hours']=0;$r['estimated_gross']=round(((float)$r['annual_salary']/52)*$weeks,2);}else{$r['regular_hours']=round($hours,2);$r['overtime_hours']=0;$r['estimated_gross']=null;}}return $rows;
    }

    public function recordPayrollExport(int $oid,int $uid,string $from,string $through,array $rows): void
    {
        $hours=array_sum(array_map(fn($r)=>(float)$r['approved_hours'],$rows));$gross=array_sum(array_map(fn($r)=>(float)($r['estimated_gross']??0),$rows));$this->db->prepare('INSERT INTO payroll_exports (organization_id,period_start,period_end,row_count,total_hours,total_gross,exported_by) VALUES (?,?,?,?,?,?,?)')->execute([$oid,$from,$through,count($rows),$hours,$gross,$uid]);$this->audit($oid,$uid,'payroll.exported',$from.' to '.$through);
    }

    public function masterSchedules(int $oid): array
    {
        $q=$this->db->prepare('SELECT ms.*,(SELECT COUNT(*) FROM master_schedule_entries mse WHERE mse.master_schedule_id=ms.id AND mse.active=1) entry_count FROM master_schedules ms WHERE ms.organization_id=? ORDER BY ms.active DESC,ms.name');$q->execute([$oid]);return $q->fetchAll();
    }

    public function masterScheduleEntries(int $oid,?int $masterId=null): array
    {
        if(!$masterId)return [];$q=$this->db->prepare('SELECT mse.*,u.name staff_name,l.name location_name,d.name department_name,p.name position_name FROM master_schedule_entries mse JOIN master_schedules ms ON ms.id=mse.master_schedule_id AND ms.organization_id=mse.organization_id LEFT JOIN memberships m ON m.id=mse.membership_id LEFT JOIN users u ON u.id=m.user_id JOIN locations l ON l.id=mse.location_id JOIN departments d ON d.id=mse.department_id JOIN positions p ON p.id=mse.position_id WHERE mse.organization_id=? AND mse.master_schedule_id=? AND mse.active=1 ORDER BY mse.weekday,mse.starts_at,u.name');$q->execute([$oid,$masterId]);return $q->fetchAll();
    }

    public function masterScheduleGenerations(int $oid,?int $masterId=null): array
    {
        if(!$masterId)return [];$q=$this->db->prepare('SELECT msg.*,u.name generated_by_name,COALESCE(mgp.status,"draft") publication_status FROM master_schedule_generations msg JOIN users u ON u.id=msg.generated_by LEFT JOIN master_generation_publications mgp ON mgp.generation_id=msg.id WHERE msg.organization_id=? AND msg.master_schedule_id=? ORDER BY msg.week_starts_on DESC LIMIT 20');$q->execute([$oid,$masterId]);return $q->fetchAll();
    }

    public function masterGenerationItems(int $oid,?int $generationId): array
    {
        if(!$generationId)return [];$q=$this->db->prepare('SELECT mgi.*,mse.membership_id intended_membership_id,iu.name intended_name,s.assigned_membership_id,au.name assigned_name,l.name location_name,d.name department_name,p.name position_name,mgr.resolution,mgr.notes resolution_notes FROM master_schedule_generation_items mgi JOIN master_schedule_generations msg ON msg.id=mgi.generation_id JOIN master_schedule_entries mse ON mse.id=mgi.master_entry_id LEFT JOIN memberships im ON im.id=mse.membership_id LEFT JOIN users iu ON iu.id=im.user_id LEFT JOIN shifts s ON s.id=mgi.shift_id LEFT JOIN memberships am ON am.id=s.assigned_membership_id LEFT JOIN users au ON au.id=am.user_id JOIN locations l ON l.id=mse.location_id JOIN departments d ON d.id=mse.department_id JOIN positions p ON p.id=mse.position_id LEFT JOIN master_generation_resolutions mgr ON mgr.generation_item_id=mgi.id WHERE msg.organization_id=? AND mgi.generation_id=? ORDER BY mgi.target_date,mse.starts_at');$q->execute([$oid,$generationId]);$rows=$q->fetchAll();foreach($rows as &$row)$row['reasons']=$row['conflict_reasons']?json_decode($row['conflict_reasons'],true):[];return $rows;
    }

    public function createMasterSchedule(int $oid,int $uid,array $in): int
    {
        $name=trim((string)($in['name']??''));$from=(string)($in['effective_from']??'');$to=!empty($in['effective_to'])?(string)$in['effective_to']:null;if($name===''||!$from||($to&&$to<$from))throw new InvalidArgumentException('Enter a name and valid effective dates.');$q=$this->db->prepare('INSERT INTO master_schedules (organization_id,name,effective_from,effective_to,created_by) VALUES (?,?,?,?,?)');$q->execute([$oid,$name,$from,$to,$uid]);$id=(int)$this->db->lastInsertId();$this->db->prepare('INSERT INTO master_schedule_versions (master_schedule_id,version_number) VALUES (?,1)')->execute([$id]);$this->audit($oid,$uid,'master_schedule.created',(string)$id);return $id;
    }

    public function updateMasterScheduleEntry(int $oid,int $uid,array $in): void
    {
        $id=(int)($in['entry_id']??0);$q=$this->db->prepare('SELECT master_schedule_id FROM master_schedule_entries WHERE id=? AND organization_id=?');$q->execute([$id,$oid]);if(!$q->fetchColumn())throw new InvalidArgumentException('Master assignment unavailable.');$member=!empty($in['membership_id'])?$this->membership($oid,$in['membership_id']):null;$weekday=(int)($in['weekday']??0);$location=$this->owned('locations',$oid,$in['location_id']??null,true);$department=$this->owned('departments',$oid,$in['department_id']??null,true);$position=$this->owned('positions',$oid,$in['position_id']??null,true);$start=(string)($in['starts_at']??'');$end=(string)($in['ends_at']??'');if($weekday<1||$weekday>7||!$start||!$end||$end<=$start)throw new InvalidArgumentException('Enter a valid weekday and time range.');$this->db->prepare('UPDATE master_schedule_entries SET membership_id=?,weekday=?,location_id=?,department_id=?,position_id=?,starts_at=?,ends_at=?,notes=? WHERE id=? AND organization_id=?')->execute([$member,$weekday,$location,$department,$position,$start,$end,trim((string)($in['notes']??''))?:null,$id,$oid]);$this->audit($oid,$uid,'master_schedule.entry_updated',(string)$id);
    }

    public function deactivateMasterEntry(int $oid,int $uid,int $id): void
    {
        $q=$this->db->prepare('UPDATE master_schedule_entries SET active=0 WHERE id=? AND organization_id=?');$q->execute([$id,$oid]);if(!$q->rowCount())throw new InvalidArgumentException('Master assignment unavailable.');$this->audit($oid,$uid,'master_schedule.entry_archived',(string)$id);
    }

    public function addMasterScheduleEntriesBulk(int $oid,int $uid,array $in): int
    {
        $days=array_values(array_unique(array_filter(array_map('intval',(array)($in['weekdays']??[])),fn($d)=>$d>=1&&$d<=7)));if(!$days)throw new InvalidArgumentException('Select at least one weekday.');$count=0;$this->db->beginTransaction();try{foreach($days as $day){$copy=$in;$copy['weekday']=$day;$this->addMasterScheduleEntry($oid,$uid,$copy);$count++;}$this->db->commit();return $count;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function duplicateMasterSchedule(int $oid,int $uid,int $sourceId,array $in): int
    {
        $q=$this->db->prepare('SELECT * FROM master_schedules WHERE id=? AND organization_id=?');$q->execute([$sourceId,$oid]);$source=$q->fetch();if(!$source)throw new InvalidArgumentException('Master schedule unavailable.');$in['name']=trim((string)($in['name']??''))?:$source['name'].' v2';$in['effective_from']=$in['effective_from']??date('Y-m-d');$id=$this->createMasterSchedule($oid,$uid,$in);$this->db->prepare('INSERT INTO master_schedule_entries (master_schedule_id,organization_id,membership_id,weekday,location_id,department_id,position_id,starts_at,ends_at,notes,active) SELECT ?,organization_id,membership_id,weekday,location_id,department_id,position_id,starts_at,ends_at,notes,active FROM master_schedule_entries WHERE master_schedule_id=? AND organization_id=?')->execute([$id,$sourceId,$oid]);$q=$this->db->prepare('SELECT COALESCE(MAX(version_number),1)+1 FROM master_schedule_versions WHERE master_schedule_id=? OR source_master_schedule_id=?');$q->execute([$sourceId,$sourceId]);$version=(int)$q->fetchColumn();$this->db->prepare('UPDATE master_schedule_versions SET source_master_schedule_id=?,version_number=? WHERE master_schedule_id=?')->execute([$sourceId,$version,$id]);return $id;
    }

    public function addMasterScheduleEntry(int $oid,int $uid,array $in): void
    {
        $master=(int)($in['master_schedule_id']??0);$q=$this->db->prepare('SELECT COUNT(*) FROM master_schedules WHERE id=? AND organization_id=? AND active=1');$q->execute([$master,$oid]);if(!$q->fetchColumn())throw new InvalidArgumentException('Master schedule unavailable.');$member=!empty($in['membership_id'])?$this->membership($oid,$in['membership_id']):null;$weekday=(int)($in['weekday']??0);if($weekday<1||$weekday>7)throw new InvalidArgumentException('Choose a weekday.');$location=$this->owned('locations',$oid,$in['location_id']??null,true);$department=$this->owned('departments',$oid,$in['department_id']??null,true);$position=$this->owned('positions',$oid,$in['position_id']??null,true);$start=(string)($in['starts_at']??'');$end=(string)($in['ends_at']??'');if(!$start||!$end||$end<=$start)throw new InvalidArgumentException('Enter a valid start and end time.');$q=$this->db->prepare('INSERT INTO master_schedule_entries (master_schedule_id,organization_id,membership_id,weekday,location_id,department_id,position_id,starts_at,ends_at,notes) VALUES (?,?,?,?,?,?,?,?,?,?)');$q->execute([$master,$oid,$member,$weekday,$location,$department,$position,$start,$end,trim((string)($in['notes']??''))?:null]);$this->audit($oid,$uid,'master_schedule.entry_created',(string)$this->db->lastInsertId());
    }

    public function generateMasterWeek(int $oid,int $uid,int $masterId,string $weekStart): array
    {
        if(!$weekStart)throw new InvalidArgumentException('Choose a target week.');$monday=(new DateTimeImmutable($weekStart))->modify('monday this week')->format('Y-m-d');$q=$this->db->prepare('SELECT * FROM master_schedules WHERE id=? AND organization_id=? AND active=1');$q->execute([$masterId,$oid]);$master=$q->fetch();if(!$master)throw new InvalidArgumentException('Master schedule unavailable.');$sunday=(new DateTimeImmutable($monday))->modify('+6 days')->format('Y-m-d');if($sunday<$master['effective_from']||($master['effective_to']&&$monday>$master['effective_to']))throw new InvalidArgumentException('That week is outside this master schedule\'s effective dates.');$entries=$this->masterScheduleEntries($oid,$masterId);if(!$entries)throw new InvalidArgumentException('Add at least one recurring assignment before generating a week.');
        $created=0;$conflicts=0;$existingMax=(int)$this->db->query('SELECT COALESCE(MAX(id),0) FROM shifts')->fetchColumn();$this->db->beginTransaction();try{$q=$this->db->prepare('INSERT INTO master_schedule_generations (master_schedule_id,organization_id,week_starts_on,generated_by) VALUES (?,?,?,?)');try{$q->execute([$masterId,$oid,$monday,$uid]);}catch(PDOException $e){if($e->getCode()==='23000')throw new InvalidArgumentException('This master schedule has already generated that week.');throw $e;}$generation=(int)$this->db->lastInsertId();$insertShift=$this->db->prepare('INSERT INTO shifts (organization_id,location_id,department_id,shift_date,starts_at,ends_at,status,eligibility_mode,exact_position_id,cross_department_mode,notes,created_by,assigned_membership_id) VALUES (?,?,?,?,?,? ,"open","exact",?,"prohibited",?,?,?)');$insertItem=$this->db->prepare('INSERT INTO master_schedule_generation_items (generation_id,master_entry_id,shift_id,target_date,result,conflict_reasons) VALUES (?,?,?,?,?,?)');
            foreach($entries as $entry){$date=(new DateTimeImmutable($monday))->modify('+'.((int)$entry['weekday']-1).' days')->format('Y-m-d');$q=$this->db->prepare('SELECT * FROM schedule_exceptions WHERE organization_id=? AND exception_date=?');$q->execute([$oid,$date]);$exception=$q->fetch();if($exception&&$exception['exception_type']==='closed'){$insertItem->execute([$generation,$entry['id'],null,$date,'skipped',json_encode([$exception['name'].' is configured as closed.'])]);continue;}if($exception&&$exception['exception_type']==='alternate_master'&&(int)$exception['alternate_master_schedule_id']!==$masterId){$insertItem->execute([$generation,$entry['id'],null,$date,'skipped',json_encode(['An alternate master schedule applies on this date.'])]);continue;}$start=$exception&&$exception['exception_type']==='special_hours'&&$exception['starts_at']?max($entry['starts_at'],$exception['starts_at']):$entry['starts_at'];$end=$exception&&$exception['exception_type']==='special_hours'&&$exception['ends_at']?min($entry['ends_at'],$exception['ends_at']):$entry['ends_at'];if($end<=$start){$insertItem->execute([$generation,$entry['id'],null,$date,'skipped',json_encode(['The assignment falls outside special operating hours.'])]);continue;}$q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE id<=? AND organization_id=? AND shift_date=? AND location_id=? AND department_id=? AND exact_position_id=? AND starts_at=? AND ends_at=? AND status<>"cancelled" AND (assigned_membership_id<=>?)');$q->execute([$existingMax,$oid,$date,$entry['location_id'],$entry['department_id'],$entry['position_id'],$start,$end,$entry['membership_id']]);if($q->fetchColumn()){$conflicts++;$insertItem->execute([$generation,$entry['id'],null,$date,'skipped',json_encode(['A matching live shift already exists.'])]);continue;}
                $member=$entry['membership_id']?(int)$entry['membership_id']:null;$insertShift->execute([$oid,$entry['location_id'],$entry['department_id'],$date,$start,$end,$entry['position_id'],$entry['notes'],$uid,$member]);$shift=(int)$this->db->lastInsertId();$result='created';$reasons=[];if($member){$elig=$this->eligibility($oid,$member,$shift,$shift);if($elig['result']==='eligible'){$this->db->prepare('UPDATE shifts SET status="assigned" WHERE id=?')->execute([$shift]);}else{$conflicts++;$result='conflict';$reasons=$elig['reasons'];$this->db->prepare('UPDATE shifts SET assigned_membership_id=NULL,status="open" WHERE id=?')->execute([$shift]);}}$created++;$insertItem->execute([$generation,$entry['id'],$shift,$date,$result,$reasons?json_encode($reasons):null]);}
            $this->db->prepare('UPDATE master_schedule_generations SET shift_count=?,conflict_count=? WHERE id=?')->execute([$created,$conflicts,$generation]);$this->db->prepare('INSERT INTO master_generation_publications (generation_id,status) VALUES (?,"draft")')->execute([$generation]);$this->db->commit();$this->audit($oid,$uid,'master_schedule.generated',$masterId.':'.$monday);return ['created'=>$created,'conflicts'=>$conflicts,'week'=>$monday];
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function resolveMasterConflict(int $oid,int $uid,array $in): void
    {
        $item=(int)($in['generation_item_id']??0);$resolution=(string)($in['resolution']??'left_open');if(!in_array($resolution,['reassigned','override','left_open','dismissed'],true))throw new InvalidArgumentException('Choose a valid resolution.');$q=$this->db->prepare('SELECT mgi.shift_id,mse.membership_id intended_member FROM master_schedule_generation_items mgi JOIN master_schedule_generations msg ON msg.id=mgi.generation_id JOIN master_schedule_entries mse ON mse.id=mgi.master_entry_id WHERE mgi.id=? AND msg.organization_id=?');$q->execute([$item,$oid]);$row=$q->fetch();if(!$row)throw new InvalidArgumentException('Generated assignment unavailable.');$member=null;if(in_array($resolution,['reassigned','override'],true)){if(!$row['shift_id'])throw new InvalidArgumentException('This skipped item has no live shift to assign.');$member=$resolution==='override'?(int)$row['intended_member']:$this->membership($oid,$in['membership_id']??null);$elig=$this->eligibility($oid,$member,(int)$row['shift_id']);if($resolution==='reassigned'&&$elig['result']!=='eligible')throw new InvalidArgumentException(implode(' ',$elig['reasons']));if($resolution==='override'&&trim((string)($in['notes']??''))==='')throw new InvalidArgumentException('Enter an override reason.');$this->db->prepare('UPDATE shifts SET assigned_membership_id=?,status="assigned" WHERE id=? AND organization_id=?')->execute([$member,$row['shift_id'],$oid]);}$this->db->prepare('INSERT INTO master_generation_resolutions (generation_item_id,resolution,membership_id,notes,resolved_by) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE resolution=VALUES(resolution),membership_id=VALUES(membership_id),notes=VALUES(notes),resolved_by=VALUES(resolved_by),resolved_at=NOW()')->execute([$item,$resolution,$member,trim((string)($in['notes']??''))?:null,$uid]);$this->audit($oid,$uid,'master_schedule.conflict_resolved',(string)$item);
    }

    public function publishMasterGeneration(int $oid,int $uid,int $generationId): void
    {
        $q=$this->db->prepare('SELECT COUNT(*) FROM master_schedule_generations WHERE id=? AND organization_id=?');$q->execute([$generationId,$oid]);if(!$q->fetchColumn())throw new InvalidArgumentException('Draft generation unavailable.');$this->db->prepare('INSERT INTO master_generation_publications (generation_id,status,published_by,published_at) VALUES (?,"published",?,NOW()) ON DUPLICATE KEY UPDATE status="published",published_by=VALUES(published_by),published_at=VALUES(published_at)')->execute([$generationId,$uid]);$this->audit($oid,$uid,'master_schedule.published',(string)$generationId);
    }

    public function scheduleExceptions(int $oid): array
    {
        $q=$this->db->prepare('SELECT se.*,ms.name alternate_master_name FROM schedule_exceptions se LEFT JOIN master_schedules ms ON ms.id=se.alternate_master_schedule_id WHERE se.organization_id=? ORDER BY se.exception_date DESC LIMIT 50');$q->execute([$oid]);return $q->fetchAll();
    }

    public function addScheduleException(int $oid,int $uid,array $in): void
    {
        $date=(string)($in['exception_date']??'');$name=trim((string)($in['name']??''));$type=in_array($in['exception_type']??'', ['closed','special_hours','alternate_master'],true)?$in['exception_type']:'closed';if(!$date||$name==='')throw new InvalidArgumentException('Enter an exception date and name.');$alternate=$type==='alternate_master'?(int)($in['alternate_master_schedule_id']??0):null;if($type==='alternate_master'&&!$alternate)throw new InvalidArgumentException('Choose the alternate master schedule.');$this->db->prepare('INSERT INTO schedule_exceptions (organization_id,exception_date,name,exception_type,starts_at,ends_at,alternate_master_schedule_id,notes,created_by) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),exception_type=VALUES(exception_type),starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),alternate_master_schedule_id=VALUES(alternate_master_schedule_id),notes=VALUES(notes),created_by=VALUES(created_by)')->execute([$oid,$date,$name,$type,!empty($in['starts_at'])?$in['starts_at']:null,!empty($in['ends_at'])?$in['ends_at']:null,$alternate,trim((string)($in['notes']??''))?:null,$uid]);$this->audit($oid,$uid,'schedule_exception.saved',$date);
    }

    public function masterEmployeePatterns(int $oid,int $masterId): array
    {
        $q=$this->db->prepare('SELECT m.id membership_id,u.name,COUNT(mse.id) assignment_count,ROUND(SUM(TIME_TO_SEC(TIMEDIFF(mse.ends_at,mse.starts_at))/3600),2) weekly_hours,GROUP_CONCAT(DISTINCT l.name ORDER BY l.name SEPARATOR ", ") locations,GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ", ") departments FROM master_schedule_entries mse JOIN memberships m ON m.id=mse.membership_id JOIN users u ON u.id=m.user_id JOIN locations l ON l.id=mse.location_id JOIN departments d ON d.id=mse.department_id WHERE mse.organization_id=? AND mse.master_schedule_id=? AND mse.active=1 GROUP BY m.id,u.name ORDER BY u.name');$q->execute([$oid,$masterId]);return $q->fetchAll();
    }

    public function masterCoverageGaps(int $oid,int $masterId): array
    {
        $q=$this->db->prepare('SELECT cr.*,l.name location_name,d.name department_name,(SELECT COUNT(*) FROM master_schedule_entries mse WHERE mse.master_schedule_id=? AND mse.organization_id=cr.organization_id AND mse.active=1 AND mse.weekday=cr.weekday AND mse.location_id=cr.location_id AND mse.department_id=cr.department_id AND mse.starts_at<cr.ends_at AND mse.ends_at>cr.starts_at) planned_count FROM coverage_requirements cr JOIN locations l ON l.id=cr.location_id JOIN departments d ON d.id=cr.department_id WHERE cr.organization_id=? AND cr.active=1 ORDER BY cr.weekday,cr.starts_at');$q->execute([$masterId,$oid]);return array_values(array_filter($q->fetchAll(),fn($r)=>(int)$r['planned_count']<(int)$r['required_count']));
    }

    public function weekBoard(int $oid,string $weekStart,?int $locationId=null,?int $departmentId=null): array
    {
        $monday=(new DateTimeImmutable($weekStart?:date('Y-m-d')))->modify('monday this week')->format('Y-m-d');$sql='SELECT s.*,l.name location_name,d.name department_name,p.name position_name,u.name assigned_name FROM shifts s JOIN locations l ON l.id=s.location_id JOIN departments d ON d.id=s.department_id LEFT JOIN positions p ON p.id=s.exact_position_id LEFT JOIN memberships m ON m.id=s.assigned_membership_id LEFT JOIN users u ON u.id=m.user_id WHERE s.organization_id=? AND s.shift_date BETWEEN ? AND DATE_ADD(?,INTERVAL 6 DAY) AND s.status<>"cancelled"';$args=[$oid,$monday,$monday];if($locationId){$sql.=' AND s.location_id=?';$args[]=$locationId;}if($departmentId){$sql.=' AND s.department_id=?';$args[]=$departmentId;}$sql.=' ORDER BY COALESCE(u.name,"zzzz"),s.shift_date,s.starts_at';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function moveShift(int $oid,int $uid,array $in): void
    {
        $shift=$this->owned('shifts',$oid,$in['shift_id']??null,true);$q=$this->db->prepare('SELECT * FROM shifts WHERE id=? AND organization_id=?');$q->execute([$shift,$oid]);$before=$q->fetch();$date=(string)($in['shift_date']??$before['shift_date']);$start=(string)($in['starts_at']??$before['starts_at']);$end=(string)($in['ends_at']??$before['ends_at']);$member=!empty($in['membership_id'])?$this->membership($oid,$in['membership_id']):null;if(!$date||!$start||!$end||$end<=$start)throw new InvalidArgumentException('Enter a valid date and time range.');$this->db->beginTransaction();try{$this->db->prepare('UPDATE shifts SET shift_date=?,starts_at=?,ends_at=?,assigned_membership_id=?,status=? WHERE id=? AND organization_id=?')->execute([$date,$start,$end,$member,$member?'assigned':'open',$shift,$oid]);if($member){$elig=$this->eligibility($oid,$member,$shift,$shift);if($elig['result']!=='eligible'&&!empty($in['require_eligible']))throw new InvalidArgumentException(implode(' ',$elig['reasons']));}$q=$this->db->prepare('SELECT * FROM shifts WHERE id=?');$q->execute([$shift]);$after=$q->fetch();$this->db->prepare('INSERT INTO shift_history (organization_id,shift_id,action,before_json,after_json,reason,changed_by) VALUES (?,? ,"moved",?,?,?,?)')->execute([$oid,$shift,json_encode($before),json_encode($after),trim((string)($in['reason']??''))?:null,$uid]);$this->db->commit();$this->audit($oid,$uid,'shift.moved',(string)$shift);}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function replacementCandidates(int $oid,int $shiftId): array
    {
        $q=$this->db->prepare('SELECT m.id membership_id,u.name,sa.location_id,sa.department_id,wp.expected_weekly_hours FROM memberships m JOIN users u ON u.id=m.user_id LEFT JOIN staff_assignments sa ON sa.membership_id=m.id AND sa.is_primary=1 LEFT JOIN workforce_profiles wp ON wp.membership_id=m.id WHERE m.organization_id=? AND m.status="active"');$q->execute([$oid]);$rows=[];foreach($q->fetchAll() as $person){$elig=$this->eligibility($oid,(int)$person['membership_id'],$shiftId);if($elig['result']==='ineligible')continue;$q=$this->db->prepare('SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(ends_at,starts_at))/3600),0) FROM shifts WHERE organization_id=? AND assigned_membership_id=? AND shift_date BETWEEN DATE_SUB((SELECT shift_date FROM shifts WHERE id=?),INTERVAL WEEKDAY((SELECT shift_date FROM shifts WHERE id=?)) DAY) AND DATE_ADD(DATE_SUB((SELECT shift_date FROM shifts WHERE id=?),INTERVAL WEEKDAY((SELECT shift_date FROM shifts WHERE id=?)) DAY),INTERVAL 6 DAY) AND status IN ("assigned","filled")');$q->execute([$oid,$person['membership_id'],$shiftId,$shiftId,$shiftId,$shiftId]);$hours=(float)$q->fetchColumn();$score=100-$hours+($elig['result']==='eligible'?20:0);$person['scheduled_hours']=round($hours,2);$person['eligibility_result']=$elig['result'];$person['reasons']=$elig['reasons'];$person['recommendation_score']=round($score,1);$rows[]=$person;}usort($rows,fn($a,$b)=>$b['recommendation_score']<=>$a['recommendation_score']);return $rows;
    }

    public function shiftHistory(int $oid,?int $shiftId=null): array
    {
        $sql='SELECT sh.*,u.name changed_by_name,s.shift_date FROM shift_history sh JOIN users u ON u.id=sh.changed_by JOIN shifts s ON s.id=sh.shift_id WHERE sh.organization_id=?';$args=[$oid];if($shiftId){$sql.=' AND sh.shift_id=?';$args[]=$shiftId;}$sql.=' ORDER BY sh.created_at DESC LIMIT 100';$q=$this->db->prepare($sql);$q->execute($args);return $q->fetchAll();
    }

    public function employeeWorkspace(int $oid,int $memberId): array
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT m.id membership_id,m.role,m.status,u.name,u.email,sa.*,l.name location_name,d.name department_name,p.name position_name,wp.employment_type,wp.expected_weekly_hours FROM memberships m JOIN users u ON u.id=m.user_id LEFT JOIN staff_assignments sa ON sa.membership_id=m.id AND sa.is_primary=1 LEFT JOIN locations l ON l.id=sa.location_id LEFT JOIN departments d ON d.id=sa.department_id LEFT JOIN positions p ON p.id=sa.position_id LEFT JOIN workforce_profiles wp ON wp.membership_id=m.id WHERE m.id=? AND m.organization_id=?');$q->execute([$member,$oid]);$profile=$q->fetch();return ['profile'=>$profile,'schedule'=>$this->memberSchedule($oid,$member),'availability'=>$this->availability($oid,$member),'time_off'=>$this->timeOff($oid,$member),'credentials'=>array_values(array_filter($this->credentials($oid),fn($x)=>(int)$x['membership_id']===$member)),'callouts'=>$this->callouts($oid,$member),'time_entries'=>$this->timeEntries($oid,$member)];
    }

    public function notificationPreferences(int $oid,int $memberId): array
    {
        $member=$this->membership($oid,$memberId);$q=$this->db->prepare('SELECT * FROM notification_preferences WHERE organization_id=? AND membership_id=?');$q->execute([$oid,$member]);return $q->fetch()?:['schedule_published'=>1,'schedule_changed'=>1,'coverage_offers'=>1,'request_decisions'=>1,'credential_reminders'=>1,'in_app_enabled'=>1,'email_enabled'=>0];
    }

    public function saveNotificationPreferences(int $oid,int $memberId,array $in): void
    {
        $member=$this->membership($oid,$memberId);$keys=['schedule_published','schedule_changed','coverage_offers','request_decisions','credential_reminders','in_app_enabled','email_enabled'];$values=array_map(fn($k)=>!empty($in[$k])?1:0,$keys);$this->db->prepare('INSERT INTO notification_preferences (membership_id,organization_id,schedule_published,schedule_changed,coverage_offers,request_decisions,credential_reminders,in_app_enabled,email_enabled) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE schedule_published=VALUES(schedule_published),schedule_changed=VALUES(schedule_changed),coverage_offers=VALUES(coverage_offers),request_decisions=VALUES(request_decisions),credential_reminders=VALUES(credential_reminders),in_app_enabled=VALUES(in_app_enabled),email_enabled=VALUES(email_enabled)')->execute([$member,$oid,...$values]);
    }

    public function saveMembershipAccess(int $oid,int $uid,array $in): void
    {
        $member=$this->membership($oid,$in['membership_id']??null);$role=in_array($in['role']??'', ['admin','scheduler','supervisor','member'],true)?$in['role']:'member';$this->db->beginTransaction();try{$this->db->prepare('UPDATE memberships SET role=? WHERE id=? AND organization_id=? AND role<>"owner"')->execute([$role,$member,$oid]);$this->db->prepare('INSERT INTO membership_permissions (membership_id,organization_id,can_schedule,can_approve,can_manage_payroll,can_manage_credentials,read_only,updated_by) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE can_schedule=VALUES(can_schedule),can_approve=VALUES(can_approve),can_manage_payroll=VALUES(can_manage_payroll),can_manage_credentials=VALUES(can_manage_credentials),read_only=VALUES(read_only),updated_by=VALUES(updated_by)')->execute([$member,$oid,!empty($in['can_schedule']),!empty($in['can_approve']),!empty($in['can_manage_payroll']),!empty($in['can_manage_credentials']),!empty($in['read_only']),$uid]);$this->db->prepare('DELETE FROM membership_scopes WHERE membership_id=?')->execute([$member]);$add=$this->db->prepare('INSERT INTO membership_scopes (membership_id,scope_type,resource_id) VALUES (?,?,?)');foreach((array)($in['location_ids']??[]) as $id)if($this->owned('locations',$oid,$id))$add->execute([$member,'location',(int)$id]);foreach((array)($in['department_ids']??[]) as $id)if($this->owned('departments',$oid,$id))$add->execute([$member,'department',(int)$id]);$this->db->commit();$this->audit($oid,$uid,'membership.access_updated',(string)$member);}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function membershipAccess(int $oid): array
    {
        $q=$this->db->prepare('SELECT m.id membership_id,m.role,u.name,mp.* FROM memberships m JOIN users u ON u.id=m.user_id LEFT JOIN membership_permissions mp ON mp.membership_id=m.id WHERE m.organization_id=? AND m.status="active" ORDER BY FIELD(m.role,"owner","admin","scheduler","supervisor","member"),u.name');$q->execute([$oid]);$rows=$q->fetchAll();$s=$this->db->prepare('SELECT scope_type,resource_id FROM membership_scopes WHERE membership_id=?');foreach($rows as &$row){$s->execute([$row['membership_id']]);$row['scopes']=$s->fetchAll();}return $rows;
    }

    public function commandCenter(int $oid,string $date): array
    {
        $q=$this->db->prepare('SELECT COUNT(*) FROM shifts WHERE organization_id=? AND status="open" AND shift_date>=?');$q->execute([$oid,$date]);$open=(int)$q->fetchColumn();$q=$this->db->prepare('SELECT COUNT(*) FROM callouts WHERE organization_id=? AND status="replacement_open"');$q->execute([$oid]);$callouts=(int)$q->fetchColumn();$q=$this->db->prepare('SELECT COUNT(*) FROM time_off_requests WHERE organization_id=? AND status="pending"');$q->execute([$oid]);$timeOff=(int)$q->fetchColumn();$q=$this->db->prepare('SELECT COUNT(*) FROM master_schedule_generation_items mgi JOIN master_schedule_generations msg ON msg.id=mgi.generation_id LEFT JOIN master_generation_resolutions mgr ON mgr.generation_item_id=mgi.id WHERE msg.organization_id=? AND mgi.result IN ("conflict","skipped") AND mgr.generation_item_id IS NULL');$q->execute([$oid]);return ['open_shifts'=>$open,'callouts'=>$callouts,'time_off'=>$timeOff,'master_conflicts'=>(int)$q->fetchColumn(),'coverage_gaps'=>count(array_filter($this->coverageRequirements($oid,$date),fn($x)=>(int)$x['assigned_count']<(int)$x['required_count']))];
    }

    private function userForMembership(int $oid,int $memberId): int{$q=$this->db->prepare('SELECT user_id FROM memberships WHERE id=? AND organization_id=?');$q->execute([$memberId,$oid]);return (int)$q->fetchColumn();}

    private function membership(int $organizationId,mixed $id): int{$q=$this->db->prepare('SELECT id FROM memberships WHERE id=? AND organization_id=? AND status="active"');$q->execute([(int)$id,$organizationId]);$found=$q->fetchColumn();if(!$found)throw new InvalidArgumentException('Staff member unavailable.');return (int)$found;}
    private function owned(string $table,int $organizationId,mixed $id,bool $required=false): ?int{if(!$id){if($required)throw new InvalidArgumentException('A required organization resource is missing.');return null;}$allowed=['locations','departments','positions','providers','stations','work_functions','qualifications','eligibility_groups','shifts'];if(!in_array($table,$allowed,true))throw new LogicException('Invalid resource type.');$q=$this->db->prepare("SELECT id FROM {$table} WHERE id=? AND organization_id=?".($table!=='shifts'?' AND active=1':''));$q->execute([(int)$id,$organizationId]);$found=$q->fetchColumn();if(!$found)throw new InvalidArgumentException('A selected organization resource is unavailable.');return (int)$found;}
    private function audit(int $oid,int $uid,string $action,string $value):void{$q=$this->db->prepare('INSERT INTO audit_logs (organization_id,user_id,action,entity_type,metadata_json,ip_address) VALUES (?, ?, ?, "scheduling", ?, ?)');$q->execute([$oid,$uid,$action,json_encode(['value'=>$value]),$_SERVER['REMOTE_ADDR']??null]);}
}
