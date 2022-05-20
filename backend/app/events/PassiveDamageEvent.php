<?php
namespace app\events;

class PassiveDamageEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_DAMAGE;
	}

	public function run()
	{
		// actor data is updated in attack event, here we only update ship data

		$ship = $this->model;
		$ship->hp = $this->args['hp'];
		$ship->def = $this->args['def'];

		$from = $this->args['from'];
		$atk = $this->args['atk'];
		$abs = $this->args['abs'];
		$dmg = $this->args['dmg'];
		$msg = $this->ctx->getMessage($ship->zone, $ship->mode, $this->type);
		if (!$msg) {
			$this->msg = "* {$from->title} hit us by $atk, Def has taken $abs, HP lowered by $dmg";
		} else {
			$this->msg = str_replace(
				['$atk', '$abs', '$dmg', '$name'],
				[$atk, $abs, $dmg, $from->title],
				$msg
			);
		}
	}
}
