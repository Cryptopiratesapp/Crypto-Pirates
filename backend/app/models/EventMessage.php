<?php
namespace app\models;

use app\components\ChanceProvider;
use PDO;

class EventMessage
{
	private $_map = [];
	private $_db = null;
	private $_null_msg = null;

	public function __construct($db)
	{
		$this->_db = $db;
	}

	public function load()
	{
		$sth = $this->_db->getPdo()->prepare('select * from event_message');
		$sth->execute();
		while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$lang = $row['lang'];
			if (!array_key_exists($lang, $this->_map)) {
				$this->_map[$lang] = [];
			}
			$map = & $this->_map[$lang];
			$id = $row['zone'] . '_' . $row['mode'] . '_' . $row['type'];
			if (!array_key_exists($id, $map)) {
				$map[$id] = [];
			}
			$chance = intval($row['chance']);
			if (!array_key_exists($chance, $map[$id])) {
				$map[$id][$chance] = [$row['content']];
			} else {
				$map[$id][$chance][] = $row['content'];
			}
		}

		$sth->closeCursor();
	}

	public function reload()
	{
		$this->_map = [];
		$this->load();
	}

	public function & getMessages($lang, $zone, $mode, $type)
	{
		$map = & $this->_map[$lang];

		if (!$mode) {
			$mode = 'none';
		}

		$id = $zone . '_' . $mode . '_' . $type;

		if (!isset($map[$id]) && $mode != 'none') {
			$id = $zone . '_none_' . $type;
			if (!isset($map[$id])) {
				return $this->_null_msg;
			}
		}

		return $map[$id];
	}

	/**
	 * @param ChanceProvider $chanceProvider
	 */
	public function registerChances($chanceProvider)
	{
		foreach($this->_map as $lang => & $map) {
			foreach($map as $chances) {
				$chanceProvider->setItemsPooled($chances);
			}
		}
	}
}
