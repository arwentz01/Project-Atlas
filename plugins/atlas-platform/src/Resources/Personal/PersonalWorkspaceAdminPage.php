<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Personal;
use Atlas\Platform\Organizations\Services\CurrentOrganizationResolver;
final class PersonalWorkspaceAdminPage
{
public function __construct(private PersonalWorkspaceRepository$workspace,private CurrentOrganizationResolver$organizations){}
public function register():void{add_submenu_page('atlas',__('My Atlas','atlas-platform'),__('My Atlas','atlas-platform'),'atlas_access','atlas-personal',[$this,'render']);}
public function saveResource():void{$this->authorize('atlas_save_resource');$this->workspace->saveResource(get_current_user_id(),sanitize_text_field(wp_unslash((string)($_POST['resource_id']??''))));$this->redirect();}
public function removeResource():void{$this->authorize('atlas_remove_saved_resource');$this->workspace->removeResource(get_current_user_id(),sanitize_text_field(wp_unslash((string)($_POST['resource_id']??''))));$this->redirect();}
public function saveSearch():void{$this->authorize('atlas_save_search');$name=substr(sanitize_text_field(wp_unslash((string)($_POST['name']??''))),0,120);if($name!==''){$this->workspace->saveSearch(get_current_user_id(),$name,['query'=>sanitize_text_field(wp_unslash((string)($_POST['query']??''))),'type'=>sanitize_key(wp_unslash((string)($_POST['type']??'')))]);}$this->redirect();}
public function render():void{$org=$this->organizations->resolveForUser(get_current_user_id());$dashboard=$this->workspace->dashboard(get_current_user_id(),$org?->id);require ATLAS_PLATFORM_DIR.'templates/resources/personal.php';}
private function authorize(string$n):void{if(!current_user_can('atlas_access')){wp_die(esc_html__('Not allowed.','atlas-platform'),'', ['response'=>403]);}check_admin_referer($n);}
private function redirect():never{wp_safe_redirect(admin_url('admin.php?page=atlas-personal'));exit;}
}
