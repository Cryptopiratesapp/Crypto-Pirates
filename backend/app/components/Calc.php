<?php
namespace app\components;

use app\models\Actor;
use app\models\ServerVar;

class Calc
{
	public static function getTime($ticks)
	{
		return [
			floor($ticks / 86400),
			floor(($ticks % 86400) / 3600),
			$ticks % 60
		];
	}

	public static function getRange($val, $min, $max)
	{
		$rmin = $val;
		if ($rmin > $min) {
			$rmin = $min;
		}
		$rmax = $val;
		if ($rmax > $max) {
			$rmax = $max;
		}
		
		return mt_rand($rmin, $rmax);
	}

	public static function getFloatRange($min, $max)
	{
		return $min + ($max - $min) * mt_rand(0, 1000) / 1000;
	}

	/**
	 * 
	 * @param Context $ctx
	 * @param Actor $selfActor
	 * @param Actor $targetActor
	 * @return boolean
	 */
	public static function getMissChance($ctx, $selfActor, $targetActor)
	{
		$self = $selfActor->model;
		$target = $targetActor->model;
		$acc = $self->getAcc();
		$man = $target->getMan();

		if ($selfActor->isPlayer) {
			$acc = $ctx->serverVars->getRelValue(ServerVar::M_ACC_ . $self->mode, $self);
		} else {
			$acc *= static::getMobParamMultiplier($ctx, $target->hops);
		}
			
		if ($targetActor->isPlayer) {
			$man = $ctx->serverVars->getRelValue(ServerVar::M_MAN_ . $target->mode, $target);
		} else {
			$man *= static::getMobParamMultiplier($ctx, $self->hops);
		}

		$c_miss = $man / $acc * $ctx->serverVars->getRelValue(ServerVar::M_MISS);
		$lim = $ctx->serverVars->getRelValue(ServerVar::LIM_MISS_MAX);
		if ($c_miss > $lim) {
			$c_miss = $lim;
		}

		return $c_miss > static::getFloatRange(0, 1);
	}

	public static function getMobParamMultiplier($ctx, $hops)
	{
		$a1 = $ctx->serverVars->getInt(ServerVar::P_MOB_PARAM_A1);
		$d = $ctx->serverVars->getInt(ServerVar::P_MOB_PARAM_D);
		$step = $ctx->serverVars->getInt(ServerVar::P_MOB_PARAM_STEP);
		$div = $ctx->serverVars->getInt(ServerVar::P_MOB_PARAM_DIV);
		$n = $a1 + (int) ($hops / $step);
		if ($n == 0) {
			$n = 1;
		}

		return $a1 + $d * ($n - 1) + $hops / $div;
	}
}
