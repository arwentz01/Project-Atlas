<?php

declare(strict_types=1);

final class Auth
{
    private const USER_KEY='atlas_user_id';

    public static function startSession(): void
    {
        if(session_status()===PHP_SESSION_ACTIVE)return;
        $secure=!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off';
        session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
        session_start();
    }

    public static function check(): bool{return self::userId()!==null;}
    public static function userId(): ?int{self::startSession();$id=(int)($_SESSION[self::USER_KEY]??0);return $id>0?$id:null;}

    public static function user(): ?array
    {
        $id=self::userId();if(!$id)return null;
        try{$db=Database::connect();$s=$db->prepare('SELECT id,email,display_name,role,active FROM users WHERE id=:id AND active=1 LIMIT 1');$s->execute(['id'=>$id]);$user=$s->fetch();if(!$user){self::logout();return null;}return $user;}catch(Throwable){return null;}
    }

    public static function attempt(string $email,string $password): bool
    {
        $email=mb_strtolower(trim($email));if($email===''||$password==='')return false;
        $db=Database::connect();$s=$db->prepare('SELECT id,password_hash,active FROM users WHERE LOWER(email)=:email LIMIT 1');$s->execute(['email'=>$email]);$row=$s->fetch();
        if(!$row||(int)$row['active']!==1||!password_verify($password,(string)$row['password_hash']))return false;
        self::startSession();session_regenerate_id(true);$_SESSION[self::USER_KEY]=(int)$row['id'];$_SESSION['atlas_authenticated_at']=time();return true;
    }

    public static function logout(): void{self::startSession();unset($_SESSION[self::USER_KEY],$_SESSION['atlas_authenticated_at']);session_regenerate_id(true);}
}
