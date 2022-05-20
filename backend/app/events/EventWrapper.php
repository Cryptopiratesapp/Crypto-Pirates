<?php

namespace app\events;

class EventWrapper
{
	public $event;

	public function run($event)
	{
		$event->wrapper = $this;
		$this->event = $event;

		return $event->run();
	}
}
