<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class LossResEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_LOSS_RES;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;
		$res = $ship->res;
		if (!$res) {
			return $this->transfer(new DefaultEvent($ctx));
		}
		
		$r = intval($this->ctx->serverVars->getRelValue(ServerVar::M_EVT_LOSS_RES, $ship));

		if (!$r) {
			$r = 1;
		}
		if ($r > $res) {
			$r = $res;
		}
		$res -= $r;
		$ship->res = $res;
		$this->params['res'] = $res;
		
		$msg = $ctx->getMessage(Zone::ZONE_EXPLORE, $ship->mode, $this->type);
		if (!$msg) {
			$this->msg = "* We've lost $r resources";
		} else {
			$this->msg = str_replace('$res', $r, $msg);
		}
	}
}
