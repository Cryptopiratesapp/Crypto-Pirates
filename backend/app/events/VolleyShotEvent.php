<?php
namespace app\events;

use app\models\ServerVar;

class VolleyShotEvent extends Event
{
	public $atk;
	public $abs;
	public $dmg;
	
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_VOLLEY_SHOT;
	}

	public function run()
	{
		// because target could be mob, only update target actor stats here,
		// target ship stats will be updated in passive damage event
		$ctx = $this->ctx;
		$self = $this->model;
		$targetActor = $this->args['target'];
		$target = $targetActor->model;

		// Общий урон = Урон + Урон * (Точность-Маневренность)/100
		$sv = $ctx->serverVars;
		// коэфф. залпа
		$atk = (int) (
			mt_rand($self->min_atk, $self->max_atk)
			* $sv->getRelValue(ServerVar::M_VOLLEY_SHOT)
			* $sv->getRelValue(ServerVar::M_VOLLEY_SHOT_ . $self->mode, null, 1)
		);
		$acc = (int) $ctx->serverVars->getRelValue(ServerVar::M_ACC_ . $self->mode, $self);
		$m_atk = ($acc - $target->getMan()) / 100;

		if ($m_atk > 0) {
			$atk += ceil($atk * $m_atk);
		}

		// коэфф. абсорба от моды, если таргет - игрок
		$m_abs = 1;
		if ($targetActor->isPlayer) {
			$m_abs = $ctx->serverVars->getRelValue(ServerVar::M_ABS_ . $target->mode);
		}
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
			$this->msg = "* Volley shot for $atk damage, enemy Def decreased by $abs and HP by $dmg";
		} else {
			$this->msg = str_replace(
				['$atk', '$abs', '$dmg', '$name'],
				[$atk, $abs, $dmg, $target->title],
				$msg
			);
		}
		
		$cd = $ctx->serverVars->getInt(ServerVar::CD_CMD_VOLLEY_SHOT);
		if ($cd) {
			$ctx->cds->add($self->uid, Event::TYPE_VOLLEY_SHOT, $cd);
		}
	}
}
