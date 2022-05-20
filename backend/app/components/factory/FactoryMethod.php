<?php
namespace app\components\factory;

use app\models\Zone;

class FactoryMethod
{
	private $_abstractFactoryCache = null;

	public function __construct($context)
	{
		$this->_abstractFactoryCache = [
			Zone::ZONE_EXPLORE => new ExploreAbstractFactory($context),
			Zone::ZONE_DOCK => new DockAbstractFactory($context),
			Zone::ZONE_DEAD => new DeadAbstractFactory($context),
		];		
	}
	
	public function getStateFactory($zone)
	{
		if (isset($this->_abstractFactoryCache[$zone])) {
			$factory = $this->_abstractFactoryCache[$zone];

			return $factory;
		}
		
		throw new \InvalidArgumentException("no abstract factory for zone $zone");
	}
}
