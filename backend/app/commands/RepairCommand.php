<?php
namespace app\commands;

use app\components\Context;
use app\events\FullRepairEvent;
use app\events\RepairEvent;
use app\events\RepairEventFail;
use app\models\ServerVar;
use app\models\Zone;

class RepairCommand extends Command
{
	/** @param Context $ctx */
	public function run($ctx, & $params = null)
	{
		$evt = null;
		$ship = $ctx->ship;
		
		if (!$ship->needsRepair()) {
			$evt = new FullRepairEvent($ctx, $ship);
		} else {
			$sv = $ctx->serverVars;
			$zone = $ctx->getActualZone($ship);
			$suffix = $zone . '_' . $ship->mode;
			$m = $sv->getRelValue(ServerVar::M_COST_REPAIR_ . $suffix, null, 1);
			$cost = ceil($sv->getInt(ServerVar::COST_REPAIR_ . $zone) * $m);

			$r = 0;
			$g = 0;

			if ($zone == Zone::ZONE_DOCK) {
				$c2 = $cost / 2;
				$cost = ceil($c2 * (1 - $ship->hp / $ship->max_hp) + $c2 * (1 - $ship->def / $ship->max_def));

				if ($cost > $ship->gold) {
					$evt = new RepairEventFail($ctx);
				} else {
					$g = $cost;
					$h = $ship->max_hp - $ship->hp;
					$d = $ship->max_def - $ship->def;
					$evt = new RepairEvent($ctx, $ship, ['h' => $h, 'd' => $d, 'r' => $r, 'g' => $g]);
				}
			} else { // battle and nav
				if ($cost > $ship->res) {
					$cost = ceil($sv->getInt(ServerVar::COST_REPAIR_ . $zone . '_gold', 0) * $m);
					if ($cost > $ship->gold) {
						$evt = new RepairEventFail($ctx);
					} else {
						$g = $cost;
					}
				} else {
					$r = $cost;
				}
			}
		}

		if (!$evt) {
			$m = $sv->getRelValue(ServerVar::M_REPAIR_ . $suffix, null, 1);
			$h = ceil($sv->getRelValue(ServerVar::P_REPAIR_HP_ . $zone, $ship) * $m);
			$d = ceil($sv->getRelValue(ServerVar::P_REPAIR_DEF_ . $zone, $ship) * $m);

			$evt = new RepairEvent($ctx, $ship, ['h' => $h, 'd' => $d, 'r' => $r, 'g' => $g]);
		}

		$evt->run();

		return new Result($evt, null);
	}

	public function validate($ctx)
	{
		return $ctx->ship->zone != Zone::ZONE_DEAD;
	}
}
