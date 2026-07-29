<?php
declare(strict_types=1);
define('ATLAS_PLATFORM_DIR',dirname(__DIR__).'/plugin/atlas-platform/');require ATLAS_PLATFORM_DIR.'src/Autoloader.php';Atlas\Platform\Autoloader::register();
function esc_html(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');} function __(string $value,string $domain=''):string{return $value;}
use Atlas\Platform\Resources\Presentation\StructuredContentRenderer;
function present_expect(bool $condition,string $message):void{if(!$condition){throw new RuntimeException($message);}echo "PASS: {$message}\n";}
$renderer=new StructuredContentRenderer();$html=$renderer->render(['blocks'=>[['type'=>'heading','level'=>1,'text'=>'Safe title'],['type'=>'paragraph','text'=>'<script>alert(1)</script>'],['type'=>'list','items'=>['One','<b>Two</b>']],['type'=>'callout','label'=>'Caution','text'=>'Stop and review']]]);
present_expect(str_contains($html,'<h2>Safe title</h2>'),'structured headings are bounded to accessible content levels');
present_expect(!str_contains($html,'<script>')&&str_contains($html,'&lt;script&gt;'),'structured body text is escaped');
present_expect(str_contains($html,'<li>One</li>')&&str_contains($html,'&lt;b&gt;Two&lt;/b&gt;'),'structured list items are escaped and rendered');
present_expect(str_contains($html,'atlas-resource-callout'),'structured callouts use an explicit component');
present_expect($renderer->render(['blocks'=>'invalid'])==='','malformed block collections render safely as empty content');
echo "All resource presentation tests passed.\n";
