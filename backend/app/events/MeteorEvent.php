<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class MeteorEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_METEOR_RAIN;
	}

	public function run()
	{
		$dmg = intval($this->ctx->serverVars->getRelValue(ServerVar::METEOR_DMG, $this->model));
		$msg = $this->ctx->getMessage(Zone::ZONE_EXPLORE, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = ("* Meteor rain hit us for $dmg HP");
		} else {
			$this->msg = str_replace('$dmg', $dmg, $msg);
		}
		
		$this->model->hp -= $dmg;
	}
}
