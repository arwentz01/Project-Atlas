<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Packets;
use RuntimeException;
final class WordPressPacketSnapshotRepository implements PacketSnapshotRepository{
public function __construct(private object$db){}
public function create(string$packetId,?string$organizationId,int$actorUserId,string$status,array$snapshot):string{$id=wp_generate_uuid4();$json=wp_json_encode($snapshot);if(!is_string($json)){throw new RuntimeException('Packet snapshot could not be encoded.');}$ok=$this->db->insert($this->db->prefix.'atlas_packet_snapshots',['id'=>$id,'packet_id'=>$packetId,'organization_id'=>$organizationId,'actor_user_id'=>$actorUserId,'packet_status'=>$status,'snapshot_json'=>$json,'created_at'=>gmdate('Y-m-d H:i:s')],['%s','%s','%s','%d','%s','%s','%s']);if($ok===false){throw new RuntimeException('Packet snapshot could not be saved.');}return$id;}
public function find(string$id,string$packetId):?array{$row=$this->db->get_row($this->db->prepare("SELECT * FROM `{$this->db->prefix}atlas_packet_snapshots` WHERE id=%s AND packet_id=%s LIMIT 1",$id,$packetId),ARRAY_A);if(!is_array($row)){return null;}$decoded=json_decode((string)$row['snapshot_json'],true);$row['snapshot']=is_array($decoded)?$decoded:[];unset($row['snapshot_json']);return$row;}
public function listForPacket(string$packetId,int$limit=10):array{$limit=max(1,min(50,$limit));$rows=$this->db->get_results($this->db->prepare("SELECT * FROM `{$this->db->prefix}atlas_packet_snapshots` WHERE packet_id=%s ORDER BY created_at DESC LIMIT %d",$packetId,$limit),ARRAY_A);return is_array($rows)?array_map(static function(array$r):array{$decoded=json_decode((string)$r['snapshot_json'],true);$r['snapshot']=is_array($decoded)?$decoded:[];unset($r['snapshot_json']);return$r;},$rows):[];}
}
