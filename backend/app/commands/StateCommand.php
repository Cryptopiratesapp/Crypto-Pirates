<?php
namespace app\commands;

use app\components\Context;
use app\events\StateEvent;

class StateCommand extends Command
{
	/** @param Context $ctx */
	public function run($ctx, & $params = null)
	{
		$evt = new StateEvent($ctx);
		$evt->run();

		$ctx->redis->hset($ctx->redisKey, 'ready', 1);

		return new Result($evt, null);
	}
	
	public function validate($ctx)
	{
		return true;
	}
}
