<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class GainResEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_GAIN_RES;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;

		$res = $ship->res;
		$max_res = $ship->max_res;
		$r = intval($ctx->serverVars->getRelValue(ServerVar::M_EVT_GAIN_RES, $ship));
		if ($res < $max_res) {
			$res += $r;
			if ($res > $max_res) {
				$res = $max_res;
			}
			$ship->res = $res;
			$this->params['res'] = $res;
			
			$msg = $ctx->getMessage(Zone::ZONE_EXPLORE, $ship->mode, $this->type);
			if (!$msg) {
				$this->msg = "* We've found $r resources.";
			} else {
				$this->msg = str_replace('$res', $r, $msg);
			}
		} else {
			$this->transfer(new OverloadEvent($ctx, $ship, ['r' => $r]));
		}
	}
}
