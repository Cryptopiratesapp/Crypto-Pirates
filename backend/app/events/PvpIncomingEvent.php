<?php
namespace app\events;

use app\models\Zone;

class PvpIncomingEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_PVP_INCOMING;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;
		$from = $this->args['from'];
		$battle = $this->args['battle'];

		$msg = $ctx->getMessage(ZONE::ZONE_EXPLORE, $ship->mode, $this->type);
		if (!$msg) {
			$this->msg = "* We've been assaulted by {$from->title}!";
		} else {
			$this->msg = str_replace(['$hops', '$name'], [$ship->hops, $from->title], $msg);
		}

		$ctx->ships->removeFromHop($ship);
		$ctx->ships->removeFromSlot($ship);
		$ship->last_zone = $ship->zone;
		$ship->zone = Zone::ZONE_BATTLE;
		$ship->battle_id = $battle->id;

		$this->params = [
			'zone' => Zone::ZONE_BATTLE,
			'level' => $from->level,
			'hp' => $from->hp,
			'max_hp' => $from->max_hp,
			'def' => $from->def,
			'max_def' => $from->max_def,
			'title' => $from->title,
		];
	}
}
