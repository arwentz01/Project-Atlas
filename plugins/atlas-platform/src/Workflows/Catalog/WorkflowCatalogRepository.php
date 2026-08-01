<?php
declare(strict_types=1);namespace Atlas\Platform\Workflows\Catalog;interface WorkflowCatalogRepository{/** @return list<WorkflowSummary> */public function publishedForContext(?string$organizationId,int$limit=50):array;}
