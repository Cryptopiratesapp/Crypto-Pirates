<?php
namespace app\events;

use app\models\Actor;
use app\models\Mode;
use app\models\ServerVar;
use app\models\Zone;

class PvpAssaultEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_PVP_ASSAULT;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;
		$mode = $ship->mode;
		$hop = $ship->hops;
		if ($hop == 0 || $mode == Mode::MODE_HIDE) {
			// don't assault in hide mode
			$this->transfer(new DefaultEvent($ctx));
			return;
		}

		$range = $ctx->serverVars->get(ServerVar::P_PVP_RANGE);
		$begin = $hop + (int) $range['min_value'];
		if ($begin < 1) {
			// don't assault in dock
			$begin = 1;
			
		}
		$end = $hop + (int) $range['max_value'];
		$modes = null;
		if ($mode == Mode::MODE_AGRO) {
			$modes = [Mode::MODE_AGRO, Mode::MODE_EXPLORE];
		} else if ($mode == Mode::MODE_EXPLORE) {
			$modes = [Mode::MODE_HIDE];
		}

		$target_ids = $ctx->ships->getInHopRange($hop, $begin, $end, $modes);
		if (empty($target_ids)) {
			$this->transfer(new DefaultEvent($ctx));
			return;
		}
		$target_id = array_rand($target_ids);
		$target = $ctx->ships->get($target_id);

		$ctx->ships->removeFromSlot($ship);
		$ctx->setProcessed($target->uid);

		$actors = [
			new Actor(Actor::TYPE_PLAYER, $ship),
			new Actor(Actor::TYPE_PLAYER, $target)
		];
		$battle = $ctx->battles->createBattle($actors);
		$msg = $ctx->getMessage(ZONE::ZONE_EXPLORE, $mode, $this->type);
		if (!$msg) {
			$this->msg = "* We hopped into hop {$target->hops} and assaulted {$target->title}!";
		} else {
			$this->msg = str_replace(['$name', '$hops'], [$target->title, $target->hops], $msg);
		}

		$ship->last_zone = $ship->zone;
		$ship->zone = Zone::ZONE_BATTLE;
		$ship->hops = $target->hops;
		$ship->battle_id = $battle->id;

		$this->params = [
			'zone' => Zone::ZONE_BATTLE,
			'level' => $target->level,
			'hp' => $target->hp,
			'max_hp' => $target->max_hp,
			'def' => $target->def,
			'max_def' => $target->max_def,
			'side' => $battle->side,
			'title' => $target->title,
		];
		$this->chainEvent = new PvpIncomingEvent($ctx, $target, ['from' => $ship, 'battle' => $battle]);
	}
}
