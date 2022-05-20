<?php
namespace app\components\factory;

use app\components\factory\EventFactory;
use app\components\state\DockState;
use app\components\strategy\DockStrategy;
use app\events\Event;

class DockAbstractFactory
{
	private $_eventFactory = null;
	private $_state = null;
	private $_types = [Event::TYPE_AUTOREPAIR];
	private $_params = [];
	
	public function __construct($ctx)
	{
		$this->_eventFactory = new EventFactory($ctx, $this->_types, $this->_params);
		$this->_state = new DockState($ctx, new DockStrategy($ctx, $this->_eventFactory));
	}
	
	public function getState($context)
	{
		return $this->_state;
	}

}
