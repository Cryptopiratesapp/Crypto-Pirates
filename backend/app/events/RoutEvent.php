<?php
namespace app\events;

class RoutEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_ROUT;
	}

	public function run()
	{
		$ship = $this->model;
		$target = $this->args['target'];
		$msg = $this->ctx->getMessage($ship->zone, $ship->mode, $this->type);

		if (!$msg) {
			$this->msg = "* {$target->title} fled from the battle";
		} else {
			$this->msg = str_replace('$name', $target->title, $msg);
		}

		$ship->pvp_wins++;
		$ship->zone = $ship->last_zone;
		$this->params['zone'] = $ship->zone;
	}
}
