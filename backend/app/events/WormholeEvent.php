<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class WormholeEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_WORMHOLE;
	}

	public function run()
	{
		$hop = $this->model->hops;
		$new_hop = $hop + $this->ctx->serverVars->getRangeInt(ServerVar::P_EVT_WORMHOLE_HOPS);
		
		if ($new_hop < 0) {
			$new_hop = 1;
		}
		
		$this->model->hops = $new_hop;

		$this->msg = $this->ctx->getMessage(Zone::ZONE_EXPLORE, $this->model->mode, $this->type);
		if (!$this->msg) {
			$this->msg = ("* A wormhole sucked us at hop $hop. We came out at hop $new_hop...");
		}
	}
}
