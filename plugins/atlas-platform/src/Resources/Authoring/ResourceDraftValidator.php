<?php
declare(strict_types=1);
namespace Atlas\Platform\Resources\Authoring;
use InvalidArgumentException;
final class ResourceDraftValidator
{
public function normalize(array$input):array
{
$text=static fn(mixed$v,int$max):string=>substr(trim(is_string($v)?$v:''),0,$max);$scope=$text($input['scope']??'',20);if(!in_array($scope,['platform','organization'],true)){throw new InvalidArgumentException('Resource scope must be platform or organization.');}
$slug=strtolower($text($input['slug']??'',191));if(preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)!==1){throw new InvalidArgumentException('Resource slug is invalid.');}$title=$text($input['title']??'',255);$type=$text($input['resource_type']??'',40);if($title===''||$type===''){throw new InvalidArgumentException('Resource title and type are required.');}
$blocks=$input['body']??null;if(!is_array($blocks)||count($blocks)>100){throw new InvalidArgumentException('Resource body must be an array of at most 100 blocks.');}$normalized=[];foreach($blocks as$block){if(!is_array($block)||!in_array($block['type']??'',['heading','paragraph','list','callout'],true)||!is_string($block['text']??null)){throw new InvalidArgumentException('Resource body contains an unsupported block.');}$normalized[]=['type'=>$block['type'],'text'=>substr(trim($block['text']),0,5000)];}
$source=is_array($input['source']??null)?$input['source']:[];$publisher=$text($source['publisher']??'',255);$sourceTitle=$text($source['title']??'',255);if($publisher===''||$sourceTitle===''){throw new InvalidArgumentException('Source publisher and title are required.');}$url=filter_var($text($source['url']??'',2000),FILTER_VALIDATE_URL)?:'';
$list=static function(mixed$value)use($text):array{$items=is_array($value)?$value:preg_split('/[,\\r\\n]+/',(string)$value);return array_values(array_unique(array_filter(array_map(static fn($item):string=>$text($item,100),$items?:[]))));};
$patientFacing=array_key_exists('patient_facing',$input)?filter_var($input['patient_facing'],FILTER_VALIDATE_BOOLEAN):$type==='patient_education';$internalOnly=array_key_exists('internal_only',$input)?filter_var($input['internal_only'],FILTER_VALIDATE_BOOLEAN):in_array($type,['payer_summary','clinical_skill'],true);if($patientFacing&&$internalOnly){throw new InvalidArgumentException('A resource cannot be both patient-facing and internal-only.');}
return['scope'=>$scope,'slug'=>$slug,'resource_type'=>$type,'title'=>$title,'summary'=>$text($input['summary']??'',2000),'body'=>$normalized,'change_summary'=>$text($input['change_summary']??'Initial draft',2000),'metadata'=>['patient_facing'=>$patientFacing,'internal_only'=>$internalOnly,'audiences'=>$list($input['audiences']??[]),'specialties'=>$list($input['specialties']??[]),'jurisdictions'=>$list($input['jurisdictions']??[]),'payers'=>$list($input['payers']??[]),'tags'=>$list($input['tags']??[])],'source'=>['publisher'=>$publisher,'title'=>$sourceTitle,'url'=>$url,'document_identifier'=>$text($source['document_identifier']??'',191),'effective_date'=>$text($source['effective_date']??'',10)],'citation'=>['page'=>$text($input['citation']['page']??'',40),'section'=>$text($input['citation']['section']??'',255)]];
}}
