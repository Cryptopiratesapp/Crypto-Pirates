<?php
namespace app\events;

use app\components\Context;
use app\models\Ship;
use app\models\Zone;

class ArriveEvent extends Event
{
	/**
	 * @param Context $ctx
	 * @param Ship $model
	 * @param array $args
	 */
	public function __construct($ctx, $model = null, $args = null)
	{
		parent::__construct($ctx, $model, $args);
		$this->type = static::TYPE_ARRIVE;
	}

	public function run()
	{
		$dock = $this->args['dock'];

		$msg = $this->ctx->getMessage(Zone::ZONE_EXPLORE, $this->model->mode, $this->type);
		if (!$msg) {
			$this->msg = ("* Arrived to {$dock->title}.");
		} else {
			$this->msg = str_replace(
				['$title', '$gold', '$hops'],
				[$dock->title, $this->model->gold, $this->model->travel_hops],
				$msg
			);
		}

		$this->model->zone = Zone::ZONE_DOCK;
		$this->model->travel_hops = 0;
		$this->model->hops = $dock->hop;
		$this->params = [
			'zone' => Zone::ZONE_DOCK,
			'name' => $dock->name,
			'title' => $dock->title
		];

		$known = isset($this->args['known']) ? $this->args['known'] : false;
		if (!$known) {
			if (!$this->ctx->userDock->exists($this->model->uid, $dock->id)) {
				$this->ctx->userDock->add($this->model->uid, $dock->id);
			}
		}
	}
}
