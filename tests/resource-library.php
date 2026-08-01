<?php

declare(strict_types=1);

define('ATLAS_PLATFORM_DIR', dirname(__DIR__) . '/plugins/atlas-platform/');
define('ABSPATH', __DIR__ . '/');
require ATLAS_PLATFORM_DIR . 'src/Autoloader.php';
Atlas\Platform\Autoloader::register();

use Atlas\Platform\Resources\Search\SearchCriteria;
use Atlas\Platform\Resources\Search\SearchPage;
use Atlas\Platform\Resources\Search\SearchResult;

function esc_html(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function esc_attr(string $v):string{return esc_html($v);} function esc_url(string $v):string{return esc_html($v);}
function esc_html__(string $v,string $d=''):string{return esc_html($v);} function esc_attr__(string $v,string $d=''):string{return esc_attr($v);}
function __(string $v,string $d=''):string{return $v;} function _n(string $one,string $many,int $n,string $d=''):string{return$n===1?$one:$many;}
function admin_url(string $path=''):string{return'https://example.test/wp-admin/'.ltrim($path,'/');}
function add_query_arg(array $args,string $url):string{return$url.'&'.http_build_query(array_filter($args,static fn($v)=>$v!==null));}
function selected(?string $value,string $current):void{if($value===$current){echo' selected="selected"';}}

$criteria=SearchCriteria::normalize('<unsafe>',null,1,20);
$results=new SearchPage([new SearchResult('550e8400-e29b-41d4-a716-446655440000','<script>Unsafe title</script>','Summary <unsafe>','clinical_skill','organization','published',null,null,'Publisher <unsafe>','Source <unsafe>')],1,20,false);
$types=['clinical_skill'];$error='';$baseUrl='';
ob_start();require ATLAS_PLATFORM_DIR.'templates/resources/library.php';$html=(string)ob_get_clean();
if(str_contains($html,'<script>')||!str_contains($html,'&lt;script&gt;Unsafe title&lt;/script&gt;')){throw new RuntimeException('Resource library did not escape rendered data.');}
if(!str_contains($html,'page=atlas-resource')||!str_contains($html,'550e8400-e29b-41d4-a716-446655440000')){throw new RuntimeException('Resource library did not render an authorized detail destination.');}
echo "PASS: resource library escapes content and renders detail destinations\nAll resource library tests passed.\n";
