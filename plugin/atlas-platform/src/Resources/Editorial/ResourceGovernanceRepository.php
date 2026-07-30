<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Editorial;
interface ResourceGovernanceRepository
{
public function history(string$resourceId,?string$organizationId,bool$allowPlatform):array;
public function revise(string$versionId,?string$organizationId,bool$allowPlatform,int$actorId):?string;
public function archive(string$resourceId,?string$organizationId,bool$allowPlatform):bool;
public function assign(string$versionId,int$reviewerId,?string$dueAt,?string$organizationId,bool$allowPlatform):bool;
public function addNote(string$versionId,int$authorId,string$type,string$text,?string$organizationId,bool$allowPlatform):bool;
public function workspace(string$versionId,?string$organizationId,bool$allowPlatform):array;
}
