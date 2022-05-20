<?php
namespace app\events;

use app\models\ServerVar;

class GainGoldEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_GAIN_GOLD;
	}

	public function run()
	{
		$ctx = $this->ctx;

		$g = intval($ctx->serverVars->getRelValue(ServerVar::M_EVT_GAIN_GOLD, $this->model));
		$this->model->gold += $g;

		$msg = $ctx->getMessage($ctx->getActualZone($this->model), $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = "* We've found $g piasters";
		} else {
			$this->msg = str_replace('$gold', $g, $msg);
		}
	}
}