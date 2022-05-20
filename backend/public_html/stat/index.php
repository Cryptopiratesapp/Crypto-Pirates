<?php
error_reporting(E_ALL);

require('../../app/base/Request.php');
require('../../app/base/Response.php');
require('../../app/base/Security.php');
require('../../app/base/Db.php');
require('../../app/components/DbHelper.php');
require('../../app/components/UserNftHelper.php');
require('../../app/models/UserSession.php');
$config = require('../../config.php');

use app\base\Db;
use app\base\Request;
use app\components\DbHelper;
use app\components\UserNftHelper;
use app\models\UserSession;

const MAX = 15;

$db = new Db($config['db']);

$cookie_name = $config['auth_cookie_name'];
$ses = new UserSession($db, $cookie_name);
$sid = Request::cookie($cookie_name);
$user = null;

if ($sid) {
	$user = $ses->getUserBySid($sid);
}

$view = Request::get('view');

$json = [];

if ($view == 'nft' && $user) {
	$json = get_nfts($db, $user);
} else {
	if (!in_array($view, ['pvp_wins', 'pve_wins', 'deaths', 'max_tokens', 'max_hops', 'total_hops'])) {
		$view = 'pvp_wins';
	}

	// little name hack
	if ($view == 'max_tokens') {
		$view = 'max_gold';
	}

	$sth = $db->getPdo()->prepare(
		"select user_id, title, $view as points from ship where active=1 order by $view desc, user_id limit " . MAX
	);

	$ships = DbHelper::getList($sth, 'user_id');
	$uids = implode(',', array_keys($ships));

	$sth = $db->getPdo()->prepare(
		"select id, username from user where id in ($uids)"
	);

	$users = DbHelper::getList($sth, 'id');

	$json = [
		'top' => []
	];
	$pos = 1;
	foreach($ships as $uid => $ship) {
		$out = [
			'pos' => (int) $pos, 
			'username' => $users[$uid]['username'],
			'shipname' => $ship['title'],
			'points' => (int) $ship['points']
		];
		if ($user && $uid == $user['id']) {
			$out['current'] = true;
		}

		$json['top'][] = $out;
		$pos++;
	}

	if ($user) {
		$uid = $user['id'];
		if (!isset($ships[$uid])) {
			$sth = $db->getPdo()->prepare(
				"select * from ship where user_id=$uid and active=1"
			);
			$ship = DbHelper::findOne($sth);
			$points = $ship[$view];

			$sth = $db->getPdo()->prepare(
				"select count(user_id) from ship where $view >= $points order by user_id"
			);

			$sth->execute(); 
			$number_of_rows = $sth->fetchColumn();

			$json['current'] = [
				'pos' => $number_of_rows,
				'username' => $user['username'],
				'shipname' => $ship['title'],
				'points' => $points
			];

			if ($number_of_rows > MAX + 1) {
				$sth = $db->getPdo()->prepare(
					"select user_id, title, $view as points from ship where active=1 and $view >= $points and user_id < $uid order by $view, user_id desc limit 1"
				);
				$ship = DbHelper::findOne($sth);

				$sth = $db->getPdo()->prepare(
					"select id, username from user where id = " . $ship['user_id']
				);
				$user = DbHelper::findOne($sth);

				$json['prev'] = [
					'pos' => $number_of_rows - 1,
					'username' => $user['username'],
					'shipname' => $ship['title'],
					'points' => $ship['points']
				];
			}

			$sth = $db->getPdo()->prepare(
				"select user_id, title, $view as points from ship where active=1 and $view < $points order by $view, user_id limit 1"
			);
			$ship = DbHelper::findOne($sth);

			if ($ship) {
				$sth = $db->getPdo()->prepare(
					"select id, username from user where id = " . $ship['user_id']
				);
				$user = DbHelper::findOne($sth);

				$json['next'] = [
					'pos' => $number_of_rows + 1,
					'username' => $user['username'],
					'shipname' => $ship['title'],
					'points' => $ship['points']
				];
			}
		}
	}
}

header('Content-type: text/json; charset=UTF-8');
echo json_encode($json, JSON_UNESCAPED_UNICODE);

function get_nfts($db, $user)
{
	$sth = $db->getPdo()->prepare('select * from ship where user_id=:uid');
	$sth->bindValue(':uid', $user['id']);
	$ship = DbHelper::findOne($sth);
	if (!$ship) {
		return null;
	}

	$cards = [];

	$userNfts = UserNftHelper::getActiveList($db, $user['id']);
	
	foreach($userNfts as $unft) {
		$cards[] = [
			//'cardSprite' => 'ntf' . $unft['nft_id'],
			'level' => (int) $unft['level'],
			'id' => (int) $unft['nft_id'],
			'stats' => [
				'hp' => (int) $unft['hp'],
				'def' => (int) $unft['def'],
				'atk' => (int) $unft['atk'],
				'acc' => (int) $unft['acc'],
				'man' => (int) $unft['man'],
				'spd' => (int) $unft['spd'],
				'wght' => 1
			]
		];
	}
	$json = [
		'playerStats' => [
			'hp' => (int) $ship['max_hp'],
		    'def' => (int) $ship['max_def'],
			'atk' => (int) $ship['max_atk'],
			'acc' => (int) $ship['acc'],
			'man' => (int) $ship['man'],
			'spd' => (int) $ship['spd'],
			'wght' => 1
		],
		'cards' => $cards
	];
	
	return $json;
}
