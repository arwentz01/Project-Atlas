<?php

declare(strict_types=1);

final class AtlasRepository
{
    public function __construct(private PDO $db) {}

    public function organizationsForUser(int $userId): array
    {
        $sql = 'SELECT o.*, m.role, m.id AS membership_id FROM organizations o JOIN memberships m ON m.organization_id = o.id WHERE m.user_id = ? AND m.status = "active" ORDER BY o.name';
        $statement = $this->db->prepare($sql);
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function organizationForUser(int $organizationId, int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT o.*, m.role, m.id AS membership_id FROM organizations o JOIN memberships m ON m.organization_id = o.id WHERE o.id = ? AND m.user_id = ? AND m.status = "active" LIMIT 1');
        $statement->execute([$organizationId, $userId]);
        return $statement->fetch() ?: null;
    }

    public function createOrganization(int $userId, string $name, string $type, string $locationName, string $timezone): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Organization name is required.');
        }
        $this->db->beginTransaction();
        try {
            $slug = $this->uniqueSlug($name);
            $statement = $this->db->prepare('INSERT INTO organizations (name, slug, organization_type, timezone, created_by) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$name, $slug, $type, $timezone, $userId]);
            $organizationId = (int)$this->db->lastInsertId();
            $this->db->prepare('INSERT INTO memberships (organization_id, user_id, role, status) VALUES (?, ?, "owner", "active")')->execute([$organizationId, $userId]);
            if (trim($locationName) !== '') {
                $this->db->prepare('INSERT INTO locations (organization_id, name, timezone, is_primary) VALUES (?, ?, ?, 1)')->execute([$organizationId, trim($locationName), $timezone]);
            }
            $this->audit($organizationId, $userId, 'organization.created', 'organization', $organizationId, ['name' => $name]);
            $this->db->commit();
            return $organizationId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function overview(int $organizationId): array
    {
        $result = [];
        foreach (['locations', 'departments', 'positions', 'supervisor_groups'] as $table) {
            $statement = $this->db->prepare("SELECT * FROM {$table} WHERE organization_id = ? AND active = 1 ORDER BY name");
            $statement->execute([$organizationId]);
            $result[$table] = $statement->fetchAll();
            $statement = $this->db->prepare("SELECT * FROM {$table} WHERE organization_id = ? ORDER BY active DESC, name");
            $statement->execute([$organizationId]);
            $result['all_' . $table] = $statement->fetchAll();
        }
        $statement = $this->db->prepare('SELECT m.id AS membership_id, m.role, u.id, u.name, u.email, d.name AS department_name, p.name AS position_name, sg.name AS supervisor_group_name FROM memberships m JOIN users u ON u.id = m.user_id LEFT JOIN staff_assignments sa ON sa.membership_id = m.id AND sa.is_primary = 1 LEFT JOIN departments d ON d.id = sa.department_id LEFT JOIN positions p ON p.id = sa.position_id LEFT JOIN supervisor_groups sg ON sg.id = sa.supervisor_group_id WHERE m.organization_id = ? AND m.status = "active" ORDER BY u.name');
        $statement->execute([$organizationId]);
        $result['people'] = $statement->fetchAll();
        $statement = $this->db->prepare('SELECT m.id AS membership_id,m.role,m.status,u.name,u.email FROM memberships m JOIN users u ON u.id=m.user_id WHERE m.organization_id=? ORDER BY m.status,u.name');
        $statement->execute([$organizationId]);
        $result['all_people'] = $statement->fetchAll();
        $statement = $this->db->prepare('SELECT i.*, d.name AS department_name, p.name AS position_name FROM invitations i LEFT JOIN departments d ON d.id = i.department_id LEFT JOIN positions p ON p.id = i.position_id WHERE i.organization_id = ? AND i.accepted_at IS NULL AND i.expires_at > NOW() ORDER BY i.created_at DESC');
        $statement->execute([$organizationId]);
        $result['invitations'] = $statement->fetchAll();
        return $result;
    }

    public function addLocation(int $organizationId, int $userId, string $name): void
    {
        $this->insertNamed('locations', $organizationId, $name, []);
        $this->audit($organizationId, $userId, 'location.created', 'location', (int)$this->db->lastInsertId(), ['name' => trim($name)]);
    }

    public function addDepartment(int $organizationId, int $userId, string $name, ?int $locationId, string $color): void
    {
        $locationId = $this->ownedId('locations', $organizationId, $locationId);
        $this->insertNamed('departments', $organizationId, $name, ['location_id' => $locationId, 'color' => $color]);
        $this->audit($organizationId, $userId, 'department.created', 'department', (int)$this->db->lastInsertId(), ['name' => trim($name)]);
    }

    public function addPosition(int $organizationId, int $userId, string $name, ?int $departmentId, string $category, string $color): void
    {
        $departmentId = $this->ownedId('departments', $organizationId, $departmentId);
        $this->insertNamed('positions', $organizationId, $name, ['department_id' => $departmentId, 'category' => $category, 'color' => $color]);
        $this->audit($organizationId, $userId, 'position.created', 'position', (int)$this->db->lastInsertId(), ['name' => trim($name)]);
    }

    public function addSupervisorGroup(int $organizationId, int $userId, string $name, ?int $departmentId, array $supervisorMembershipIds): void
    {
        $departmentId = $this->ownedId('departments', $organizationId, $departmentId);
        $this->db->beginTransaction();
        try {
            $this->insertNamed('supervisor_groups', $organizationId, $name, ['department_id' => $departmentId ?: null]);
            $groupId = (int)$this->db->lastInsertId();
            $membershipCheck = $this->db->prepare('SELECT id FROM memberships WHERE id = ? AND organization_id = ? AND status = "active"');
            $insert = $this->db->prepare('INSERT IGNORE INTO supervisor_group_members (supervisor_group_id, membership_id) VALUES (?, ?)');
            foreach ($supervisorMembershipIds as $membershipId) {
                $membershipCheck->execute([(int)$membershipId, $organizationId]);
                if ($membershipCheck->fetchColumn()) {
                    $insert->execute([$groupId, (int)$membershipId]);
                }
            }
            if ($departmentId) {
                $this->db->prepare('UPDATE departments SET default_supervisor_group_id = ? WHERE id = ? AND organization_id = ?')->execute([$groupId, $departmentId, $organizationId]);
                $this->db->prepare('UPDATE staff_assignments SET supervisor_group_id = ? WHERE department_id = ? AND organization_id = ?')->execute([$groupId, $departmentId, $organizationId]);
            }
            $this->audit($organizationId, $userId, 'supervisor_group.created', 'supervisor_group', $groupId, ['name' => trim($name)]);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function createInvitation(int $organizationId, int $userId, array $input, string $baseUrl): string
    {
        $email = strtolower(trim((string)($input['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address for the invitation.');
        }
        $token = bin2hex(random_bytes(24));
        $departmentId = $this->ownedId('departments', $organizationId, !empty($input['department_id']) ? (int)$input['department_id'] : null);
        $positionId = $this->ownedId('positions', $organizationId, !empty($input['position_id']) ? (int)$input['position_id'] : null);
        $locationId = $this->ownedId('locations', $organizationId, !empty($input['location_id']) ? (int)$input['location_id'] : null);
        $statement = $this->db->prepare('INSERT INTO invitations (organization_id, email, role, department_id, position_id, location_id, token_hash, invited_by, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 14 DAY))');
        $statement->execute([
            $organizationId,
            $email,
            $input['role'] ?? 'member',
            $departmentId,
            $positionId,
            $locationId,
            hash('sha256', $token),
            $userId,
        ]);
        $invitationId = (int)$this->db->lastInsertId();
        $this->audit($organizationId, $userId, 'invitation.created', 'invitation', $invitationId, ['email' => $email]);
        return $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'token=' . $token;
    }

    public function invitation(string $token): ?array
    {
        $statement = $this->db->prepare('SELECT i.*, o.name AS organization_name FROM invitations i JOIN organizations o ON o.id = i.organization_id WHERE i.token_hash = ? AND i.accepted_at IS NULL AND i.expires_at > NOW() LIMIT 1');
        $statement->execute([hash('sha256', $token)]);
        return $statement->fetch() ?: null;
    }

    public function acceptInvitation(string $token, int $userId): int
    {
        $invitation = $this->invitation($token);
        if (!$invitation) {
            throw new InvalidArgumentException('This invitation is invalid or has expired.');
        }
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('INSERT INTO memberships (organization_id, user_id, role, status) VALUES (?, ?, ?, "active") ON DUPLICATE KEY UPDATE role = VALUES(role), status = "active"');
            $statement->execute([$invitation['organization_id'], $userId, $invitation['role']]);
            $membership = $this->db->prepare('SELECT id FROM memberships WHERE organization_id = ? AND user_id = ?');
            $membership->execute([$invitation['organization_id'], $userId]);
            $membershipId = (int)$membership->fetchColumn();
            $groupStatement = $this->db->prepare('SELECT default_supervisor_group_id FROM departments WHERE id = ? AND organization_id = ?');
            $groupStatement->execute([$invitation['department_id'], $invitation['organization_id']]);
            $groupId = $groupStatement->fetchColumn() ?: null;
            $assignment = $this->db->prepare('INSERT INTO staff_assignments (organization_id, membership_id, location_id, department_id, position_id, supervisor_group_id, is_primary) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE location_id = VALUES(location_id), department_id = VALUES(department_id), position_id = VALUES(position_id), supervisor_group_id = VALUES(supervisor_group_id)');
            $assignment->execute([$invitation['organization_id'], $membershipId, $invitation['location_id'], $invitation['department_id'], $invitation['position_id'], $groupId]);
            $this->db->prepare('UPDATE invitations SET accepted_at = NOW(), accepted_by = ? WHERE id = ?')->execute([$userId, $invitation['id']]);
            $this->audit((int)$invitation['organization_id'], $userId, 'invitation.accepted', 'membership', $membershipId, []);
            $this->db->commit();
            return (int)$invitation['organization_id'];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function manageStructure(int $organizationId,int $userId,string $type,int $id,string $name,bool $active): void
    {
        $tables=['location'=>'locations','department'=>'departments','position'=>'positions','supervisor_group'=>'supervisor_groups'];if(!isset($tables[$type]))throw new InvalidArgumentException('Unknown organization resource.');$name=trim($name);if($name==='')throw new InvalidArgumentException('A name is required.');$table=$tables[$type];$q=$this->db->prepare("UPDATE {$table} SET name=?,active=? WHERE id=? AND organization_id=?");$q->execute([$name,$active?1:0,$id,$organizationId]);if(!$q->rowCount())throw new InvalidArgumentException('Organization resource unavailable or unchanged.');$this->audit($organizationId,$userId,$type.'.updated',$type,$id,['name'=>$name,'active'=>$active]);
    }

    public function setMembershipStatus(int $organizationId,int $userId,int $membershipId,string $status): void
    {
        if(!in_array($status,['active','inactive'],true))throw new InvalidArgumentException('Invalid membership status.');$q=$this->db->prepare('SELECT role,user_id FROM memberships WHERE id=? AND organization_id=?');$q->execute([$membershipId,$organizationId]);$m=$q->fetch();if(!$m)throw new InvalidArgumentException('Team member unavailable.');if($m['role']==='owner'&&$status==='inactive')throw new InvalidArgumentException('The organization owner cannot be deactivated.');$this->db->prepare('UPDATE memberships SET status=? WHERE id=? AND organization_id=?')->execute([$status,$membershipId,$organizationId]);$this->audit($organizationId,$userId,'membership.'.$status,'membership',$membershipId,[]);
    }

    public function cancelInvitation(int $organizationId,int $userId,int $invitationId): void
    {
        $q=$this->db->prepare('UPDATE invitations SET expires_at=NOW() WHERE id=? AND organization_id=? AND accepted_at IS NULL');$q->execute([$invitationId,$organizationId]);if(!$q->rowCount())throw new InvalidArgumentException('Pending invitation unavailable.');$this->audit($organizationId,$userId,'invitation.cancelled','invitation',$invitationId,[]);
    }

    public function organizationSettings(int $organizationId): array
    {
        $q=$this->db->prepare('SELECT o.name,o.timezone,o.organization_type,os.* FROM organizations o LEFT JOIN organization_settings os ON os.organization_id=o.id WHERE o.id=?');$q->execute([$organizationId]);$row=$q->fetch()?:[];$row['operating_hours']=$row['operating_hours_json']?json_decode($row['operating_hours_json'],true):[];return $row;
    }

    public function saveOrganizationSettings(int $organizationId,int $userId,array $input): void
    {
        $name=trim((string)($input['name']??''));$timezone=trim((string)($input['timezone']??''));if($name===''||$timezone==='')throw new InvalidArgumentException('Organization name and timezone are required.');$hours=[];foreach(range(1,7) as $day){$open=trim((string)($input['open_'.$day]??''));$close=trim((string)($input['close_'.$day]??''));if($open&&$close){if($close<=$open)throw new InvalidArgumentException('Operating-hour closing times must follow opening times.');$hours[$day]=['open'=>$open,'close'=>$close];}}$primary=preg_match('/^#[0-9a-f]{6}$/i',(string)($input['primary_color']??''))?$input['primary_color']:'#2867d8';$secondary=preg_match('/^#[0-9a-f]{6}$/i',(string)($input['secondary_color']??''))?$input['secondary_color']:'#7756d9';$this->db->beginTransaction();try{$this->db->prepare('UPDATE organizations SET name=?,timezone=? WHERE id=?')->execute([$name,$timezone,$organizationId]);$this->db->prepare('INSERT INTO organization_settings (organization_id,display_name,timezone,week_starts_on,operating_hours_json,primary_color,secondary_color,logo_text,updated_by) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE display_name=VALUES(display_name),timezone=VALUES(timezone),week_starts_on=VALUES(week_starts_on),operating_hours_json=VALUES(operating_hours_json),primary_color=VALUES(primary_color),secondary_color=VALUES(secondary_color),logo_text=VALUES(logo_text),updated_by=VALUES(updated_by)')->execute([$organizationId,$name,$timezone,max(1,min(7,(int)($input['week_starts_on']??1))),json_encode($hours),$primary,$secondary,trim((string)($input['logo_text']??''))?:null,$userId]);$this->audit($organizationId,$userId,'organization.settings_updated','organization',$organizationId,[]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function localAccountSettings(int $organizationId): array
    {
        $q=$this->db->prepare('SELECT COALESCE(oss.allow_local_accounts,0) allow_local_accounts FROM organizations o LEFT JOIN organization_security_settings oss ON oss.organization_id=o.id WHERE o.id=?');$q->execute([$organizationId]);$settings=$q->fetch()?:['allow_local_accounts'=>0];
        $q=$this->db->prepare('SELECT la.username,la.must_change_password,la.created_at,u.name,m.role,m.status FROM local_accounts la JOIN users u ON u.id=la.user_id JOIN memberships m ON m.user_id=u.id AND m.organization_id=la.organization_id WHERE la.organization_id=? ORDER BY u.name');$q->execute([$organizationId]);$settings['accounts']=$q->fetchAll();return $settings;
    }

    public function saveLocalAccountSettings(int $organizationId,int $userId,bool $enabled): void
    {
        $this->db->prepare('INSERT INTO organization_security_settings (organization_id,allow_local_accounts,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE allow_local_accounts=VALUES(allow_local_accounts),updated_by=VALUES(updated_by)')->execute([$organizationId,$enabled?1:0,$userId]);$this->audit($organizationId,$userId,'local_accounts.settings_updated','organization',$organizationId,['enabled'=>$enabled]);
    }

    public function createLocalAccount(int $organizationId,int $userId,array $input): array
    {
        $q=$this->db->prepare('SELECT COALESCE(allow_local_accounts,0) FROM organization_security_settings WHERE organization_id=?');$q->execute([$organizationId]);if(!(bool)$q->fetchColumn())throw new InvalidArgumentException('Enable local accounts before creating one.');
        $name=trim((string)($input['name']??''));$username=strtolower(trim((string)($input['username']??'')));$password=(string)($input['password']??'');$role=(string)($input['role']??'member');
        if($name==='')throw new InvalidArgumentException('Enter the user’s name.');if(!preg_match('/^[a-z0-9][a-z0-9._-]{2,39}$/',$username))throw new InvalidArgumentException('Username must be 3–40 characters using letters, numbers, dots, dashes, or underscores.');if(strlen($password)<8)throw new InvalidArgumentException('Temporary password must be at least 8 characters.');if(!in_array($role,['admin','scheduler','supervisor','member'],true))throw new InvalidArgumentException('Choose a valid access role.');
        $location=$this->ownedId('locations',$organizationId,!empty($input['location_id'])?(int)$input['location_id']:null);$department=$this->ownedId('departments',$organizationId,!empty($input['department_id'])?(int)$input['department_id']:null);$position=$this->ownedId('positions',$organizationId,!empty($input['position_id'])?(int)$input['position_id']:null);
        $this->db->beginTransaction();try{$email=$username.'.'.bin2hex(random_bytes(4)).'@local.atlas.invalid';$this->db->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);$newUser=(int)$this->db->lastInsertId();$this->db->prepare('INSERT INTO local_accounts (user_id,organization_id,username,created_by) VALUES (?,?,?,?)')->execute([$newUser,$organizationId,$username,$userId]);$this->db->prepare('INSERT INTO memberships (organization_id,user_id,role,status) VALUES (?,?,?,"active")')->execute([$organizationId,$newUser,$role]);$membership=(int)$this->db->lastInsertId();$this->db->prepare('INSERT INTO staff_assignments (organization_id,membership_id,location_id,department_id,position_id,is_primary) VALUES (?,?,?,?,?,1)')->execute([$organizationId,$membership,$location,$department,$position]);$this->audit($organizationId,$userId,'local_account.created','membership',$membership,['username'=>$username,'role'=>$role]);$this->db->commit();return ['username'=>$username,'password'=>$password,'role'=>$role,'name'=>$name];}catch(PDOException $e){if($this->db->inTransaction())$this->db->rollBack();if(($e->errorInfo[1]??null)===1062)throw new InvalidArgumentException('That username is already in use.');throw $e;}catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function seedSmokeTestAccounts(int $organizationId,int $userId): array
    {
        $this->saveLocalAccountSettings($organizationId,$userId,true);$suffix=substr(bin2hex(random_bytes(3)),0,6);$password='Atlas!'.bin2hex(random_bytes(4));
        $this->db->prepare('INSERT IGNORE INTO locations (organization_id,name,timezone,is_primary) SELECT ?,"Demo Main Clinic",timezone,0 FROM organizations WHERE id=?')->execute([$organizationId,$organizationId]);$q=$this->db->prepare('SELECT id FROM locations WHERE organization_id=? AND name="Demo Main Clinic"');$q->execute([$organizationId]);$location=(int)$q->fetchColumn();
        $this->db->prepare('INSERT IGNORE INTO departments (organization_id,location_id,name,color) VALUES (?, ?, "Demo Clinical Support", "#2867d8"),(?, ?, "Demo Front Office", "#7756d9")')->execute([$organizationId,$location,$organizationId,$location]);$q=$this->db->prepare('SELECT id FROM departments WHERE organization_id=? AND name="Demo Clinical Support"');$q->execute([$organizationId]);$clinical=(int)$q->fetchColumn();$q=$this->db->prepare('SELECT id FROM departments WHERE organization_id=? AND name="Demo Front Office"');$q->execute([$organizationId]);$front=(int)$q->fetchColumn();
        $this->db->prepare('INSERT IGNORE INTO positions (organization_id,department_id,name,category,color) VALUES (?, ?, "Demo Medical Assistant", "clinical_support", "#0d9a7c"),(?, ?, "Demo Patient Services", "front_office", "#7756d9")')->execute([$organizationId,$clinical,$organizationId,$front]);$q=$this->db->prepare('SELECT id FROM positions WHERE organization_id=? AND name="Demo Medical Assistant"');$q->execute([$organizationId]);$position=(int)$q->fetchColumn();
        $this->db->prepare('INSERT IGNORE INTO providers (organization_id,location_id,department_id,name,specialty) VALUES (?,?,?,?,"Primary Care")')->execute([$organizationId,$location,$clinical,'Dr. Atlas Demo']);$this->db->prepare('INSERT IGNORE INTO stations (organization_id,location_id,department_id,name) VALUES (?,?,?,"Demo Pod A")')->execute([$organizationId,$location,$clinical]);$this->db->prepare('INSERT IGNORE INTO work_functions (organization_id,department_id,name) VALUES (?, ?, "Demo Inbox Coverage")')->execute([$organizationId,$clinical]);$this->db->prepare('INSERT IGNORE INTO qualifications (organization_id,name) VALUES (?,"Demo CPR")')->execute([$organizationId]);$this->db->prepare('INSERT IGNORE INTO request_types (organization_id,name,paid) VALUES (?,"Demo PTO",1),(?,"Demo Unpaid Leave",0)')->execute([$organizationId,$organizationId]);
        $accounts=[];foreach(['admin','scheduler','supervisor','member'] as $role)$accounts[]=$this->createLocalAccount($organizationId,$userId,['name'=>'Demo '.ucfirst($role),'username'=>'demo.'.$role.'.'.$suffix,'password'=>$password,'role'=>$role,'location_id'=>$location,'department_id'=>$clinical,'position_id'=>$position]);
        $periodStart=date('Y-m-d',strtotime('monday this week'));$periodEnd=date('Y-m-d',strtotime($periodStart.' +13 days'));$this->db->prepare('INSERT INTO schedule_periods (organization_id,name,starts_on,ends_on,status,created_by) VALUES (?,"Demo Smoke Test",?,?,"published",?)')->execute([$organizationId,$periodStart,$periodEnd,$userId]);$period=(int)$this->db->lastInsertId();$member=$this->db->prepare('SELECT m.id FROM memberships m JOIN local_accounts la ON la.user_id=m.user_id WHERE m.organization_id=? AND la.username=?');$member->execute([$organizationId,$accounts[3]['username']]);$memberId=(int)$member->fetchColumn();$shift=$this->db->prepare('INSERT INTO shifts (organization_id,schedule_period_id,location_id,department_id,assigned_membership_id,shift_date,starts_at,ends_at,status,eligibility_mode,exact_position_id,created_by,notes) VALUES (?,?,?,?,?,?,?,?,"assigned","exact",?,?,"Demo seeded shift")');foreach(range(0,4) as $day)$shift->execute([$organizationId,$period,$location,$clinical,$memberId,date('Y-m-d',strtotime($periodStart.' +'.$day.' days')),'08:00','16:30',$position,$userId]);
        $accounts[]=['name'=>'Current organization owner','username'=>'Use your existing owner account','password'=>'Existing password','role'=>'owner'];$this->audit($organizationId,$userId,'demo.smoke_accounts_created','organization',$organizationId,['suffix'=>$suffix]);return $accounts;
    }

    public function departmentDefaults(int $organizationId): array
    {
        $q=$this->db->prepare('SELECT d.id department_id,d.name,dsd.* FROM departments d LEFT JOIN department_schedule_defaults dsd ON dsd.department_id=d.id WHERE d.organization_id=? AND d.active=1 ORDER BY d.name');$q->execute([$organizationId]);return $q->fetchAll();
    }

    public function saveDepartmentDefault(int $organizationId,int $userId,array $input): void
    {
        $department=$this->ownedId('departments',$organizationId,(int)($input['department_id']??0));if(!$department)throw new InvalidArgumentException('Department unavailable.');$start=!empty($input['default_starts_at'])?$input['default_starts_at']:null;$end=!empty($input['default_ends_at'])?$input['default_ends_at']:null;if($start&&$end&&$end<=$start)throw new InvalidArgumentException('Default ending time must follow the starting time.');$this->db->prepare('INSERT INTO department_schedule_defaults (department_id,organization_id,default_starts_at,default_ends_at,minimum_staff,allow_cross_department,updated_by) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE default_starts_at=VALUES(default_starts_at),default_ends_at=VALUES(default_ends_at),minimum_staff=VALUES(minimum_staff),allow_cross_department=VALUES(allow_cross_department),updated_by=VALUES(updated_by)')->execute([$department,$organizationId,$start,$end,max(0,min(999,(int)($input['minimum_staff']??0))),!empty($input['allow_cross_department']),$userId]);$this->audit($organizationId,$userId,'department.defaults_updated','department',$department,[]);
    }

    public function importEmployees(int $organizationId,int $userId,string $csv,string $baseUrl): array
    {
        $lines=preg_split('/\r\n|\r|\n/',trim($csv));if(count($lines)<2)throw new InvalidArgumentException('CSV must include a header and at least one employee.');$header=array_map(fn($x)=>strtolower(trim($x)),str_getcsv(array_shift($lines)));$required=['email','role','location','department','position'];foreach($required as $column)if(!in_array($column,$header,true))throw new InvalidArgumentException('CSV header must include: '.implode(', ',$required).'.');$lookup=function(string $table,string $name)use($organizationId){if(trim($name)==='')return null;$q=$this->db->prepare("SELECT id FROM {$table} WHERE organization_id=? AND LOWER(name)=LOWER(?) AND active=1");$q->execute([$organizationId,trim($name)]);return $q->fetchColumn()?:null;};$rows=[];$invalid=0;foreach($lines as $i=>$line){if(trim($line)==='')continue;$values=str_getcsv($line);$row=array_combine($header,array_pad($values,count($header),''));$errors=[];$email=strtolower(trim((string)$row['email']));$role=trim((string)$row['role']);if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Invalid email.';if(!in_array($role,['member','supervisor','scheduler','admin'],true))$errors[]='Invalid role.';$location=$lookup('locations',(string)$row['location']);$department=$lookup('departments',(string)$row['department']);$position=$lookup('positions',(string)$row['position']);if($row['location']&&!$location)$errors[]='Location not found.';if($row['department']&&!$department)$errors[]='Department not found.';if($row['position']&&!$position)$errors[]='Position not found.';$q=$this->db->prepare('SELECT COUNT(*) FROM invitations WHERE organization_id=? AND email=? AND accepted_at IS NULL AND expires_at>NOW()');$q->execute([$organizationId,$email]);if($q->fetchColumn())$errors[]='A pending invitation already exists.';$rows[]=['row_number'=>$i+2,'email'=>$email,'role'=>$role,'location_name'=>$row['location'],'department_name'=>$row['department'],'position_name'=>$row['position'],'location_id'=>$location,'department_id'=>$department,'position_id'=>$position,'errors'=>$errors];if($errors)$invalid++;}$status=$invalid?'rejected':'validated';$this->db->prepare('INSERT INTO employee_import_batches (organization_id,status,row_count,valid_count,invalid_count,created_by) VALUES (?,?,?,?,?,?)')->execute([$organizationId,$status,count($rows),count($rows)-$invalid,$invalid,$userId]);$batch=(int)$this->db->lastInsertId();$insert=$this->db->prepare('INSERT INTO employee_import_rows (batch_id,row_number,email,role,location_name,department_name,position_name,status,error_message) VALUES (?,?,?,?,?,?,?,?,?)');foreach($rows as $row)$insert->execute([$batch,$row['row_number'],$row['email'],$row['role'],$row['location_name'],$row['department_name'],$row['position_name'],$row['errors']?'invalid':'valid',$row['errors']?implode(' ',$row['errors']):null]);if($invalid)return ['batch_id'=>$batch,'imported'=>0,'invalid'=>$invalid,'links'=>[]];$links=[];foreach($rows as $row){$links[]=$this->createInvitation($organizationId,$userId,['email'=>$row['email'],'role'=>$row['role'],'location_id'=>$row['location_id'],'department_id'=>$row['department_id'],'position_id'=>$row['position_id']],$baseUrl);$invitation=(int)$this->db->lastInsertId();$this->db->prepare('UPDATE employee_import_rows SET status="imported",invitation_id=? WHERE batch_id=? AND row_number=?')->execute([$invitation,$batch,$row['row_number']]);}$this->db->prepare('UPDATE employee_import_batches SET status="imported" WHERE id=?')->execute([$batch]);return ['batch_id'=>$batch,'imported'=>count($rows),'invalid'=>0,'links'=>$links];
    }

    public function importBatches(int $organizationId): array
    {
        $q=$this->db->prepare('SELECT * FROM employee_import_batches WHERE organization_id=? ORDER BY created_at DESC LIMIT 20');$q->execute([$organizationId]);return $q->fetchAll();
    }

    public function workforceAdmin(int $organizationId,int $membershipId): array
    {
        $q=$this->db->prepare('SELECT er.*,m.status membership_status FROM memberships m LEFT JOIN employment_records er ON er.membership_id=m.id WHERE m.id=? AND m.organization_id=?');$q->execute([$membershipId,$organizationId]);$employment=$q->fetch()?:[];$q=$this->db->prepare('SELECT swa.*,l.name location_name,d.name department_name,p.name position_name FROM secondary_work_assignments swa LEFT JOIN locations l ON l.id=swa.location_id LEFT JOIN departments d ON d.id=swa.department_id LEFT JOIN positions p ON p.id=swa.position_id WHERE swa.organization_id=? AND swa.membership_id=? AND swa.active=1 ORDER BY swa.created_at DESC');$q->execute([$organizationId,$membershipId]);$assignments=$q->fetchAll();$q=$this->db->prepare('SELECT emn.*,u.name author_name FROM employee_manager_notes emn JOIN users u ON u.id=emn.created_by WHERE emn.organization_id=? AND emn.membership_id=? ORDER BY emn.created_at DESC');$q->execute([$organizationId,$membershipId]);$notes=$q->fetchAll();$q=$this->db->prepare('SELECT eoi.*,u.name completed_by_name FROM employee_onboarding_items eoi LEFT JOIN users u ON u.id=eoi.completed_by WHERE eoi.organization_id=? AND eoi.membership_id=? ORDER BY eoi.completed_at,eoi.due_on,eoi.created_at');$q->execute([$organizationId,$membershipId]);$onboarding=$q->fetchAll();$q=$this->db->prepare('SELECT id,snapshot_type,created_at FROM employee_profile_snapshots WHERE organization_id=? AND membership_id=? ORDER BY created_at DESC LIMIT 20');$q->execute([$organizationId,$membershipId]);$snapshots=$q->fetchAll();$q=$this->db->prepare('SELECT ed.*,u.name uploaded_by_name FROM employee_documents ed JOIN users u ON u.id=ed.uploaded_by WHERE ed.organization_id=? AND ed.membership_id=? ORDER BY ed.created_at DESC');$q->execute([$organizationId,$membershipId]);return ['employment'=>$employment,'assignments'=>$assignments,'notes'=>$notes,'onboarding'=>$onboarding,'snapshots'=>$snapshots,'documents'=>$q->fetchAll()];
    }

    private function snapshotEmployee(int $organizationId,int $membershipId,int $userId,string $type): void
    {
        $q=$this->db->prepare('SELECT m.*,u.name,u.email,sa.location_id,sa.department_id,sa.position_id,wp.employment_type,wp.expected_weekly_hours,er.starts_on,er.ends_on,er.employment_status FROM memberships m JOIN users u ON u.id=m.user_id LEFT JOIN staff_assignments sa ON sa.membership_id=m.id AND sa.is_primary=1 LEFT JOIN workforce_profiles wp ON wp.membership_id=m.id LEFT JOIN employment_records er ON er.membership_id=m.id WHERE m.id=? AND m.organization_id=?');$q->execute([$membershipId,$organizationId]);$profile=$q->fetch();if($profile)$this->db->prepare('INSERT INTO employee_profile_snapshots (organization_id,membership_id,snapshot_type,profile_json,created_by) VALUES (?,?,?,?,?)')->execute([$organizationId,$membershipId,$type,json_encode($profile),$userId]);
    }

    public function saveEmploymentRecord(int $organizationId,int $userId,array $input): void
    {
        $member=(int)($input['membership_id']??0);$q=$this->db->prepare('SELECT COUNT(*) FROM memberships WHERE id=? AND organization_id=?');$q->execute([$member,$organizationId]);if(!$q->fetchColumn())throw new InvalidArgumentException('Employee unavailable.');$status=in_array($input['employment_status']??'', ['preboarding','active','leave','ended'],true)?$input['employment_status']:'active';$start=!empty($input['starts_on'])?$input['starts_on']:null;$end=!empty($input['ends_on'])?$input['ends_on']:null;if($start&&$end&&$end<$start)throw new InvalidArgumentException('Employment ending date cannot precede the start date.');$this->snapshotEmployee($organizationId,$member,$userId,$status==='ended'?'offboarding':'assignment_change');$this->db->prepare('INSERT INTO employment_records (membership_id,organization_id,starts_on,ends_on,employment_status,separation_reason,updated_by) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE starts_on=VALUES(starts_on),ends_on=VALUES(ends_on),employment_status=VALUES(employment_status),separation_reason=VALUES(separation_reason),updated_by=VALUES(updated_by)')->execute([$member,$organizationId,$start,$end,$status,trim((string)($input['separation_reason']??''))?:null,$userId]);if($status==='ended')$this->db->prepare('UPDATE memberships SET status="inactive" WHERE id=? AND organization_id=? AND role<>"owner"')->execute([$member,$organizationId]);$this->audit($organizationId,$userId,'employment.'.$status,'membership',$member,[]);
    }

    public function addSecondaryAssignment(int $organizationId,int $userId,array $input): void
    {
        $member=(int)($input['membership_id']??0);$q=$this->db->prepare('SELECT COUNT(*) FROM memberships WHERE id=? AND organization_id=?');$q->execute([$member,$organizationId]);if(!$q->fetchColumn())throw new InvalidArgumentException('Employee unavailable.');$location=$this->ownedId('locations',$organizationId,!empty($input['location_id'])?(int)$input['location_id']:null);$department=$this->ownedId('departments',$organizationId,!empty($input['department_id'])?(int)$input['department_id']:null);$position=$this->ownedId('positions',$organizationId,!empty($input['position_id'])?(int)$input['position_id']:null);if(!$location&&!$department&&!$position)throw new InvalidArgumentException('Choose at least one secondary assignment resource.');$this->snapshotEmployee($organizationId,$member,$userId,'assignment_change');$this->db->prepare('INSERT INTO secondary_work_assignments (organization_id,membership_id,location_id,department_id,position_id,effective_from,effective_to,created_by) VALUES (?,?,?,?,?,?,?,?)')->execute([$organizationId,$member,$location,$department,$position,!empty($input['effective_from'])?$input['effective_from']:null,!empty($input['effective_to'])?$input['effective_to']:null,$userId]);
    }

    public function addManagerNote(int $organizationId,int $userId,int $membershipId,string $note): void
    {
        $note=trim($note);if($note==='')throw new InvalidArgumentException('Manager note cannot be empty.');$this->db->prepare('INSERT INTO employee_manager_notes (organization_id,membership_id,note_text,created_by) SELECT ?,?,?,? WHERE EXISTS (SELECT 1 FROM memberships WHERE id=? AND organization_id=?)')->execute([$organizationId,$membershipId,$note,$userId,$membershipId,$organizationId]);
    }

    public function addOnboardingItem(int $organizationId,int $userId,array $input): void
    {
        $member=(int)($input['membership_id']??0);$name=trim((string)($input['item_name']??''));if($name==='')throw new InvalidArgumentException('Checklist item is required.');$this->db->prepare('INSERT INTO employee_onboarding_items (organization_id,membership_id,item_name,due_on,created_by) SELECT ?,?,?,?,? WHERE EXISTS (SELECT 1 FROM memberships WHERE id=? AND organization_id=?)')->execute([$organizationId,$member,$name,!empty($input['due_on'])?$input['due_on']:null,$userId,$member,$organizationId]);
    }

    public function completeOnboardingItem(int $organizationId,int $userId,int $itemId): void
    {
        $q=$this->db->prepare('UPDATE employee_onboarding_items SET completed_by=IF(completed_at IS NULL,?,NULL),completed_at=IF(completed_at IS NULL,NOW(),NULL) WHERE id=? AND organization_id=?');$q->execute([$userId,$itemId,$organizationId]);if(!$q->rowCount())throw new InvalidArgumentException('Checklist item unavailable.');
    }

    public function addEmployeeDocument(int $organizationId,int $userId,int $membershipId,string $name,string $storageName,string $mime,int $size): void
    {
        $q=$this->db->prepare('INSERT INTO employee_documents (organization_id,membership_id,document_name,storage_name,mime_type,file_size,uploaded_by) SELECT ?,?,?,?,?,?,? WHERE EXISTS (SELECT 1 FROM memberships WHERE id=? AND organization_id=?)');$q->execute([$organizationId,$membershipId,$name,$storageName,$mime,$size,$userId,$membershipId,$organizationId]);if(!$q->rowCount())throw new InvalidArgumentException('Employee unavailable.');
    }

    public function employeeDocument(int $organizationId,int $documentId): ?array
    {
        $q=$this->db->prepare('SELECT * FROM employee_documents WHERE id=? AND organization_id=?');$q->execute([$documentId,$organizationId]);return $q->fetch()?:null;
    }

    private function insertNamed(string $table, int $organizationId, string $name, array $extra): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('A name is required.');
        }
        $columns = array_merge(['organization_id', 'name'], array_keys($extra));
        $values = array_merge([$organizationId, $name], array_values($extra));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->db->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')')->execute($values);
    }

    private function uniqueSlug(string $name): string
    {
        $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)) ?? '', '-') ?: 'organization';
        $slug = $base;
        $counter = 2;
        $statement = $this->db->prepare('SELECT COUNT(*) FROM organizations WHERE slug = ?');
        while (true) {
            $statement->execute([$slug]);
            if ((int)$statement->fetchColumn() === 0) return $slug;
            $slug = $base . '-' . $counter++;
        }
    }

    private function ownedId(string $table, int $organizationId, ?int $id): ?int
    {
        if (!$id) return null;
        if (!in_array($table, ['locations', 'departments', 'positions'], true)) {
            throw new LogicException('Invalid organization resource type.');
        }
        $statement = $this->db->prepare("SELECT id FROM {$table} WHERE id = ? AND organization_id = ? AND active = 1");
        $statement->execute([$id, $organizationId]);
        if (!$statement->fetchColumn()) {
            throw new InvalidArgumentException('One of the selected organization resources is unavailable.');
        }
        return $id;
    }

    private function audit(int $organizationId, int $userId, string $action, string $entityType, int $entityId, array $metadata): void
    {
        $statement = $this->db->prepare('INSERT INTO audit_logs (organization_id, user_id, action, entity_type, entity_id, metadata_json, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$organizationId, $userId, $action, $entityType, $entityId, json_encode($metadata), $_SERVER['REMOTE_ADDR'] ?? null]);
    }
}
