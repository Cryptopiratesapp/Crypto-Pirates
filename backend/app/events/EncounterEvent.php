<?php
namespace app\events;

use app\components\Calc;
use app\components\ChanceProvider;
use app\models\Actor;
use app\models\Zone;

class EncounterEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_ENCOUNTER;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;
		$mob = $this->_findMob();
		if ($mob) {
			$msg = $ctx->getMessage(ZONE::ZONE_EXPLORE, $ship->mode, $this->type);
			if (!$msg) {
				$this->msg = '* We\'ve been assaulted by ' . $mob->title . '!';
			} else {
				$this->msg = str_replace('$name', $mob->title, $msg);
			}

			$ship->last_zone = $ship->zone;
			$ship->zone = Zone::ZONE_BATTLE;

			$mob_m = Calc::getMobParamMultiplier($ctx, $ship->hops);
			$mob_hp = (int) ($mob->hp * $mob_m);
			$mob_def = (int) ($mob->def * $mob_m);
			$mob_spd = (int) ($mob->spd * $mob_m);

			$this->params = [
				'zone' => Zone::ZONE_BATTLE,
				'hp' => $mob_hp,
				'max_hp' => $mob_hp,
				'def' => $mob_def,
				'max_def' => $mob_def,
				'atk' => $mob->min_atk,
				'spd' => $mob->spd,
				'acc' => $mob->acc,
				'man' => $mob->man,
				'name' => $mob->name,
				'title' => $mob->title,
			];

			$actors = [
				new Actor(Actor::TYPE_PLAYER, $ship),
				new Actor(Actor::TYPE_MOB, $mob, ['hp' => $mob_hp, 'def' => $mob_def, 'spd' => $mob_spd])
			];

			$battle = $this->ctx->battles->createBattle($actors);
			$ship->battle_id = $battle->id;
		} else {
			$this->transfer(new DefaultEvent($this->ctx));
		}
	}

	private function _findMob()
	{
		$hops = $this->model->hops;
		$pool = [];
		$list = $this->ctx->mobs->getList();
		foreach ($list as $id => $mob) {
			if ($mob->min_hops <= $hops && ($mob->max_hops == 0 || $mob->max_hops >= $hops)) {
				$chance = (int) $mob->chance;
				if (!isset($pool[$chance])) {
					$pool[$chance] = [$id];
				} else {
					$pool[$chance][] = $id;
				}
			}
		}

		if (empty($pool)) {
			return null;
		}

		$id = $this->ctx->chanceProvider->getItemDynamic($pool, ChanceProvider::CP_POOLED);
		$mob = $list[$id];
		
		return $mob;
	}
}
