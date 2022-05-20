<?php
namespace app\events;

use app\models\Actor;
use app\models\Battle;

class FleeFailEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_FLEE_FAIL;
	}

	public function run()
	{
		$msg = $this->ctx->getMessage($this->model->zone, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = '* Flee unsuccesful, continue fighting';
		} else {
			/** @var Battle $battle */
			$ctx = $this->ctx;
			$battle = $ctx->battles->get($this->model->battle_id);
			$actor = $battle->getEnemy(Actor::TYPE_PLAYER, $this->model->uid);
			$this->msg = str_replace('$name', $actor->model->title, $msg);
		}
	}
}
