<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class DepartEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_DEPART;
	}

	public function run()
	{
		$this->msg = $this->ctx->getMessage($this->model->zone, $this->model->mode, $this->type);
		if (!$this->msg) {
			$this->msg = '* The rest is over. Taking off...';
		}
		$ship = $this->model;
		$ship->zone = Zone::ZONE_EXPLORE;
		$ship->dir = $this->args['dir'];
		$ship->hops += $ship->dir;
		if ($ship->hops < 0) {
			$ship->hops = $this->ctx->serverVars->getInt(ServerVar::P_MAX_HOPS);
		}

		$this->params = ['zone' => Zone::ZONE_EXPLORE, 'mode' => $ship->mode];
	}
}
