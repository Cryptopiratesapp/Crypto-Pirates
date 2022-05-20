<?php
namespace app\events;

class OverloadEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_OVERLOAD;
	}

	public function run()
	{
		$r = isset($this->args['r']) ? $this->args['r'] : 'some';
		$msg = $this->ctx->getMessage($this->model->zone, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = "* Found some resources, but could not get them, the ship is overloaded";
		} else {
			$this->msg = str_replace('res', $r, $msg);
		}
	}
}
