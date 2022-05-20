<?php
namespace app\events;

use app\models\ServerVar;
use app\models\Zone;

class LossGoldEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_LOSS_GOLD;
	}

	public function run()
	{
		if (!$this->model->gold) {
			return $this->transfer(new DefaultEvent($this->ctx));
		}
		
		$g = intval($this->ctx->serverVars->getRelValue(ServerVar::M_EVT_LOSS_GOLD, $this->model));
		if (!$g) {
			$g = 1;
		}
		$this->model->gold -= $g;
		$this->params['gold'] = $this->model->gold;

		$msg = $this->ctx->getMessage(Zone::ZONE_EXPLORE, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = "* We've lost $g piasters";
		} else {
			$this->msg = str_replace('$gold', $g, $msg);
		}
	}
}
