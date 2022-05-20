<?php
namespace app\events;

use app\models\ServerVar;

class ModeEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_MODE;
	}

	public function run()
	{
		$mode = $this->args['mode'];
		$this->model->mode = $mode;
		$this->params = ['mode' => $mode];
		/** event must be run after actual mode applied to ship */
		$zone = $this->ctx->getActualZone($this->model);
		$msg = $this->ctx->getMessage($zone, $mode, $this->type);
		if (!$msg) {
			$this->msg = ('* Changed the mode to ' . $this->args['mode']);
		} else {
			$sv = $this->ctx->serverVars;
			$acc = $sv->getPDiffValue(ServerVar::M_ACC_ . $mode);
			$atk = $sv->getPDiffValue(ServerVar::M_ATK_ . $mode);
			$abs = $sv->getPDiffValue(ServerVar::M_ABS_ . $mode);
			$spd = $sv->getPDiffValue(ServerVar::M_SPD_ . $mode);
			$man = $sv->getPDiffValue(ServerVar::M_MAN_ . $mode);
			$repair = $sv->getPDiffValue(ServerVar::M_REPAIR_ . $zone . '_' . $mode);
			$m_cost_repair = $sv->getPDiffValue(ServerVar::M_COST_REPAIR_ . $zone . '_' . $mode);

			$this->msg = str_replace(
				['$atk', '$abs', '$spd', '$acc', '$man', '$repair', '$cost_repair'],
				[$atk, $abs, $spd, $acc, $man, $repair, $m_cost_repair],
				$msg
			);
		}
	}
}