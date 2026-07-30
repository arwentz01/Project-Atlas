<?php
declare(strict_types=1);namespace Atlas\Platform\Resources\Editorial;interface EditorialQueueRepository{/** @return list<EditorialQueueItem> */public function findForContext(?string$organizationId,bool$includePlatform,int$limit=50):array;}
