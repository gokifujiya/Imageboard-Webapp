<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Project root
$root = realpath(__DIR__ . '/..');

// Prefer Composer autoload if present
$vendor = $root . '/vendor/autoload.php';
if (file_exists($vendor)) {
    require_once $vendor;
}

// Fallback simple autoloader (loads /Database/MySQLWrapper.php etc.)
if (!class_exists('Database\\MySQLWrapper')) {
    spl_autoload_extensions(".php");
    spl_autoload_register(function ($class) use ($root) {
        $file = $root . '/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

use Database\MySQLWrapper;

$mysqli = new MySQLWrapper();
$charset = $mysqli->get_charset();
if ($charset === null) throw new Exception('Charset could not be read');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$query = "SELECT * FROM test_users WHERE username = '$username' AND password = '$password';";
$result = $mysqli->query($query);
$userData = $result ? $result->fetch_assoc() : null;

if ($userData) {
    $login_username = $userData["username"];
    $login_email    = $userData["email"];
    $login_role     = $userData["role"];

    echo "ログイン成功<br/>";
    echo "こんにちは、{$login_username}<br/>";
    if ($login_role == 'admin') {
        echo "role: admin でログインしています。<br/>";
        echo "password: {$password}<br/>";
    }
} else {
    echo "ログイン失敗<br/>";
}
