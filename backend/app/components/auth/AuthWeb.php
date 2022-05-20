<?php
namespace app\components\auth;

use app\base\Security;

class AuthWeb
{
	private $_redis;
	public $uid;
	public $output;
	
	public function __construct($db)
	{
		$this->_redis = $db->getRedis();
	}

	function process(& $msg)
	{
		$this->uid = null;
		$this->output = null;
		$r = $this->_redis;

		if ($msg == 'YARR') {
			$code = Security::getRandomString(16, '0aA');
			$r->set("auth:$code", 0);
			$this->output = "YARR $code";
			
			return true;
		}

		if (substr($msg, 0, 6) === 'START ') {
			$authkey = 'auth:' . substr($msg, 6);
			$uid = $r->get($authkey);

			if ($uid) {
				$r->del($authkey);
				$this->uid = $uid;
				$uidkey = "u:$uid";

				return !empty($r->hget($uidkey, 'sid'));
			}
		}

		return false;
	}

}
