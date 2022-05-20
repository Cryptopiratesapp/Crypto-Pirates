<?php
namespace app\events;

use app\models\Actor;

class FleeEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_FLEE;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;
		$msg = $ctx->getMessage($ship->zone, $ship->mode, $this->type);
		$battle = $ctx->battles->get($ship->battle_id);

		if (!$msg) {
			$this->msg = "* Fled from battle";
		} else {
			$actor = $battle->getEnemy(Actor::TYPE_PLAYER, $ship->uid);
			$this->msg = str_replace('$name', $actor->model->title, $msg);
		}

		$battle->endEvent = $this;
		$ship->zone = $ship->last_zone;
		$hop = $ship->hops - $ship->dir;
		if (!$ctx->docks->exists($hop)) {
			$ship->hops = $hop;
		}
		$this->params['zone'] = $ship->zone;
	}
}
