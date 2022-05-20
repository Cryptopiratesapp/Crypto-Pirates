<?php
namespace app\components;

//use app\models\Protoship;
use app\models\Ship;
use app\models\Zone;
use Exception;
use PDO;

class ShipMap
{
	private $_ctx;
	private $_updateShipSth;
	private $_selectAllSth;
	private $_selectShipSth;
	//private $_proto = [];
	private $_ships = [];
	private $_hops = [];
	private $_slots = [];

	/** @param Context $ctx */
	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
		//$this->_selectAllProtoSth = $ctx->pdo->prepare('select * from protoship');
		$this->_selectShipSth = $ctx->pdo->prepare('select * from ship where user_id=:uid');
		$this->_selectAllSth = $ctx->pdo->prepare('select * from ship where active=1');
		$this->_updateShipSth = $ctx->pdo->prepare(
<<<SHIP
	update ship
	set dir=:dir, slot=:slot, hp=:hp, def=:def, level=:level, slots=:slots, gold=:gold, res=:res,
		zone=:zone, last_zone=:last_zone, mode=:mode, hops=:hops, travel_hops=:travel_hops,
		total_hops=:total_hops, max_hops=:max_hops, battle_id=:battle_id,
		pvp_wins=:pvp_wins, pve_wins=:pve_wins, deaths=:deaths, max_gold=:max_gold
	where user_id=:uid and active=1
SHIP
		);
	}

//	public function load_proto()
//	{
//		$this->_proto = [];
//		$res = $this->_selectAllProtoSth->execute();
//		if (!$res) {
//			throw new Exception('Error loading protoships from db');
//		}
//		while ($data = $this->_selectAllProtoSth->fetch(PDO::FETCH_ASSOC)) {
//			$proto = new Protoship($data);
//			$this->_proto[$proto->level] = $proto;
//		}
//
//		$this->_selectAllProtoSth->closeCursor();
//	}
	
	public function load()
	{
//		$this->load_proto();

		$this->_ships = [];
		$this->_hops = [];
		$this->_slots = [];

		$res = $this->_selectAllSth->execute();
		if (!$res) {
			throw new Exception('Error loading ships from db');
		}
		while ($data = $this->_selectAllSth->fetch(PDO::FETCH_ASSOC)) {
			$ship = new Ship($data);
			$this->registerShip($ship);
		}

		$this->_selectAllSth->closeCursor();
		
		return count($this->_ships);
	}

	public function registerShip($ship)
	{
		//$ship->proto = $this->_proto[$ship->level];
		$this->_ships[$ship->uid] = $ship;

		// any non-combatant ships are placed to slot maps
		if ($ship->zone !== Zone::ZONE_BATTLE) {
			$this->addToSlot($ship);
		}
		// only exploration ships are placed to hop maps
		if ($ship->zone == Zone::ZONE_EXPLORE) {
			$this->addToHop($ship);
		}
	}

	public function reload($uid)
	{
		$this->_selectShipSth->bindValue(':uid', $uid);
		$shipdata = DbHelper::findOne($this->_selectShipSth);
		if (isset($this->_ships[$uid])) {
			$ship = $this->_ships[$uid];
			$this->removeFromHop($ship);
			$this->removeFromSlot($ship);
		}
		$ship = new Ship($shipdata);
		$this->registerShip($ship);
	}
	
	public function get($uid)
	{
		return $this->_ships[$uid];
	}
	
	public function addToSlot($ship)
	{
		$this->_slots[$ship->slot][$ship->uid] = true;
	}
	
	public function removeFromSlot($ship)
	{
		unset ($this->_slots[$ship->slot][$ship->uid]);
		if (empty($this->_slots[$ship->slot])) {
			unset ($this->_slots[$ship->slot]);
		}
	}
	
	public function getAtSlot($slot)
	{
		if (isset($this->_slots[$slot])) {
			return $this->_slots[$slot];
		}
		
		return [];
	}

	public function updateSlot($ship, $new_slot)
	{
		if (isset($this->_slots[$ship->slot][$ship->uid])) {
			if ($ship->slot == $new_slot) {
				return;
			}
			$this->removeFromSlot($ship);
		}
		$ship->slot = $new_slot;
		$this->addToSlot($ship);
	}

	public function addToHop($ship)
	{
		$this->_hops[$ship->hops][$ship->mode][$ship->uid] = true;
	}

	public function removeFromHop($ship)
	{
		// remember that ship mode must be the same as when added
		$hop = $ship->hops;
		$mode = $ship->mode;
		$uid = $ship->uid;

		if (isset($this->_hops[$hop][$mode][$uid])) {
			unset($this->_hops[$hop][$mode][$uid]);
			if (empty($this->_hops[$hop][$mode])) {
				unset($this->_hops[$hop][$mode]);
			}
			if (empty($this->_hops[$hop])) {
				unset($this->_hops[$hop]);
			}
		}
	}

	public function & getAtHop($hop, & $modes)
	{
		$out = [];
		if (isset($this->_hops[$hop])) {
			$base = & $this->_hops[$hop];
			foreach($modes as $mode) {
				if (isset($base[$mode])) {
					$out += $base[$mode];
				}
			}
		}
		return $out;
	}
	
	public function getInHopRange($hop, $begin, $end, & $modes)
	{
		$ray_left = $hop;
		$ray_right = $hop;
		$out = [];
		$inrange = true;
		while ($inrange && empty($out)) {
			$inrange = false;
			if ($ray_right < $end) {
				$inrange = true;
				$out += $this->getAtHop($ray_right, $modes);
				$ray_right++;
			}
			if ($ray_left > $begin) {
				$ray_left--;
				$inrange = true;
				$out += $this->getAtHop($ray_left, $modes);
			}
		}
		
		return $out;
	}

	/**
	 * @param Ship $ship
	 */
	function save($ship)
	{
		if ($ship->hops > $ship->max_hops) {
			$ship->max_hops = $ship->hops;
		}

		if ($ship->gold > $ship->max_gold) {
			$ship->max_gold = $ship->gold;
		}

		$this->_updateShipSth->bindValue(':uid', $ship->uid);
		$this->_updateShipSth->bindValue(':slot', $ship->slot);
		$this->_updateShipSth->bindValue(':level', $ship->level);
		$this->_updateShipSth->bindValue(':slots', $ship->slots);
		$this->_updateShipSth->bindValue(':hp', $ship->hp);
		$this->_updateShipSth->bindValue(':def', $ship->def);
		$this->_updateShipSth->bindValue(':level', $ship->level);
		$this->_updateShipSth->bindValue(':gold', $ship->gold);
		$this->_updateShipSth->bindValue(':res', $ship->res);
		$this->_updateShipSth->bindValue(':zone', $ship->zone);
		$this->_updateShipSth->bindValue(':dir', $ship->dir);
		$this->_updateShipSth->bindValue(':last_zone', $ship->last_zone);
		$this->_updateShipSth->bindValue(':mode', $ship->mode);
		$this->_updateShipSth->bindValue(':hops', $ship->hops);
		$this->_updateShipSth->bindValue(':travel_hops', $ship->travel_hops);
		$this->_updateShipSth->bindValue(':total_hops', $ship->total_hops);
		$this->_updateShipSth->bindValue(':max_hops', $ship->max_hops);
		$this->_updateShipSth->bindValue(':battle_id', $ship->battle_id);
		$this->_updateShipSth->bindValue(':pvp_wins', $ship->pvp_wins);
		$this->_updateShipSth->bindValue(':pve_wins', $ship->pve_wins);
		$this->_updateShipSth->bindValue(':deaths', $ship->deaths);
		$this->_updateShipSth->bindValue(':max_gold', $ship->max_gold);

		if (!$this->_updateShipSth->execute()) {
			fwrite(STDERR, "could not update ship for uid={$ship->uid}\n");
			
			return false;
		}

		return true;
		//echo "ship uid={$ship->uid} {$ship->zone}:{$ship->hops}:{$ship->mode} hp={$ship->hp} gold={$ship->gold} slot={$ship->slot}\n";
	}
}
