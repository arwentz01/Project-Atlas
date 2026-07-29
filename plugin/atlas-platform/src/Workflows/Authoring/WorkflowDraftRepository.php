<?php
declare(strict_types=1);namespace Atlas\Platform\Workflows\Authoring;interface WorkflowDraftRepository{public function create(string$key,string$fingerprint,array$draft,int$userId):array;}
