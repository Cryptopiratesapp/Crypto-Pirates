<?php
namespace app\models;

class Mob
{
	public $id;
	public $is_boss;
	public $level;
	public $name;
	public $title;
	public $img_url;
	public $min_hops;
	public $max_hops;
	public $chance;
	public $min_drop_gold;
	public $max_drop_gold;
	public $min_drop_res;
	public $max_drop_res;
	public $hp;
	public $min_atk;
	public $max_atk;
	public $spd;
	public $acc;
	public $def;
	public $man;
	public $abs;

	public function __construct($data)
	{
		$this->id = (int) $data['id'];
		$this->is_boss = (int) $data['is_boss'];
		$this->level = (int) $data['level'];
		$this->name = $data['name'];
		$this->title = $data['title'];
		$this->img_url = $data['img_url'];
		$this->min_hops = (int) $data['min_hops'];
		$this->max_hops = (int) $data['max_hops'];
		$this->chance = (int) $data['chance'];
		$this->min_drop_trophy = (int) $data['min_drop_trophy'];
		$this->max_drop_trophy = (int) $data['max_drop_trophy'];
		$this->min_drop_res = (int) $data['min_drop_res'];
		$this->max_drop_res = (int) $data['max_drop_res'];
		$this->hp = (int) $data['hp'];
		$this->min_atk = (int) $data['min_atk'];
		$this->max_atk = (int) $data['max_atk'];
		$this->spd = (int) $data['spd'];
		$this->acc = (int) $data['acc'];
		$this->def = (int) $data['def'];
		$this->man = (int) $data['man'];
		$this->abs = (float) $data['abs'];
	}

	public function getAcc()
	{
		return $this->acc;
	}
	
	public function getMan() {
		return $this->man;
	}
	
	public function getAbs()
	{
		return $this->abs;
	}
	
	public function getSpd()
	{
		return $this->spd;
	}
}
