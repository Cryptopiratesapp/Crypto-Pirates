<?php
namespace app\models;

use app\components\ChanceProvider;
use PDO;

class ZoneEvent
{
	private $_db;
	private $_map = [];

	public function __construct($db)
	{
		$this->_db = $db;
	}
	public function load()
	{
		$sth = $this->_db->getPdo()->prepare('select * from zone_event');
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$key = $row['zone'] . $row['mode'];

			if (!array_key_exists($key, $this->_map)) {
				$this->_map[$key] = [];
			}
			$chance = intval($row['chance']);
			if (!array_key_exists($chance, $this->_map[$key])) {
				$this->_map[$key][$chance] = [(int) $row['event_type']];
			} else {
				$this->_map[$key][$chance][] = (int) $row['event_type'];
			}
		}
		$sth->closeCursor();
		
//		foreach(array_keys($this->_map) as $id) {
//			ksort($this->_map[$id]);
//		}
	}
	
	public function reload()
	{
		$this->_map = [];
		$this->load();
	}
	
	public function & getEvents($zone, $mode)
	{
		$key = $zone . $mode;
		if (!isset($this->_map[$key])) {
			$o = null;
			return $o;
		}

		return $this->_map[$key];
	}

	/**
	 * @param ChanceProvider $chanceProvider
	 */
	public function registerChances($chanceProvider)
	{
		foreach($this->_map as $chances) {
			$chanceProvider->setItems($chances);
		}
	}
}
