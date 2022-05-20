<?php
namespace app\models;

class Ship
{
	//public $proto;
	public $uid;
	public $slot;
	public $title;
	public $total_hops;
	public $hops;
	public $travel_hops;
	public $max_hops;
	public $level;
	public $slots;
	public $gold;
	public $zone;
	public $last_zone;
	public $mode;
	public $hp;
	public $max_hp;
	public $def;
	public $max_def;
	public $res;
	public $max_res;
	public $min_atk;
	public $max_atk;
	public $spd;
	public $acc;
	public $man;
	public $abs;
	public $battle_id;
	public $pvp_wins;
	public $pve_wins;
	public $deaths;
	public $max_gold;
	public $ticks;

	public function __construct($data)
	{
		$this->uid = (int) $data['user_id'];
		$this->slot = (int) $data['slot'];
		$this->title = $data['title'];
		$this->dir = (int) $data['dir'];
		$this->total_hops = (int) $data['total_hops'];
		$this->hops = (int) $data['hops'];
		$this->travel_hops = (int) $data['travel_hops'];
		$this->max_hops = (int) $data['max_hops'];
		$this->level = (int) $data['level'];
		$this->slots = (int) $data['slots'];
		$this->gold = (int) $data['gold'];
		$this->zone = $data['zone'];
		$this->last_zone = $data['last_zone'];
		$this->mode = $data['mode'];
		$this->hp = (int) $data['hp'];
		$this->max_hp = (int) $data['max_hp'];
		$this->def = (int) $data['def'];
		$this->max_def = (int) $data['max_def'];		
		$this->res = (int) $data['res'];
		$this->max_res = (int) $data['max_res'];
		$this->min_atk = (int) $data['min_atk'];
		$this->max_atk = (int) $data['max_atk'];
		$this->spd = (int) $data['spd'];
		$this->acc = (int) $data['acc'];
		$this->man = (int) $data['man'];
		$this->abs = (float) $data['abs'];
		$this->battle_id = (int) $data['battle_id'];
		$this->pvp_wins = (int) $data['pvp_wins'];
		$this->pve_wins = (int) $data['pve_wins'];
		$this->deaths = (int) $data['deaths'];
		$this->max_gold = (int) $data['max_gold'];
		$this->ticks = 0;
	}

	public function needsRepair()
	{
		return ($this->hp < $this->max_hp || $this->def < $this->max_def);
	}
	
	public function repair($hp, $def = 0, $res = 0, $gold = 0)
	{
		$this->res -= $res;
		$this->gold -= $gold;

		$hp += $this->hp;
		if ($hp > $this->max_hp) {
			$this->hp = $this->max_hp;
		} else {
			$this->hp = $hp;
		}
		$def += $this->def;
		if ($def > $this->max_def) {
			$this->def = $this->max_def;
		} else {
			$this->def = $def;
		}
	}

	public function trim()
	{
		if ($this->hp > $this->max_hp) {
			$this->hp = $this->max_hp;
		}
		if ($this->res > $this->max_res) {
			$this->res = $this->max_res;
		}
		if ($this->def > $this->max_def) {
			$this->def = $this->max_def;
		}
	}

	public function getAcc()
	{
		return $this->acc;
	}

	public function getMan()
	{
		return $this->man;
	}

	public function getSpd()
	{
		return $this->spd;
	}

	public function getAbs()
	{
		return $this->abs;
	}
}
