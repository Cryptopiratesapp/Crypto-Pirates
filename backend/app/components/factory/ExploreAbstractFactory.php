<?php
namespace app\components\factory;

use app\components\state\ExploreState;
use app\components\strategy\ExploreStrategy;
use app\events\Event;

class ExploreAbstractFactory
{
	private $_ctx = null;
	private $_eventFactory = null;
	private $_state = null;
	private $_types = [
		Event::TYPE_DEFAULT, Event::TYPE_ENCOUNTER,
		Event::TYPE_LOSS_GOLD, Event::TYPE_GAIN_GOLD, Event::TYPE_LOSS_RES, Event::TYPE_GAIN_RES
	];
	private $_params = [];


	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
		$this->_eventFactory = new EventFactory($ctx, $this->_types, $this->_params);
		$this->_state = new ExploreState($ctx, new ExploreStrategy($ctx, $this->_eventFactory));
	}
	
	public function getState($ctx)
	{
		return $this->_state;
	}

}
