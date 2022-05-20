<?php
namespace app\events;

class RepairEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_REPAIR;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$ship = $ctx->ship;

		$h = $this->args['h'];
		$d = $this->args['d'];
		$r = isset($this->args['r']) ? $this->args['r'] : 0;
		$g = isset($this->args['g']) ? $this->args['g'] : 0;

		$ship->repair($h, $d, $r, $g);
		$zone = $ctx->getActualZone($ship);
		$msg = $ctx->getMessage($zone, $ship->mode, $this->type);
		if (!$msg) {
			$this->msg = "* Spent $r resources and $g piasters, restoring ship's HP by $h and Def by $d";
		} else {
			$this->msg = str_replace(['$hp', '$def', '$res', '$gold'], [$h, $d, $r, $g], $msg);
		}
	}
}
