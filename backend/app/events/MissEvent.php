<?php
namespace app\events;

class MissEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_MISS;
	}

	public function run()
	{
		$this->msg = $this->ctx->getMessage($this->model->zone, $this->model->mode, $this->type);
		if (!$this->msg) {
			$this->msg = '* Our strike did not connect';
		}
	}
}
