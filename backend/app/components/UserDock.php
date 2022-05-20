<?php

namespace app\components;
use PDO;

class UserDock
{
	private $_pdo;
	private $_selectAllSth;
	private $_selectSth;
	private $_insertSth;
	
	public function __construct(PDO $pdo)
	{
		$this->_pdo = $pdo;
		$this->_selectAllSth = $pdo->prepare('select dock_id from user_dock where user_id=:uid');
		$this->_selectSth = $pdo->prepare('select dock_id from user_dock where user_id=:uid and dock_id=:dock_id');
		$this->_insertSth = $pdo->prepare('insert into user_dock values(now(), :uid, :dock_id)');
	}

	public function & getList($uid)
	{
		$sth = $this->_selectAllSth;
		$sth->bindValue(':uid', $uid);
		$sth->execute();
		$out = [];
		while ($data = $sth->fetch(PDO::FETCH_ASSOC)) {
			$out[] = (int) $data['dock_id'];
		}

		$sth->closeCursor();

		return $out;
	}

	public function exists($uid, $port_id)
	{
		$sth = $this->_selectSth;
		$sth->bindValue(':uid', $uid);
		$sth->bindValue(':dock_id', $port_id);
		$sth->execute();
		$data = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();

		return !empty($data);
	}		
	
	public function add($uid, $dock_id)
	{
		$sth = $this->_insertSth;
		$sth->bindValue(':uid', $uid);
		$sth->bindValue(':dock_id', $dock_id);

		return $sth->execute();
	}
}
