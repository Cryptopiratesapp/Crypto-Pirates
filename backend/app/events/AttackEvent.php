<?php
namespace app\events;

use app\components\Calc;
use app\models\ServerVar;

class AttackEvent extends Event
{
	public $atk;
	public $abs;
	public $dmg;

	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_ATTACK;
	}

	public function run()
	{
		// because target could be mob, only update target actor stats here,
		// target ship stats will be updated in passive damage event
		$ctx = $this->ctx;
		$self = $this->model;
		$targetActor = $this->args['target'];
		$target = $targetActor->model;

		// коефф. градации параметров мода от хопа
		$m_abs = 1;
		$m_man = 1;

		if ($targetActor->isPlayer) {
			$m_abs = $ctx->serverVars->getRelValue(ServerVar::M_ABS_ . $target->mode);
			$m_man = $ctx->serverVars->getRelValue(ServerVar::M_MAN_ . $target->mode);
		} else {
			$m_man = Calc::getMobParamMultiplier($ctx, $self->hops); // $mob_m
			// $m_abs = $mob_m; // absorb for monster is not modified
		}

		// коэфф. урона от моды
		$atk = mt_rand($self->min_atk, $self->max_atk) * $ctx->serverVars->getRelValue(ServerVar::M_ATK_ . $self->mode);

		$acc = $ctx->serverVars->getRelValue(ServerVar::M_ACC_ . $self->mode, $self);

		$m_atk_max = $acc / ($target->getMan() * $m_man);
		$m_atk_min = $m_atk_max / 2;

		$lim = $ctx->serverVars->getRelValue(ServerVar::LIM_M_ATK_MIN);
		if ($m_atk_min > $lim) {
			$m_atk_min = $lim;
		}
		$lim = $ctx->serverVars->getRelValue(ServerVar::LIM_M_ATK_MAX_MIN);
		if ($m_atk_max < $lim) {
			$m_atk_max = $lim;
		}
		$lim = $ctx->serverVars->getRelValue(ServerVar::LIM_M_ATK_MAX_MAX);
		if ($m_atk_max > $lim) {
			$m_atk_max = $lim;
		}

		$atk = (int) ($atk * Calc::getFloatRange($m_atk_min, $m_atk_max));
	
		$abs = ceil($target->getAbs() * $m_abs * $targetActor->def);
		$dmg = 0;

		if ($abs >= $atk) {
			$abs = $atk;
		} else {
			$dmg = $atk - $abs;
		}

		$targetActor->def -= $abs;
		$targetActor->hp -= $dmg;

		if ($targetActor->hp < 0) {
			$targetActor->hp = 0;
		}

		$this->atk = $atk;
		$this->abs = $abs;
		$this->dmg = $dmg;

		$this->params = ['hp' => $targetActor->hp, 'def' => $targetActor->def];

		$msg = $ctx->getMessage($self->zone, $self->mode, $this->type);
		if (!$msg) {
			$this->msg = "* Strike with $atk damage, enemy lost $dmg HP and $abs Def";
		} else {
			$this->msg = str_replace(
				['$atk', '$abs', '$dmg', '$name'],
				[$atk, $abs, $dmg, $target->title],
				$msg
			);
		}
	}
}
