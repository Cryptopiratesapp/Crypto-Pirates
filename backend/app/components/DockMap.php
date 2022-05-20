<?php
namespace app\components;

use app\models\Dock;
use Exception;
use PDO;

class DockMap
{
	private $_ctx;
	private $_selectAllSth;
	private $_docks = [];
	private $_dockIds = [];

	/** @param Context $ctx */
	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
		$this->_selectAllSth = $ctx->pdo->prepare('select * from dock where hop is not null order by hop');
	}
	
	public function load()
	{
		$this->_docks = [];

		$res = $this->_selectAllSth->execute();
		if (!$res) {
			throw new Exception('Error loading docks from db');
		}
		while ($data = $this->_selectAllSth->fetch(PDO::FETCH_ASSOC)) {
			$dock = new Dock($data);
			$this->_docks[$dock->hop] = $dock;
			$this->_dockIds[$dock->id] = $dock;
		}

		$this->_selectAllSth->closeCursor();
	
		if (!isset($this->_docks[0])) {
			throw new Exception('Dock at hop 0 does not exist');
		}

		return count($this->_docks);
	}

	public function reload()
	{
		$this->_docks = [];
		$this->_dockIds = [];
		$this->load();
	}

	public function exists($hop)
	{
		return isset($this->_docks[$hop]);
	}
	
	public function get($hop)
	{
		return $this->_docks[$hop];
	}

	public function getById($id)
	{
		return $this->_dockIds[$id];
	}

	public function & getList()
	{
		return $this->_docks;
	}
}
