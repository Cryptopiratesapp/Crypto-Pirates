<?php
namespace app\components\strategy;

use app\components\Calc;
use app\components\Context;
use app\events\DamageEvent;
use app\events\EvadeEvent;
use app\events\Event;
use app\models\Actor;
use app\models\Battle;

class MobBattleStrategy
{
	/**
	 * @param Context $ctx
	 * @param Actor $selfActor
	 * @param Battle $battle
	 * @return Event
	 */
	public function run($ctx, $selfActor, $battle)
	{
		$mob = $selfActor->model;
		$targetActor = $battle->getEnemy(Actor::TYPE_MOB, $mob->id);
		$target = $targetActor->model;

		if (Calc::getMissChance($ctx, $selfActor, $targetActor)) {
			$evt = new EvadeEvent($ctx, $target, ['from' => $mob]);
			$evt->run();
			return $evt;
		}

		$evt = new DamageEvent($ctx, $target, ['from' => $mob]);
		$evt->run();

		// update target actor data from modified target model data
		$targetActor->hp = $target->hp;
		$targetActor->def = $target->def;

		if ($target->hp < 1) {
			$battle->endEvent = $evt;
		}

		return $evt;
	}
}
