<?php
namespace app\events;

use app\components\Calc;
use app\models\Actor;
use app\models\Zone;

class WinEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_WIN;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $this->model;
		$ship->zone = $ship->last_zone;
		
		$this->params = [
			'zone' => $ship->zone,
			'mode' => $ship->mode
		];
		
		$targetActor = $this->args['target'];
		$target = $targetActor->model;

		$msg = $ctx->getMessage(Zone::ZONE_BATTLE, $ship->mode, $this->type);
		if (!$msg) {
			$this->msg = '* The victory is ours!';
		} else {
			$drop_gold = 0;
			$drop_res = 0;
			if ($targetActor->type == Actor::TYPE_MOB) {
				$mob_m = Calc::getMobParamMultiplier($ctx, $ship->hops);
				$drop_gold = mt_rand($target->min_drop_gold, $target->max_drop_gold);
				$drop_res = mt_rand($target->min_drop_res, $target->max_drop_res);
				$drop_gold = (int) ($drop_gold * $mob_m);
				$drop_res = (int) ($drop_res * $mob_m);
				$ship->pve_wins++;
			} else {
				$ship->pvp_wins++;
			}

			$ship->gold += $drop_gold;
			$res = $ship->res + $drop_res;
			if ($res > $ship->max_res) {
				$res = $ship->max_res;
			}
			$ship->res = $res;
			$this->msg = str_replace(
				['$name', '$gold', '$res'],
				[$target->title, $drop_gold, $drop_res],
				$msg
			);
		}
	}
}
