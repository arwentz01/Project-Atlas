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
        $q=$this->db->prepare('SELECT s.*,l.name location_name,d.name department_name FROM shifts s JOIN locations l ON l.id=s.location_id JOIN departments d ON d.id=s.department_id WHERE s.organization_id=? AND s.assigned_membership_id=? AND s.status IN ("assigned","filled") ORDER BY s.shift_date,s.starts_at');$q->execute([$oid,$memberId]);return $q->fetchAll();
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

    private function membership(int $organizationId,mixed $id): int{$q=$this->db->prepare('SELECT id FROM memberships WHERE id=? AND organization_id=? AND status="active"');$q->execute([(int)$id,$organizationId]);$found=$q->fetchColumn();if(!$found)throw new InvalidArgumentException('Staff member unavailable.');return (int)$found;}
    private function owned(string $table,int $organizationId,mixed $id,bool $required=false): ?int{if(!$id){if($required)throw new InvalidArgumentException('A required organization resource is missing.');return null;}$allowed=['locations','departments','positions','providers','stations','work_functions','qualifications','eligibility_groups','shifts'];if(!in_array($table,$allowed,true))throw new LogicException('Invalid resource type.');$q=$this->db->prepare("SELECT id FROM {$table} WHERE id=? AND organization_id=?".($table!=='shifts'?' AND active=1':''));$q->execute([(int)$id,$organizationId]);$found=$q->fetchColumn();if(!$found)throw new InvalidArgumentException('A selected organization resource is unavailable.');return (int)$found;}
    private function audit(int $oid,int $uid,string $action,string $value):void{$q=$this->db->prepare('INSERT INTO audit_logs (organization_id,user_id,action,entity_type,metadata_json,ip_address) VALUES (?, ?, ?, "scheduling", ?, ?)');$q->execute([$oid,$uid,$action,json_encode(['value'=>$value]),$_SERVER['REMOTE_ADDR']??null]);}
}
