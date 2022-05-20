<?php
namespace app\commands;

use app\components\Context;
use app\events\ErrorEvent;
use app\events\ModeEvent;
use app\models\Mode;
use app\models\Zone;

class ModeCommand extends Command
{
	/** @param Context $ctx */
	public function run($ctx, & $params = null)
	{
		$mode = $params[0];
		$evt = new ModeEvent($ctx);

		switch($mode) {
			case Mode::MODE_HIDE:
			case Mode::MODE_EXPLORE:
			case Mode::MODE_AGRO:
				$evt->args['mode'] = $mode;
				break;
			default: $evt = new ErrorEvent($ctx);
		}

		$evt->run();

		return new Result(
			$evt,
			null
		);
	}
	
	public function validate($ctx)
	{
		return $ctx->ship->zone != Zone::ZONE_DEAD;
	}
}
