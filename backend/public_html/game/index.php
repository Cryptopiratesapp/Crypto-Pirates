<?php
require('../../app/base/Db.php');
require('../../app/base/Request.php');
require('../../app/base/Response.php');
require('../../app/base/Security.php');
require('../../app/models/UserSession.php');

use app\base\Db;
use app\base\Request;
use app\base\Response;
use app\models\UserSession;

$config = require('../../config.php');

$db = null;
$ses = null;
$user = null;
$cookie_name = $config['auth_cookie_name'];
$sid = Request::cookie($cookie_name);

if ($sid) {
	$db = new Db($config['db']);
	$ses = new UserSession($db, $cookie_name);
	$user = $ses->getUserBySid($sid);
}

if ($user) {
	if (!$ses->checkIntegrity()) {
		die('integrity error');
	}
	$ship = $ses->getShip();

	if (Request::isPost()) {
		$auth = Request::post('auth');
		header('Content-Type: text/plain; charset=UTF-8');
		if ($ses->syncAuthCode($auth)) {
			echo 'OK';
		} else {
			echo 'ERROR {"msg":"Integrity test fail for ' . $auth . '"}';
		}

		return;	
	}

	render('game', ['user' => $user, 'ship' => $ship, 'listener' => $config['listener']]);
} else {
	Response::redirect('/');
}

function render($view, $data = []) {
	require '../../app/views/' . $view . '.php';
}

?>