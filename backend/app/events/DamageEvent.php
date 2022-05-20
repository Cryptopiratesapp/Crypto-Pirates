<?php
namespace app\events;

use app\components\Calc;
use app\models\ServerVar;

class DamageEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_DAMAGE;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$mob = $this->args['from'];
		$target = $this->model;

		// коэфф. силы моба от хопа
		$mob_m = Calc::getMobParamMultiplier($ctx, $target->hops);

		$atk = ceil(mt_rand($mob->min_atk, $mob->max_atk) * $mob_m);
		$acc = $mob->acc * $mob_m;
		// маневренность корабля * коэфф. от моды
		$man = $ctx->serverVars->getRelValue(ServerVar::M_MAN_ . $target->mode, $target);

		$m_atk_max = $acc / $man;
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

		// абсорб корабля * коэфф. от моды
		$abs = $ctx->serverVars->getRelValue(ServerVar::M_ABS_ . $target->mode, $target);
		$abs = ceil($abs * $target->def);

		$dmg = 0;

		if ($abs >= $atk) {
			$abs = $atk;
		} else {
			$dmg = $atk - $abs;
		}

		$target->def -= $abs;
		$target->hp -= $dmg;

		if ($target->hp < 0) {
			$target->hp = 0;
		}

		$msg = $this->ctx->getMessage($target->zone, $target->mode, $this->type);
		if (!$msg) {
			$this->msg = "* Received $atk damage from {$mob->title}, Def took $abs, and HP $dmg";
		} else {
			$this->msg = str_replace(
				['$atk', '$abs', '$dmg', '$name'],
				[$atk, $abs, $dmg, $mob->title],
				$msg
			);
		}
	}
}
