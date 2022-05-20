<?php
namespace app\components\strategy;

use app\components\Calc;
use app\components\Context;
use app\events\AttackEvent;
use app\events\AutoRepairEvent;
use app\events\EvadeEvent;
use app\events\Event;
use app\events\EventWrapper;
use app\events\MissEvent;
use app\events\PassiveDamageEvent;
use app\models\Actor;
use app\models\Battle;
use app\models\ServerVar;

class PlayerBattleStrategy
{
	private $_wrapper;
	
	public function __construct()
	{
		$this->_wrapper = new EventWrapper();
	}

	/**
	 * @param Context $ctx
	 * @param Actor $selfActor
	 * @param Battle $battle
	 * @return Event
	 */
	public function run($ctx, $selfActor, $battle)
	{
		$ship = $selfActor->model;
		$ctx->setShip($ship);
		
		$ctx->cds->update($ship->uid);
		$result = $ctx->processRequests();

		// events that are result of process request and can end battle:
		// flee, volley_shot
		// chain events must be set and battle needs to be ended (if due) before return

		$repaired = false;

		if ($result) {
			if ($result->evt) {
				return $result->evt;
			}
		}

		if (!$repaired && !$ctx->cds->get($ship->uid, Event::TYPE_AUTOREPAIR)) {
			$t_autorepair = $ctx->serverVars->getRelValue(ServerVar::T_AUTOREPAIR_ . $ship->zone, $ship);
			if ($t_autorepair >= $ship->hp) {
				$evt = new AutoRepairEvent($ctx, $ship);
				if ($this->_wrapper->run($evt)) {
					return $this->_wrapper->event;
				}
			}
		}
	
		$targetActor = $battle->getEnemy(Actor::TYPE_PLAYER, $ship->uid);
		$target = $targetActor->model;

		if (Calc::getMissChance($ctx, $selfActor, $targetActor)) {
			$evt = new MissEvent($ctx, $ship);
			$evt->run();
			if ($targetActor->isPlayer) {
				$evt->chainEvent = new EvadeEvent($ctx, $target, ['from' => $ship]);
			}
			return $evt;
		}

		$evt = new AttackEvent($ctx, $ship, ['target' => $targetActor]);
		$evt->run();
		if ($targetActor->isPlayer) {
			$evt->chainEvent = new PassiveDamageEvent(
				$ctx, $target,
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

		if ($targetActor->hp < 1) {
			$battle->endEvent = $evt;
		}

		return $evt;
	}
}
