<?php
namespace app\components\strategy;

use app\events\ArriveEvent;

class DeadStrategy
{
	private $_ctx;
	private $_eventFactory;
	
	public function __construct($context, $eventFactory)
	{
		$this->_ctx = $context;
		$this->_eventFactory = $eventFactory;
	}
	
	public function run()
	{
		$ctx = $this->_ctx;
		$ship = $ctx->ship;

		$target_dock = $this->_ctx->docks->get(0);
		$min = $ship->hops;
		$visited = $ctx->userDock->getList($ship->uid);
		$cnt = count($visited);

		for ($i = 0; $i < $cnt; $i++) {
			$dock_id = $visited[$i];
			$dock = $ctx->docks->getById($dock_id);
			$tmp = abs($ship->hops - $dock->hop);
			if ($tmp < $min) {
				$target_dock = $dock;
				$min = $tmp;
			}
		}

		$event = new ArriveEvent(
			$ctx,
			$ship,
			['dock' => $target_dock, 'known' => $cnt]
		);

		$event->run();

		return $event;
	}
}
