<?php
namespace app\models;

use PDO;
use PDOStatement;
use app\base\Response;
use app\base\Security;

class UserSession
{
	private $_db;
	private $_cookie_name;
	private $_user;
	private $_ship;
	private $_key;
	private $_uid;

	public function __construct($db, $cookie_name)
	{
		$this->_db = $db;
		$this->_cookie_name = $cookie_name;
	}

	public function checkUserExists($email)
	{
		$sth = $this->_db->getPdo()->prepare('select * from user where email = :email');
		$sth->bindValue(':email', $email);
		
		return $this->_findOne($sth);
	}

	public function getUserBySid($sid)
	{
		$sth = $this->_db->getPdo()->prepare('select * from user where sid = :sid');
		$sth->bindValue(':sid', $sid);
		
		return $this->_get_user($sth);
	}

	public function getUserByEmail($email)
	{
		$sth = $this->_db->getPdo()->prepare('select * from user where email = :email');
		$sth->bindValue(':email', $email);
		
		return $this->_get_user($sth);
	}

	public function getUserByEthAddr($addr)
	{
		if ($addr[1] == 'x') {
			$addr = substr($addr, 2);
		}

		$sth = $this->_db->getPdo()->prepare('select * from user where eth_addr = :addr');
		$sth->bindValue(':addr', hex2bin($addr));
		
		return $this->_get_user($sth);
	}

	public function getUser($id = null)
	{
		if ($id) {
			$sth = $this->_db->getPdo()->prepare('select * from user where id = :id');
			$sth->bindValue(':id', $id);
			
			return $this->_get_user($sth);
		}

		return $this->_user;
	}
	
	public function loginByCredentials($email, $password)
	{
		$sth = $this->_db->getPdo()->prepare('select * from user where email=:email');
		$sth->bindValue(':email', $email);
		$this->_get_user($sth);

		if (!$this->_user) {
			die('ses: user not found');
			return false;
		}

		if ($this->_user['password'] !== Security::hashPassword($this->_user['salt'], $password)) {
			die('ses: pass is wrong');
			$this->_user = null;
			$this->_uid = null;
			$this->_ship = null;

			return false;
		}

		$this->_fill();

		return $this->_login_user();
	}

	public function loginByUserModel($user)
	{
		$this->_user = $user;
		$this->_fill();

		return $this->_login_user();
	}

	private function _login_user()
	{
		$sid = Security::getRandomString(32);

		if (!Response::setCookie($this->_cookie_name, $sid)) {
			die('*** no set cookie');
		}
		$res = $this->_db->getRedis()->hset($this->_key, 'sid', $sid);
		if ($res === false) {
			die('*** no set redis');
		}
		if (!$this->updateUserSid($sid)) {
			die('*** no update sid');
		}

		return true;
	}

	private function _get_user($sth)
	{
		$this->_user = $this->_findOne($sth);
		if ($this->_user) {
			$this->_fill();
		}

		return $this->_user;
	}
	
	private function _fill()
	{
		$this->_uid = $this->_user['id'];
		$this->_key = "u:{$this->_user['id']}";
	}

	public function updateUserSid($sid)
	{
		$sth = $this->_db->getPdo()->prepare('update user set date_upd = now(), sid=:sid where id=:id');
		$sth->bindValue(':id', $this->_uid);
		$sth->bindValue(':sid', $sid);

		if ($sth->execute()) {
			$this->_user['sid'] = $sid;

			return true;
		}
	
		return false;
	}

	public function syncAuthCode($auth)
	{
		$redis = $this->_db->getRedis();
		$val = $redis->get("auth:$auth");

		return
			$val !== false
			&& $redis->set("auth:$auth", $this->_user['id']) !== false;
	}

	
	public function checkIntegrity()
	{
		$sid = $this->_db->getRedis()->hget($this->_key, 'sid');

		return $sid === $this->_user['sid'];
	}

	public function getShip()
	{
		if (!$this->_ship) {
			$sth = $this->_db->getPdo()->prepare('select * from ship where user_id = :uid and active = 1');
			$sth->bindValue(':uid', $this->_uid);
			$this->_ship = $this->_findOne($sth);
		}

		return $this->_ship;
	}
	
	public function logout()
	{
		Response::deleteCookie($this->_cookie_name);
		if ($this->_user && $this->_user['email'] != 'test') {
			$this->updateUserSid(null);
			$this->_db->getRedis()->del($this->_key);
			$this->_user = null;
			$this->_key = null;
		}
	}
	
	private function _findOne(PDOStatement $sth)
	{
		if ($sth->execute()) {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$sth->closeCursor();

			return $row;
		}
	
		return null;
	}
}