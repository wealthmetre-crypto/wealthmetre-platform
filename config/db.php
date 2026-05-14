<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'wealthmetre');
define('DB_USER', 'wm_user');
define('DB_PASS', 'Chukali1234');
define('DB_CHARSET', 'utf8mb4');

if (!function_exists('getDB')) {
    function getDB(): PDO {
        static $pdo = null;
        if ($pdo === null) {
            $pdo = new PDO(
                'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
            );
        }
        return $pdo;
    }
}
if (!function_exists('sanitize')) {
    function sanitize(string $v, int $max=255): string {
        return mb_substr(htmlspecialchars(trim($v),ENT_QUOTES,'UTF-8'),0,$max);
    }
}
if (!function_exists('intSafe')) {
    function intSafe($v): int { return (int)preg_replace('/\D/','',(string)$v); }
}

if (!function_exists('jsonOk')) {
    function jsonOk(array $d=[]): void {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['status'=>'ok'],$d),JSON_UNESCAPED_UNICODE);
        exit;
    }
}
if (!function_exists('jsonErr')) {
    function jsonErr(string $m, int $c=400): void {
        http_response_code($c);
        header('Content-Type: application/json');
        echo json_encode(['status'=>'error','message'=>$m]);
        exit;
    }
}
