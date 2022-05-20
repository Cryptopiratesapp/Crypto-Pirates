<?php
namespace app\models;

class Console
{
	const SET = 'set';
	const HP = 'hp';
	const DEF = 'def';
	const RES = 'res';
	const HOP = 'hop';
	const GOLD = 'gold';
	const FOE = 'foe';
	const DOCK = 'dock';
	const EVT = 'evt';
	const SPAWN = 'spawn';
	const NFT = 'nft';
	const ADD = 'add';
	const DEL = 'del';

	private $_db = null;

	private $_map = [
		self::SET => [
			self::HP => 1, self::DEF => 1, self::RES => 1, self::GOLD => 1
		],
		self::DOCK => 1,
		self::HOP => 1,
		self::EVT => 1,
		self::SPAWN => 1,
		self::FOE => [self::SET => true],
		self::NFT => [self::ADD => 2, self::DEL => 1],
	];

	public function __construct($db)
	{
		$this->_db = $db;
	}
	
	public function parse($text)
	{
		$chunks = explode(' ', $text);
		if (!count($chunks)) {
			return false;
		}

		$finished = false;
		$pos = 0;
		$pool = $this->_map;
		$out = [];
		$required = 0;

		while($pos < count($chunks)) {
			$cmd = strtolower($chunks[$pos++]);
			if (isset($pool[$cmd])) {
				$out[] = $cmd;
				$pool = $pool[$cmd];
				if ($pool === true) {
					if (isset($this->_map[$cmd])) {
						$pool = $this->_map[$cmd];
					} else {
						break;
					}
				} else if (!is_array($pool)) {
					$required = $pool;
					$finished = true;
					break;
				}
			} else {
				break;
			}
		}

		if ($finished && ($pos + $required <= count($chunks))) {
			while($required--) {
				$out[] = $chunks[$pos++];
			}

			return $out;
		}

		return null;
	}
}
