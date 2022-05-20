<?php
namespace app\commands;

use app\components\Context;
use app\events\DepartEvent;
use app\events\NavEvent;
use app\models\Zone;

class NavCommand extends Command
{	

	/**
	 * @param Context $ctx
	 * @param array $params
	 * @return Result
	 */
	public function run($ctx, & $params = null)
	{
		$ship = $ctx->ship;
		$dir = 1;
		if ($params && $params[0] == 'B') {
			$dir = -1;
		}

		$evt = null;

		if ($ship->zone == Zone::ZONE_DOCK) {
			$evt = new DepartEvent($ctx, null, ['dir' => $dir]);
		} else {
			$evt = new NavEvent($ctx, null, ['dir' => $dir]); 
		}

		$evt->run();

		return new Result(
			$evt,
			null
		);
	}
	
	public function validate($ctx)
	{
		return $ctx->ship->zone == Zone::ZONE_DOCK || $ctx->ship->zone == Zone::ZONE_EXPLORE;
	}
}
