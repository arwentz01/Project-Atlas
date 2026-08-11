<?php

declare(strict_types=1);

final class App
{
    public static function url(string $path='/'): string
    {
        $base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        $base=$base==='/'?'':$base;
        $parts=parse_url($path);
        $route='/'.ltrim((string)($parts['path']??'/'),'/');
        $url=($base?:'').($route==='/'?'/':$route);
        if(isset($parts['query']))$url.='?'.$parts['query'];
        return $url;
    }

    public static function e(string $value): string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}

    public static function render(string $title,string $content,bool $shell=true): void
    {
        $user=Auth::user();
        ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title><?=self::e($title)?> | Atlas</title><link rel="stylesheet" href="<?=self::e(self::url('/assets/app.css'))?>"></head><body class="<?= $shell?'app-body':'auth-body' ?>"><?php if($shell):?><div class="app"><aside class="sidebar"><a class="brand" href="<?=self::e(self::url('/'))?>"><span class="mark">A</span><span>ATLAS</span></a><span class="nav-label">Workspace</span><nav><?php foreach(self::nav() as [$label,$path]):?><a href="<?=self::e(self::url($path))?>"><?=self::e($label)?></a><?php endforeach;?></nav><div class="sidebar-user"><a class="user-link" href="<?=self::e(self::url('/profile'))?>"><span class="avatar"><?=self::e(strtoupper(substr((string)($user['display_name']??'A'),0,1)))?></span><span><strong><?=self::e((string)($user['display_name']??'Atlas User'))?></strong><small><?=self::e((string)($user['role']??'Member'))?></small></span></a><form method="post" action="<?=self::e(self::url('/logout'))?>"><?=Csrf::input()?><button>Sign out</button></form></div></aside><div class="workspace"><header class="topbar"><form action="<?=self::e(self::url('/search'))?>" method="get"><input name="q" placeholder="Search Atlas…" aria-label="Search Atlas"></form><div class="top-context"><span>Atlas Demo Health</span><a href="<?=self::e(self::url('/profile'))?>">Profile</a></div></header><main><?= $content ?></main></div></div><?php else:?><?= $content ?><?php endif;?></body></html><?php
    }

    public static function nav(): array{return [['Home','/'],['Resources','/resources'],['Insurance','/insurance'],['Playbooks','/playbooks'],['Knowledge Base','/knowledge-base'],['Patient Resources','/patient-resources'],['Search','/search'],['My Workspace','/workspace']];}
}
