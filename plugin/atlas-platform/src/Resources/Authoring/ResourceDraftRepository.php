<?php
declare(strict_types=1);namespace Atlas\Platform\Resources\Authoring;interface ResourceDraftRepository{public function create(string$key,string$fingerprint,array$draft,int$userId):array;}
