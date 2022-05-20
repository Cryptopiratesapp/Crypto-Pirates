<?php
error_reporting(E_ALL);

require '../../app/base/Request.php';
require '../../app/base/Response.php';
require '../../app/base/Security.php';
require '../../app/base/Db.php';
require '../../app/crypto/Keccak.php';
require '../../app/crypto/PointMathGMP.php';
require '../../app/crypto/SECp256k1.php';
require '../../app/crypto/Signature.php';

require('../../app/models/UserSession.php');
require('../../app/components/ShipHelper.php');
require('../../app/components/DbHelper.php');

$config = require('../../config.php');

use app\base\Request;
use app\base\Response;
use app\base\Db;
use app\models\UserSession;
use app\components\ShipHelper;
use app\base\Security;
use kornrunner\Keccak;

$action = null;
$db = new Db($config['db']);
$ses = new UserSession($db, $config['auth_cookie_name']);

if (Request::isPost()) {
	$action = Request::post('action');
} else {
	$action = Request::get('action');
}

$response = null;

if ($action) {
	if ($action == 'login') {
		$response = login($db, $ses);
	} else if ($action == 'check') {
		$response = check($db, $ses);
		//$response = login($ses);
	} else if ($action == 'logout') {
		$ses->logout();
	}
}

if (!$response) {
	Response::redirect('/');
}

header('Content-Type: text/plain; charset=UTF-8');
echo $response;

/**
 * @param type $db
 * @param UserSession $ses
 * @return string
 */
function login($db, $ses)
{
	$addr = substr(Request::post('addr'), 2);

	if (strlen($addr) != 40) {
		return 'FALSE';
	}

	$user = $ses->getUserByEthAddr($addr);

	$nonce = Security::getRandomString(8, 'Aa0');

	if (!$user) {
		$date = date('Y-m-d H:i:s');

		$sth = $db->getPdo()->prepare(
			'insert into user(date_db, date_upd, email, password, status, gold, username, eth_addr, nonce) values('
			. ':date, :date, :email, :pass, 0, 0, :username, :addr, :nonce)'
		);
		$sth->bindValue(':date', $date);
		$sth->bindValue(':email', $nonce . '@eth');
		$sth->bindValue(':pass', '000');
		$sth->bindValue(':username', $nonce);
		$sth->bindValue(':addr', hex2bin($addr));
		$sth->bindValue(':nonce', $nonce);
	
		if (!$sth->execute()) {
			die('sth execute error');
		}

		$uid = $db->getPdo()->lastInsertId();
		if (!ShipHelper::createShip($db, $uid)) {
			die('ship generation error');
		}
	} else {
		$sth = $db->getPdo()->prepare(
			'update user set nonce=:nonce where eth_addr=:addr'
		);
		$sth->bindValue(':addr', hex2bin($addr));
		$sth->bindValue(':nonce', $nonce);
	
		if (!$sth->execute()) {
			die('sth execute error');
		}
	}

	return $nonce;
}

/**
 * 
 * @param type $db
 * @param UserSession $ses
 * @return string
 */
function check($db, $ses)
{
	$addr = substr(Request::post('addr'), 2);
	
	if (strlen($addr) != 40) {
		return 'FALSE';
	}

	$user = $ses->getUserByEthAddr($addr);
	if (!$user) {
		return 'FALSE';
	}

	$signature = Request::post('msg');

	$result = personal_ecRecover('Crypto-Pirates-' . $user['nonce'], $signature);

	if ($result == $addr) {
		$ses->loginByUserModel($user);
		return 'OK';
	} else {
		return 'FALSE';
	}
}

function personal_ecRecover($msg, $signed) {
    $personal_prefix_msg = "\x19Ethereum Signed Message:\n". strlen($msg). $msg;
    $hex = keccak256($personal_prefix_msg);

	$rHex   = substr($signed, 2, 64);
    $sHex   = substr($signed, 66, 64);
    $vValue = hexdec(substr($signed, 130, 2));
    $messageHex       = substr($hex, 2);
    $messageGmp       = gmp_init('0x' . $messageHex);
    $r = $rHex;		//hex string without 0x
    $s = $sHex; 	//hex string without 0x
    $v = $vValue; 	//27 or 28

    $rGmp = gmp_init('0x' . $r);
    $sGmp = gmp_init('0x' . $s);

    if ($v != 27 && $v != 28) {
        $v += 27;
    }

    $recovery = $v - 27;
    if ($recovery !== 0 && $recovery !== 1) {
        throw new Exception('Invalid signature v value');
    }

    $publicKey = Signature::recoverPublicKey($rGmp, $sGmp, $messageGmp, $recovery);
    $publicKeyString = $publicKey['x'] . $publicKey['y'];

    return substr(keccak256(hex2bin($publicKeyString)), -40);
}

function keccak256($str)
{
    return '0x' . Keccak::hash($str, 256);
}

?>