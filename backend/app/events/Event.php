<?php
namespace app\events;

use app\components\Context;
use app\models\Ship;

abstract class Event
{
	public const TYPE_ERROR = 0;
	public const TYPE_INFO = 1;
	public const TYPE_DEFAULT = 2;
	public const TYPE_MODE = 3;
	public const TYPE_STATE = 4;

	public const TYPE_DEPART = 20;
	public const TYPE_ARRIVE = 21;
	public const TYPE_REPAIR = 22;
	public const TYPE_AUTOREPAIR = 23;
	public const TYPE_NAV = 25;
	public const TYPE_FULL_REPAIR = 26;

	public const TYPE_SELL = 50;
	public const TYPE_BUY = 51;
	public const TYPE_TRADE = 52;
	public const TYPE_GAIN_GOLD = 53;
	public const TYPE_GAIN_RES = 54;
	public const TYPE_LOSS_GOLD = 55;
	public const TYPE_LOSS_RES = 56;
	public const TYPE_OVERLOAD = 57;

	public const TYPE_ENCOUNTER = 100;
	public const TYPE_WIN = 101;
	public const TYPE_LOSE = 102;
	public const TYPE_FLEE = 103;
	public const TYPE_ATTACK = 104;
	public const TYPE_MISS = 105;
	public const TYPE_EVADE = 106;
	public const TYPE_DAMAGE = 108;
	public const TYPE_USE = 109;
	public const TYPE_VOLLEY_SHOT = 110;
	public const TYPE_FLEE_FAIL = 111;
	public const TYPE_ROUT = 112;

	public const TYPE_WORMHOLE = 200;
	public const TYPE_METEOR_RAIN = 201;

	public const TYPE_PVP_ASSAULT = 300;
	public const TYPE_PVP_INCOMING = 301;

	public $chainEvent = null;
	public $type;
	public $params = [];
	public $args = [];
	public $msg;
	public $model;
	/** @property Context $ctx */
	public $ctx;
	public $wrapper;
	public $uid;
	
	/**
	 * @param Context $ctx
	 * @param Ship $model
	 * @param array $args
	 */
	public function __construct($ctx, $model = null, $args = null)
	{
		$this->ctx = $ctx;
		if (!$model) {
			$this->model = $ctx->ship;
		} else {
			$this->model = $model;
		}
		if ($args) {
			$this->args = $args;
		}
	}

	public function transfer($evt)
	{
		if ($this->wrapper) {
			$evt->wrapper = $this->wrapper;
			$evt->model = $this->model;
			$evt->args = $this->args;
			$this->wrapper->event = $evt;

			return $evt->run();
		}
		
		return false;
	}

	public abstract function run();

	public static function getIds()
	{
		return [
			self::TYPE_ERROR => 'error',
			self::TYPE_INFO => 'info',
			self::TYPE_DEFAULT => 'default',
			self::TYPE_MODE => 'mode',
			self::TYPE_STATE => 'state',

			self::TYPE_DEPART => 'depart',
			self::TYPE_ARRIVE => 'arrive',
			self::TYPE_REPAIR => 'repair',
			self::TYPE_AUTOREPAIR => 'autorepair',
			self::TYPE_NAV => 'nav',

			self::TYPE_SELL => 'sell',
			self::TYPE_BUY => 'buy',
			self::TYPE_TRADE => 'trade',
			self::TYPE_GAIN_GOLD => 'gain_gold',
			self::TYPE_GAIN_RES => 'gain_res',
			self::TYPE_LOSS_GOLD => 'loss_gold',
			self::TYPE_LOSS_RES => 'loss_res',
			self::TYPE_OVERLOAD => 'overload',

			self::TYPE_ENCOUNTER => 'encounter',
			self::TYPE_WIN => 'win',
			self::TYPE_LOSE => 'lose',
			self::TYPE_FLEE => 'flee',
			self::TYPE_FLEE_FAIL => 'flee_fail',
			self::TYPE_ATTACK => 'attack',
			self::TYPE_MISS => 'miss',
			self::TYPE_EVADE => 'evade',
			self::TYPE_DAMAGE => 'damage',
			self::TYPE_USE => 'use',
			self::TYPE_VOLLEY_SHOT => 'volley_shot',

			self::TYPE_WORMHOLE => 'wormhole',
			self::TYPE_METEOR_RAIN => 'meteor_rain',
			self::TYPE_PVP_ASSAULT => 'pvp_assault',
			self::TYPE_PVP_INCOMING => 'pvp_incoming',
		];
	}
}
