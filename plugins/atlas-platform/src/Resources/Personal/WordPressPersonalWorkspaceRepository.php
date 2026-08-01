<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Personal;
final class WordPressPersonalWorkspaceRepository implements PersonalWorkspaceRepository
{
public function __construct(private object$db){}
public function saveResource(int$u,string$r):void{$this->db->replace($this->db->prefix.'atlas_saved_resources',['user_id'=>$u,'resource_id'=>$r,'created_at'=>current_time('mysql',true)]);}
public function removeResource(int$u,string$r):void{$this->db->delete($this->db->prefix.'atlas_saved_resources',['user_id'=>$u,'resource_id'=>$r]);}
public function saveSearch(int$u,string$n,array$c):void{$this->db->insert($this->db->prefix.'atlas_saved_searches',['id'=>wp_generate_uuid4(),'user_id'=>$u,'name'=>$n,'criteria_json'=>wp_json_encode($c),'created_at'=>current_time('mysql',true)]);}
public function dashboard(int$u,?string$o):array{$r=$this->db->prefix.'atlas_resources';$v=$this->db->prefix.'atlas_resource_versions';$s=$this->db->prefix.'atlas_saved_resources';$saved=$this->db->get_results($this->db->prepare("SELECT r.id,v.title,v.summary FROM `{$s}` s INNER JOIN `{$r}` r ON r.id=s.resource_id INNER JOIN `{$v}` v ON v.id=r.current_version_id WHERE s.user_id=%d AND r.archived_at IS NULL AND (r.scope IN ('platform','public') OR (r.scope='organization' AND r.organization_id=%s)) ORDER BY s.created_at DESC LIMIT 50",$u,$o??''),ARRAY_A);$searches=$this->db->get_results($this->db->prepare("SELECT * FROM `{$this->db->prefix}atlas_saved_searches` WHERE user_id=%d ORDER BY created_at DESC LIMIT 20",$u),ARRAY_A);return['saved'=>is_array($saved)?$saved:[],'searches'=>is_array($searches)?$searches:[]];}
}
