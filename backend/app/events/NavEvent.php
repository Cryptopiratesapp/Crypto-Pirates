<?php
namespace app\events;

use app\components\Context;
use app\models\Ship;
use app\models\Zone;

class NavEvent extends Event
{
	/**
	 * @param Context $ctx
	 * @param Ship $model
	 * @param array $args
	 */
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_NAV;
	}

	public function run()
	{
		$dir = $this->args['dir'];

		$msg = $this->ctx->getMessage(Zone::ZONE_EXPLORE, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = "* Let's go!";
		}
//		else {
//			$this->msg = str_replace(
//				'$dir',
//				$dir,
//				$msg
//			);
//		}

		$this->model->dir = $dir;
//		$this->params = [
//			'dir' => $dir
//		];
	}
}
