<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Packets;
interface PacketSnapshotRepository{public function create(string$packetId,?string$organizationId,int$actorUserId,string$status,array$snapshot):string;public function find(string$id,string$packetId):?array;/** @return list<array<string,mixed>> */public function listForPacket(string$packetId,int$limit=10):array;}
