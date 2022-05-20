<?php
namespace app\models;

class Actor
{
	const TYPE_PLAYER = 0;
	const TYPE_MOB = 1;

	public $model;
	public $id;
	public $realId;
	public $type;
	public $isPlayer;
	public $hp;
	public $def;
	public $spd;

	public function __construct($type, $model, $override = null)
	{
		$this->model = $model;
		$this->type = $type;
		$this->isPlayer = $type === self::TYPE_PLAYER;
		// ids of players and monsters can collide so generate special id for monsters 
		if ($this->isPlayer) {
			$this->id = $model->uid;
			$this->realId = $model->uid;
		} else {
			$this->realId = $model->id;
			$this->id = 'm' . $this->realId;
		}
		if ($override) {
			$this->hp = $override['hp'];
			$this->def = $override['def'];
			$this->spd = $override['spd'];
		} else {
			$this->hp = $model->hp;
			$this->def = $model->def;
			$this->spd = $model->getSpd();
		}
	}

	public function serialize()
	{
		// don't serialize much for player because all data is available and up-to-date in shipmap
		if ($this->isPlayer) {
			return [
				'id' => $this->realId,
				'type' => $this->type,
			];
		} else {
			return [
				'id' => $this->realId,
				'type' => $this->type,
				'hp' => $this->hp,
				'def' => $this->def
			];
		}
	}
}
