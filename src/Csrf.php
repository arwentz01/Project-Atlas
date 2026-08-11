<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string{Auth::startSession();if(empty($_SESSION['atlas_csrf']))$_SESSION['atlas_csrf']=bin2hex(random_bytes(32));return (string)$_SESSION['atlas_csrf'];}
    public static function input(): string{return '<input type="hidden" name="csrf" value="'.htmlspecialchars(self::token(),ENT_QUOTES,'UTF-8').'">';}
    public static function verify(?string $token): void{Auth::startSession();$expected=(string)($_SESSION['atlas_csrf']??'');if($expected===''||$token===null||!hash_equals($expected,$token)){http_response_code(419);exit('Your session expired. Refresh and try again.');}}
}
