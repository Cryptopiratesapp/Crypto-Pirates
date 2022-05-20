<?php
namespace app\components\strategy;

use app\events\EventWrapper;

class DockStrategy
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
		$evt = $this->_eventFactory->getEvent();
		$this->_wrapper->run($evt);

		return $this->_wrapper->event;
	}
}
