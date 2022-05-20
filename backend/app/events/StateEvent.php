<?php
namespace app\events;

use app\components\Calc;
use app\components\Context;
use app\models\Actor;
use app\models\Battle;
use app\models\Ship;
use app\models\Zone;
use Exception;

class StateEvent extends Event
{
	/**
	 * @param Context $ctx
	 * @param Ship $model
	 * @param array $args
	 */
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_STATE;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $ctx->ship;
		$this->params = [
			'zone' => $ship->zone,
			'mode' => $ship->mode,
			'ship_name' => $ship->title,
//			'dir' => $ship->dir
		];

		$msg = $ctx->getMessage($ship->zone, $ship->mode, $this->type);
		
		if ($ship->zone == Zone::ZONE_DOCK) {
			$dock = $ctx->docks->get($ship->hops);
			$this->params['name'] = $dock->name;
			if (!$msg) {
				$msg = ("* The ship is staying in the {$dock->title} dock.");
			} else {
				$msg = str_replace('$name', $dock->title, $msg);
			}
		} else if ($ship->zone == Zone::ZONE_BATTLE) {
			/** @var Battle $battle */
			$battle = $ctx->battles->get($ship->battle_id);
			if (!$battle) {
				throw new Exception(date('d.m.Y H:i:s') . " ship {$ship->uid} {$ship->title} battle id {$ship->battle_id} desync");
			}
			$enemy = $battle->getEnemy(Actor::TYPE_PLAYER, $ship->uid);
			if ($enemy->isPlayer) {
				$model = $enemy->model;
				$this->params['title'] = $model->title;
				$this->params['level'] = $model->level;
				$this->params['hp'] = $model->hp;
				$this->params['max_hp'] = $model->max_hp;
				$this->params['def'] = $model->def;
				$this->params['max_def'] = $model->max_def;
				$this->params['atk'] = $model->min_atk;
				$this->params['spd'] = $model->spd;
				$this->params['acc'] = $model->acc;
				$this->params['man'] = $model->man;
				$this->params['b_type'] = 'pvp';
				
				if (!$msg) {
					$msg = "* Continuing battle with {$model->title}!";
				} else {
					$msg = str_replace('$name', $model->title, $msg);
				}
			} else {
				$model = $enemy->model;

				$mob_m = Calc::getMobParamMultiplier($ctx, $ship->hops);

				$this->params['title'] = $model->title;
				$this->params['name'] = $model->name;
				$this->params['img'] = $model->img_url;
				$this->params['hp'] = $enemy->hp;
				$this->params['max_hp'] = (int) ($model->hp * $mob_m);
				$this->params['def'] = $enemy->def;
				$this->params['max_def'] = (int) ($model->def * $mob_m);
				$this->params['atk'] = $model->min_atk;
				$this->params['spd'] = $model->spd;
				$this->params['acc'] = $model->acc;
				$this->params['man'] = $model->man;

				$this->params['b_type'] = 'pve';
				
				if (!$msg) {
					$msg = "* Continuing battle with {$model->title}!";
				} else {
					$msg = str_replace('$name', $model->title, $msg);
				}
			}
		} else if (!$msg) {
			$msg = '* All hands on deck!';
		}
		
		$this->msg = $msg;
	}
}
