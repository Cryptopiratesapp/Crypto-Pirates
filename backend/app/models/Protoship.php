<?php
namespace app\models;

class Protoship
{
	public $name;
	public $title;
	public $level;
	public $hp;
	public $res;
	public $min_atk;
	public $max_atk;
	public $spd;
	public $acc;
	public $def;
	public $man;
	public $abs;

	public function __construct($data)
	{
		$this->name = $data['name'];
		$this->title = $data['title'];
		$this->level = (int) $data['level'];
		$this->hp = (int) $data['hp'];
		$this->res = (int) $data['res'];
		$this->min_atk = (int) $data['min_atk'];
		$this->max_atk = (int) $data['max_atk'];
		$this->spd = (int) $data['spd'];
		$this->acc = (int) $data['acc'];
		$this->def = (int) $data['def'];
		$this->man = (int) $data['man'];
		$this->abs = (float) $data['abs'];
	}
}
