<?php
namespace app\base;
use Redis;
use PDO;

class Db
{
	private $_pdo = null;
	private $_redis = null;
	private $_config = null;

	public function __construct($dbconfig)
	{
		$this->_config = $dbconfig;
	}

	public function getRedis()
	{
		if (!$this->_redis) {
			$this->_redis = new Redis();
			if (!$this->_redis->connect(
				$this->_config['redis']['host'],
				$this->_config['redis']['port'])
			) {
				die('no redis');
			}
		}
		return $this->_redis;
	}

	public function getPdo()
	{
		if (!$this->_pdo) {
			$this->_pdo = new PDO($this->_config['pdo']['dsn']);
			$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		
		return $this->_pdo;
	}
}
