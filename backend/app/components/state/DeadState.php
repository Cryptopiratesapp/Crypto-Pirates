<?php
namespace app\components\state;

class DeadState extends State
{
	private $_strategy;
	
	public function __construct($ctx, $strategy)
	{
		parent::__construct($ctx);
		$this->_strategy = $strategy;
	}

	public function update()
	{
		return $this->_strategy->run();
	}
}
