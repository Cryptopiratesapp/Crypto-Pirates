<?php
namespace app\commands;

use app\components\Context;
use app\events\FleeEvent;
use app\events\FleeFailEvent;
use app\models\ServerVar;
use app\models\Zone;

class FleeCommand extends Command
{
	/** @param Context $ctx */
	public function run($ctx, & $params = null)
	{
		$val = $ctx->serverVars->getInt(ServerVar::C_CMD_FLEE);
		$chance = mt_rand(0, 100);
		$evt = null;
		if ($chance < $val) {
			$evt = new FleeEvent($ctx);
		} else {
			$evt = new FleeFailEvent($ctx);
		}

		$evt->run();

		return new Result(
			$evt,
			null
		);
	}
	
	public function validate($ctx)
	{
		return $ctx->ship->zone == Zone::ZONE_BATTLE;
	}
}
