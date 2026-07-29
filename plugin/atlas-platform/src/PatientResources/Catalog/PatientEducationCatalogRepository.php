<?php
declare(strict_types=1);namespace Atlas\Platform\PatientResources\Catalog;interface PatientEducationCatalogRepository{/** @return list<PatientEducationItem> */public function publishedForContext(string$organizationId,int$limit=50):array;}
