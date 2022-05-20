<?php
namespace app\events;

class FullRepairEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_FULL_REPAIR;
	}

	public function run()
	{
		$this->msg = $this->ctx->getMessage($this->model->zone, null, $this->type);
		if (!$this->msg) {
			$this->msg = '* The ship is fully repaired';
		}
			
		return true;
	}
}
