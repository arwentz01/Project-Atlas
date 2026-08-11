<?php

declare(strict_types=1);

final class App
{
    public static function url(string $path='/'): string
    {
        $base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        $base=$base==='/'?'':$base;
        $path='/'.ltrim($path,'/');
        return ($base?:'').($path==='/'?'/':$path);
    }

    public static function e(string $value): string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}

    public static function render(string $title,string $content,bool $shell=true): void
    {
        $user=Auth::user();
        ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=self::e($title)?> | Atlas</title><link rel="stylesheet" href="<?=self::e(self::url('/assets/app.css'))?>"></head><body class="<?= $shell?'app-body':'auth-body' ?>"><?php if($shell):?><div class="app"><aside class="sidebar"><a class="brand" href="<?=self::e(self::url('/'))?>"><span class="mark">A</span><span>ATLAS</span></a><nav><?php foreach(self::nav() as [$label,$path]):?><a href="<?=self::e(self::url($path))?>"><?=self::e($label)?></a><?php endforeach;?></nav><div class="sidebar-user"><strong><?=self::e((string)($user['display_name']??'Atlas User'))?></strong><span><?=self::e((string)($user['role']??'Member'))?></span><form method="post" action="<?=self::e(self::url('/logout'))?>"><?=Csrf::input()?><button>Sign out</button></form></div></aside><div class="workspace"><header class="topbar"><form action="<?=self::e(self::url('/search'))?>" method="get"><input name="q" placeholder="Search Atlas…"></form><span>Healthcare operations workspace</span></header><main><?= $content ?></main></div></div><?php else:?><?= $content ?><?php endif;?></body></html><?php
    }

    public static function nav(): array{return [['Home','/'],['Resources','/resources'],['Insurance','/insurance'],['Playbooks','/playbooks'],['Knowledge Base','/knowledge-base'],['Patient Resources','/patient-resources'],['Search','/search'],['My Workspace','/workspace']];}

    public static function cards(): array{return [
        ['Resources','Practical clinical and operational references','/resources','24'],
        ['Insurance','Payer requirements and documentation guidance','/insurance','5'],
        ['Playbooks','Step-by-step operational guidance','/playbooks','8'],
        ['Knowledge Base','Local policy, SOPs, and standards','/knowledge-base','12'],
    ];}
}
