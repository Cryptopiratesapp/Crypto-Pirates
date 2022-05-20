<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class PassiveLoseEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_LOSE;
	}

	public function run()
	{
		$ship = $this->model;
		$from = $this->args['from'];

		$ship->zone = Zone::ZONE_DEAD;
		$this->params = [
			'zone' => Zone::ZONE_DEAD
		];
			
		$g = (int) $this->ctx->serverVars->getRelValue(ServerVar::M_EVT_LOSE_GOLD, $ship);
		$r = (int) $this->ctx->serverVars->getRelValue(ServerVar::M_EVT_LOSE_RES, $ship);
		
		$ship->deaths++;
		$ship->gold -= $g;
		$ship->res -= $r;
		if ($ship->res < 0) {
			$ship->res = 0;
		}

		$msg = $this->ctx->getMessage(Zone::ZONE_BATTLE, $ship->mode, $this->type);
		if (!$msg) {
			$this->msg = '* We have lost the battle but not the war...';
		} else {
			$this->msg = str_replace('name', $from->title, $msg);
		}
	}
}
