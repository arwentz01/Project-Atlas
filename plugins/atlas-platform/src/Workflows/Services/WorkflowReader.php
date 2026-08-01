<?php
declare(strict_types=1);namespace Atlas\Platform\Workflows\Services;use Atlas\Platform\Workflows\Domain\Workflow;use Atlas\Platform\Workflows\Repositories\WorkflowRepository;final class WorkflowReader{public function __construct(private WorkflowRepository$repo){}public function find(string$id,?string$org):?Workflow{return$this->repo->findPublishedForContext($id,$org);}}
