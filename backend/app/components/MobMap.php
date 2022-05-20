<?php
namespace app\components;

use app\models\Mob;
use Exception;
use PDO;

class MobMap
{
	private $_ctx;
	private $_selectMobAllSth;
	private $_mobs = [];

	/** @param Context $ctx */
	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
		$this->_selectMobAllSth = $ctx->pdo->prepare('select * from mob');
		$this->_selectMobArtefactAllSth = $ctx->pdo->prepare('select * from mob_artefact');
	}
	
	public function load()
	{
		$this->_mobs = [];

		$res = $this->_selectMobAllSth->execute();
		if (!$res) {
			throw new Exception('Error loading mobs from db');
		}
		while ($data = $this->_selectMobAllSth->fetch(PDO::FETCH_ASSOC)) {
			$mob = new Mob($data);
			$this->_mobs[$mob->id] = $mob;
		}

		$this->_selectMobAllSth->closeCursor();
	
		return count($this->_mobs);
	}

	public function reload()
	{
		$this->_mobs = [];
		$this->load();
	}

	public function get($id)
	{
		return $this->_mobs[$id];
	}
	
	public function & getList()
	{
		return $this->_mobs;
	}

}
