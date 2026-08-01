<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Personal;
interface PersonalWorkspaceRepository
{
public function saveResource(int$userId,string$resourceId):void;
public function removeResource(int$userId,string$resourceId):void;
public function saveSearch(int$userId,string$name,array$criteria):void;
public function dashboard(int$userId,?string$organizationId):array;
}
