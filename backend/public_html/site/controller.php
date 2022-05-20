<?php
error_reporting(E_ALL);

require('../../app/base/Request.php');
require('../../app/base/Response.php');
require('../../app/base/Security.php');
require('../../app/base/Db.php');
require('../../app/components/DbHelper.php');
require('../../app/components/ShipHelper.php');
require('../../app/models/UserSession.php');
$config = require('../../config.php');

use app\base\Db;
use app\base\Request;
use app\base\Response;
use app\base\Security;
use app\components\DbHelper;
use app\components\ShipHelper;
use app\models\UserSession;

$action = null;
$db = new Db($config['db']);
$ses = new UserSession($db, $config['auth_cookie_name']);

if (Request::isPost()) {
	$action = Request::post('action');
} else {
	$action = Request::get('action');
}

if ($action) {
	if ($action == 'register') {
		register($db, $ses);
	} else if ($action == 'login') {
		login($ses);
	} else if ($action == 'logout') {
		$ses->logout();
	}
}

Response::redirect('/');

function login($ses, $email = null, $password = null)
{
	if (!$email) {
		$email = Request::post('email');
		$password = Request::post('password');
	}

	if (!$ses->loginByCredentials($email, $password)) {
		die('user not found');
	}
}

function register($db, $ses)
{
	$email = Request::post('email');
	$password = Request::post('password');

	if (!$email || !$password) {
		return false;
	}

	if ($ses->checkUserExists($email)) {
		die('already registered.');
	}
	
	$date = date('Y-m-d H:i:s');
	$salt = Security::getRandomString(16);
	$hash = Security::hashPassword($salt, $password);

	$sth = $db->getPdo()->prepare('select * from protoship where level=1');
	$proto = DbHelper::findOne($sth);
	if (empty($proto)) {
		die('protoship lv.1 not found');
	}

	$sth = $db->getPdo()->prepare(
		'insert into user(date_db, date_upd, status, gold, email, username, password, salt) values('
		. ':date, :date, 1, 100, :login, :login, :hash, :salt)'
	);
	$sth->bindValue(':date', $date);
	$sth->bindValue(':login', $email);
	$sth->bindValue(':hash', $hash);
	$sth->bindValue(':salt', $salt);
	
	if (!$sth->execute()) {
		die('sth execute error');
	}
	
	$uid = $db->getPDO()->lastInsertId();

	if (!ShipHelper::createShip($db, $uid)) {
		die('ship generation error');
	}

	login($ses, $email, $password);
}
?>