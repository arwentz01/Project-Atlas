<?php
declare(strict_types=1);namespace Atlas\Platform\Workflows\Repositories;use Atlas\Platform\Workflows\Domain\Workflow;interface WorkflowRepository{public function findPublishedForContext(string$id,?string$organizationId):?Workflow;}
