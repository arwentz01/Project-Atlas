<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Authoring;
use RuntimeException;
final class WordPressResourceMetadataRepository implements ResourceMetadataRepository
{
public function __construct(private object$db){}
public function save(string$resourceId,array$metadata):void{$json=wp_json_encode($metadata);if(!is_string($json)||$this->db->replace($this->db->prefix.'atlas_resource_metadata',['resource_id'=>$resourceId,'metadata_json'=>$json,'updated_at'=>current_time('mysql',true)])===false){throw new RuntimeException('Resource metadata could not be saved.');}}
}
