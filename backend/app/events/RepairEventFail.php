<?php
namespace app\events;

class RepairEventFail extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_REPAIR;
	}

	public function run()
	{
		$this->msg = '* Insufficient resources';

		return;
	}
}
