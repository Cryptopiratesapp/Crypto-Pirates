<?php
namespace app\components\strategy;

use app\events\ArriveEvent;
use app\events\Event;
use app\events\EventWrapper;
use app\models\ServerVar;

class ExploreStrategy
{
	private $_ctx;
	private $_eventFactory;
	private $_wrapper;

	public function __construct($ctx, $eventFactory)
	{
		$this->_ctx = $ctx;
		$this->_eventFactory = $eventFactory;
		$this->_wrapper = new EventWrapper();
	}

	public function run()
	{
		/** @var Event $event */
		$event = null;
		$ctx = $this->_ctx;
		$ship = $ctx->ship;
		$ship->hops += $ship->dir;
		$ship->total_hops++;
		$ship->travel_hops++;

		$max_hops = $ctx->serverVars->getInt(ServerVar::P_MAX_HOPS);

		if ($ship->hops < 0) {
			$ship->hops = $max_hops;
		} else if ($ship->hops > $max_hops) {
			$ship->hops = 0;
		}

		if ($ctx->docks->exists($ship->hops)) {
			$event = new ArriveEvent(
				$ctx,
				$ctx->ship,
				['dock' => $ctx->docks->get($ship->hops)]
			);
		} else {
			$event = $this->_eventFactory->getEvent();
		}
	
		$this->_wrapper->run($event);
	
		return $this->_wrapper->event;
	}
}
