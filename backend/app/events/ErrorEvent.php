<?php
namespace app\events;

class ErrorEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_ERROR;
	}

	public function run()
	{
		$this->params = [];
		$this->msg = '* If you see this, it must be some error...';
	}
}
