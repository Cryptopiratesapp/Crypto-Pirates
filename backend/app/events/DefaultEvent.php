<?php
namespace app\events;

use app\models\Zone;

class DefaultEvent extends Event
{
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_DEFAULT;
	}

	public function run()
	{
		$this->params = [];
		$this->msg = $this->ctx->getMessage(Zone::ZONE_EXPLORE, $this->model->mode, $this->type);
		if (!$this->msg) {
			$this->msg = '* Something is happening, do not pay attention...';
		}
	}
}
