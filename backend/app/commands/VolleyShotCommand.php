<?php
namespace app\commands;

use app\components\Context;
use app\events\Event;
use app\events\PassiveDamageEvent;
use app\events\VolleyShotEvent;
use app\models\Actor;
use app\models\Zone;

class VolleyShotCommand extends Command
{
	/** @param Context $ctx */
	public function run($ctx, & $params = null)
	{
		$ship = $ctx->ship;
		
		$cd = $ctx->cds->get($ship->uid, Event::TYPE_VOLLEY_SHOT);
		if ($cd) {
			return new Result(
				null,
				't=' . Event::TYPE_INFO . "&m=* not available for $cd more turns"
			);
		}

		$battle = $ctx->battles->get($ship->battle_id);
		$enemyActor = $battle->getEnemy(Actor::TYPE_PLAYER, $ship->uid);

		$evt = new VolleyShotEvent($ctx, $ship, ['target' => $enemyActor]);
		$evt->run();

		if ($enemyActor->isPlayer) {
			$evt->chainEvent = new PassiveDamageEvent(
				$ctx, $enemyActor->model,
				[
					'hp' => $evt->params['hp'],
					'def' => $evt->params['def'],
					'atk' => $evt->atk,
					'abs' => $evt->abs,
					'dmg' => $evt->dmg,
					'from' => $ship
				]
			);
		}

		if ($enemyActor->hp < 1) {
			$battle->endEvent = $evt;
		}

		return new Result($evt, null);
	}

	public function validate($ctx)
	{
		/** @todo: check cooldown */
		return $ctx->ship->zone == Zone::ZONE_BATTLE;
	}
}
