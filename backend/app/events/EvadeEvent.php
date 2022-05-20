<?php

namespace app\events;

class EvadeEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_EVADE;
	}

	public function run()
	{
		$ctx = $this->ctx;
		$from = $this->args['from'];
		$msg = $ctx->getMessage($this->model->zone, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = '* We have swiftly evaded the blow!';
		} else {
			$this->msg = str_replace('$name', $from->title, $msg);
		}
	}
}
