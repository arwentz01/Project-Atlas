<?php

declare(strict_types=1);

final class Router
{
    private array $routes=[];

    public function get(string $path, callable $handler, bool $auth=true): void{$this->routes[]=['GET',$this->normalize($path),$handler,$auth];}
    public function post(string $path, callable $handler, bool $auth=true): void{$this->routes[]=['POST',$this->normalize($path),$handler,$auth];}

    public function dispatch(): void
    {
        $method=strtoupper($_SERVER['REQUEST_METHOD']??'GET');
        $path=$this->requestPath();
        foreach($this->routes as [$verb,$route,$handler,$needsAuth]){
            if($verb!==$method||$route!==$path)continue;
            if($needsAuth&&!Auth::check()){header('Location: '.App::url('/login'));exit;}
            $handler();return;
        }
        http_response_code(404);App::render('Page not found','<section class="empty"><h1>Page not found</h1><p>Atlas does not have a route for this address.</p></section>');
    }

    private function requestPath(): string
    {
        $path=(string)(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)??'/');
        $base=rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        if($base!==''&&$base!=='/'&&str_starts_with($path,$base))$path=substr($path,strlen($base));
        return $this->normalize($path);
    }

    private function normalize(string $path): string{$path='/'.trim($path,'/');return $path==='/'?'/':rtrim($path,'/');}
}
