<?php
declare(strict_types=1);

namespace Atlas\Platform\Resources\Editorial;

final class EditorialTransitionPolicy
{
    private const TRANSITIONS=['draft'=>['in_review'],'in_review'=>['draft','approved'],'approved'=>['draft','published'],'published'=>['review_due','superseded','archived'],'review_due'=>['in_review','superseded','archived'],'superseded'=>['archived'],'archived'=>[]];
    public function allows(string $from,string $to):bool{return in_array($to,self::TRANSITIONS[$from]??[],true);}
}
