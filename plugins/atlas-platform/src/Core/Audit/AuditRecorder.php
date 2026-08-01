<?php
declare(strict_types=1);namespace Atlas\Platform\Core\Audit;interface AuditRecorder{public function record(string$event,string$module,int$actorId,?string$organizationId,string$objectType,string$objectId,array$context=[]):void;}
