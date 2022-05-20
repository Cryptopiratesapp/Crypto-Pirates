<?php
require 'base/Db.php';
require 'base/Security.php';
require 'base/socket/SocketServer.php';
require 'base/socket/SocketServerError.php';
require 'base/socket/Buffer.php';
require 'base/socket/IOBuffer.php';
require 'base/socket/DefaultEncoder.php';
require 'base/socket/WSEncoder.php';
require 'components/auth/AuthWeb.php';
require 'components/auth/AuthMobile.php';

$config = require '../config.php';

use app\base\Db;
use app\base\socket\SocketServer;
use app\base\socket\DefaultEncoder;
use app\base\socket\WSEncoder;
use app\components\auth\AuthWeb;
use app\components\auth\AuthMobile;

$db = new Db($config['db']);

$pingSth = $db->getPdo()->prepare('select 1');
$pingTimeout = 300;
$pingTime = 0;

$redis = $db->getRedis();
$redis->del('responses');

$keys = $redis->keys('auth:*');
foreach($keys as $key) {
	$redis->del($key);
}

$encoders = [
	new WSEncoder(),
	new DefaultEncoder('YARR')
];

$authMobile = new AuthMobile($db);
$authWeb = new AuthWeb($db);

$server = new SocketServer();

$listener_uri = $config['listener'];

$pos = strpos($listener_uri, '://');
if ($pos === false) {
	$pos = 0;
}
list($host, $port) = explode(':', substr($listener_uri, $pos + 3));

echo "starting $host:$port\n";

$redis->set('listener_updated', time());

$result = $server->start($host, $port);

if ($result !== true) {
    echo "bad result: $result\n";
    die();
}

$socketmap = [];

while (true) {
	$redis->set('listener', 0);
	$res = $server->update();

	$t = time();
	$redis->set('listener_updated', $t);

	if ($pingTime < $t) {
		$pingTime = $t + $pingTimeout;
		echo "pinging mysql...\n";
		$pingSth->execute();
		$pingSth->closeCursor();
	}

	if (!$res) {
		continue;
	}

	$buffers = $server->getReadableBuffers();
	foreach ($buffers as $name => $buf) {
		/* no encoder: buf was not initialized */
		if (!$buf->encoder) {
			detectEncoder($buf, $encoders);
			if (!$buf->encoder) {
				echo "*** no encoder detected for $name\n";
				$server->closeConnection($name);
			}
			/* if encoder sent something back, skip further processing */
			if ($buf->isWritable()) {
				continue;
			}
		}

		/* not mapped to socketmap means not authenticated */
		if (!array_key_exists($name, $socketmap)) {
			if (!process_auth($buf, $name)) {
				$server->closeConnection($name);
			}

			continue;
		}

		process_command($socketmap[$name], trim($buf->read()));
		//$buf->clear();
	}

	$response_cnt = 0;
	while ($data = $redis->lpop('responses')) {
		$p = strpos($data, ' ');
		$uid = substr($data, 0, $p);
		$key = "u:$uid";
		$name = $redis->hget($key, 'sock');
		if ($name !== false) {
			if (!array_key_exists($name, $server->buffers)) {
				echo "response write: name exists but write buffer does not exist\n";
				unmap_socket($uid, $name);
			} else {
				$response_cnt++;
				$buf = $server->buffers[$name];
				$buf->write(substr($data, $p + 1));
			}
		}
	}

	$redis->set('listener_response_cnt', $response_cnt);

	usleep(100);
}

function detectEncoder($buf, & $encoders)
{
	$text = $buf->readRaw();

	foreach($encoders as $encoder) {
		$out = $encoder->detect($text);
		if ($out !== false) {
			$buf->encoder = $encoder;
			if ($out) {
				$buf->writeRaw($out);
			}
	
			break;
		}
	}
}

function process_auth($buf, $name)
{
	global $redis;
	global $socketmap;
	global $authWeb;
	global $authMobile;

	$auth = null;
	if ($buf->encoder instanceof WSEncoder) {
		$auth = $authWeb;
	} else {
		$auth = $authMobile;
	}
	
	$msg = trim($buf->read());
	$res = $auth->process($msg);
	
	if ($res !== false) {
		if ($auth->uid) {
			$uid = $auth->uid;
			remove_extra_sockets($uid);
	
			$key = "u:$uid";
			$redis->hdel($key, 'ready');

			$res = $redis->hset($key, 'sock', $name);
			if ($res !== false) {
				echo "*** map $uid -> $name\n";
				$socketmap[$name] = $uid;
				$buf->write('OK');
				
				return true;
			}
		}

		if ($auth->output) {
			$buf->write($auth->output);
			$auth->output = null;

			return true;
		}

		return $res;
	}

	return false;
}

function unmap_socket($uid, $name)
{
	global $redis;
	global $socketmap;

	echo "*** unmap $uid -> $name\n";

	$key = "u:$uid";
	$redis->hdel($key, 'sock');
	$redis->hdel($key, 'ready');
	$redis->hdel($key, 'request');

	unset($socketmap[$name]);
}

function remove_extra_sockets($uid)
{
	global $socketmap;
	global $server;

	$found = [];
	foreach($socketmap as $name => $used_uid) {
		if ($used_uid == $uid) {
			$found[] = $name;
		}
	}

	foreach($found as $name) {
		unmap_socket($uid, $name);
		$server->closeConnection($name);
	}
}

function process_command($uid, $msg)
{
	global $redis;

	if ($msg == 'STATE') {
		echo "state request pushed for uid=$uid\n";
		/** @todo make sure only one state request per uid is pushed */
		$redis->rpush('state_requests', $uid);
	} else {
		echo "process command: $msg\n";
		$key = "u:$uid";
		$redis->hset($key, 'request', $msg);
	}
}
