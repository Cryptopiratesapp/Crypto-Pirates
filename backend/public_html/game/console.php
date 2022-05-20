<?php
require '../../app/base/Request.php';
require '../../app/base/Response.php';
require '../../app/base/Security.php';
require '../../app/base/Db.php';
require '../../app/models/UserSession.php';
require '../../app/models/Console.php';

use app\base\Db;
use app\base\Request;
use app\models\Console;
use app\models\UserSession;

$config = require('../../config.php');

$db = null;
$redis = null;
$user = null;
$ship = null;
$ses = null;
$cookie_name = $config['auth_cookie_name'];
$sid = Request::cookie($cookie_name);
$response = null;

if ($sid) {
	$db = new Db($config['db']);
	$ses = new UserSession($db, $cookie_name);
	$user = $ses->getUserBySid($sid);
	
	if ($user && !$ses->checkIntegrity()) {
		$ses->logout();
		$user = null;
		$response = 'ERR 0';
	}
}

if ($user['dev']) {
	$msg = Request::post('cmd');
	$console = new Console($db);
	$out = $console->parse($msg);
	if ($out) {
		$redis = $db->getRedis();
		$key = 'u:' . $user['id'];
		$redis->hset($key, 'request', '~ ' . implode(' ', $out));
		$response = 'OK';
	} else {
		$response = 'ERR 1';
	}
} else {
	$response = 'ERR 2';
}

echo $response;
