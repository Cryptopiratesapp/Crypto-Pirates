<?php
require('../app/base/Request.php');
require('../app/base/Response.php');
require('../app/base/Security.php');
require('../app/base/Db.php');
require('../app/models/UserSession.php');

use app\base\Request;
use app\models\UserSession;
use app\base\Db;

$config = require('../config.php');

$db = null;
$redis = null;
$user = null;
$ship = null;
$ses = null;
$cookie_name = $config['auth_cookie_name'];
$sid = Request::cookie($cookie_name);

if ($sid) {
	$db = new Db($config['db']);
	$ses = new UserSession($db, $cookie_name);
	$user = $ses->getUserBySid($sid);
	
	if ($user && !$ses->checkIntegrity()) {
		$ses->logout();
		$user = null;
	}
}

if ($user) {
	$ship = $ses->getShip();
	render('start', ['user' => $user, 'ship' => $ship]);
} else {
	render('loginform');
}

function render($view, $data = [])
{
	$view = '../app/views/' . $view . '.php';
	require('../app/views/layout.php');
}

?>