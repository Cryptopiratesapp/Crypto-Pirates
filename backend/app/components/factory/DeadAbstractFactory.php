<?php
namespace app\components\factory;

use app\components\state\DeadState;
use app\components\strategy\DeadStrategy;
use app\events\Event;

class DeadAbstractFactory
{
	private $_eventFactory = null;
	private $_state;
	private $_types = [Event::TYPE_ARRIVE];
	private $_params = [];

	public function __construct($ctx)
	{
		$this->_eventFactory = new EventFactory($ctx, $this->_types, $this->_params);
		$this->_state = new DeadState($ctx, new DeadStrategy($ctx, $this->_eventFactory));
	}
	
	public function getState($ctx)
	{
		return $this->_state;
	}

}
