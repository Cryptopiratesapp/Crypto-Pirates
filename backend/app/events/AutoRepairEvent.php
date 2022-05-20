<?php
namespace app\events;

use app\models\ServerVar;

class AutoRepairEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_AUTOREPAIR;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;

		if (!$ship->needsRepair()) {
			return $this->transfer(new FullRepairEvent($ctx));
		}

		$zone = $ctx->getActualZone($ship);
		$r = (int) $ctx->serverVars->getRelValue(ServerVar::COST_AUTOREPAIR_ . $zone);
 
		if ($r <= $ship->res) {
			$h = intval($ctx->serverVars->getRelValue(ServerVar::P_AUTOREPAIR_HP_ . $zone, $ship));
			if (!$h) {
				$h = 1;
			}
			$d = intval($ctx->serverVars->getRelValue(ServerVar::P_AUTOREPAIR_DEF_ . $zone, $ship));
			if (!$d) {
				$d = 1;
			}
			$ship->repair($h, $d, $r);

			$msg = $ctx->getMessage($zone, $ship->mode, $this->type);
			if ($msg) {
				$this->msg = str_replace(['$hp', '$def', '$res'], [$h, $d, $r], $msg);
			} else {
				$this->msg = "* The ship has been repaired for $h hp, $d defence";
			}

			$cd = (int) $ctx->serverVars->getInt(ServerVar::CD_AUTOREPAIR_ . $ship->zone);
			if ($cd) {
				$ctx->cds->add($ship->uid, Event::TYPE_AUTOREPAIR, $cd);
			}

			return true;
		}
		
		return false;
	}
}
