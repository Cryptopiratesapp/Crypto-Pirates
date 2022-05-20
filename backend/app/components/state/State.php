<?php
namespace app\components\state;

abstract class State
{
	/** @property \app\components\Context $ctx */
	protected $_ctx;
	
	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
	}

	public function getEvents()
	{
		$event = $this->update();
		$events = [$event];
		while($event->chainEvent) {
			$event = $event->chainEvent;
			$event->run();
			$events[] = $event;
		}

		return $events;
	}
	
	public abstract function update();
}
