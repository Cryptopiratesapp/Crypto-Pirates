<?php
namespace app\components\factory;

use app\events\{
	Event, DefaultEvent, SellEvent, BuyEvent, TradeEvent,
	GainGoldEvent, GainResEvent, LossGoldEvent, LossResEvent,
	ArriveEvent, DepartEvent, EncounterEvent,
	AttackEvent, MissEvent, EvadeEvent, RepairEvent, DamageEvent,
	WinEvent, LoseEvent, FleeEvent, FleeFailEvent, AutoRepairEvent,
	WormholeEvent, MeteorEvent, PvpAssaultEvent//, PvpIncomingEvent
};

class EventFactory
{
	private $_eventTypes;
	private $_params;
	/** @property \app\components\Context $_ctx */
	private $_ctx;

	public function __construct($ctx, $eventTypes, $params)
	{
		$this->_ctx = $ctx;
		$this->_eventTypes = $eventTypes;
		$this->_params = $params;
	}
	
	public function getEvent($eventType = null)
	{
		if (!$eventType) {
			$eventType = $this->_ctx->getEvent();
		}

		if (!$eventType) {
			return null;
		}

		switch($eventType) {
			case Event::TYPE_DEFAULT: return new DefaultEvent($this->_ctx);
			case Event::TYPE_AUTOREPAIR: return new AutoRepairEvent($this->_ctx);	
			//case Event::TYPE_SELL: return new SellEvent($this->_ctx);
			//case Event::TYPE_BUY: return new BuyEvent($this->_ctx);
			//case Event::TYPE_TRADE: return new TradeEvent($this->_ctx);
			case Event::TYPE_GAIN_GOLD: return new GainGoldEvent($this->_ctx);
			case Event::TYPE_GAIN_RES: return new GainResEvent($this->_ctx);
			case Event::TYPE_LOSS_GOLD: return new LossGoldEvent($this->_ctx);
			case Event::TYPE_LOSS_RES: return new LossResEvent($this->_ctx);
			//case Event::TYPE_ARRIVE: return new ArriveEvent($this->_ctx);
			//case Event::TYPE_DEPART: return new DepartEvent($this->_ctx);
			case Event::TYPE_ENCOUNTER: return new EncounterEvent($this->_ctx);
			//case Event::TYPE_ATTACK: return new AttackEvent($this->_ctx);
			//case Event::TYPE_MISS: return new MissEvent($this->_ctx);
			//case Event::TYPE_EVADE: return new EvadeEvent($this->_ctx);
			//case Event::TYPE_DAMAGE: return new DamageEvent($this->_ctx);
			//case Event::TYPE_REPAIR: return new RepairEvent($this->_ctx);
			//case Event::TYPE_WIN: return new WinEvent($this->_ctx);
			//case Event::TYPE_LOSE: return new LoseEvent($this->_ctx);
			//case Event::TYPE_FLEE: return new FleeEvent($this->_ctx);
			//case Event::TYPE_FLEE_FAIL: return new FleeFailEvent($this->_ctx);
			case Event::TYPE_WORMHOLE: return new WormholeEvent($this->_ctx);
			case Event::TYPE_METEOR_RAIN: return new MeteorEvent($this->_ctx);
			case Event::TYPE_PVP_ASSAULT: return new PvpAssaultEvent($this->_ctx);
			//case Event::TYPE_PVP_INCOMING: return new PvpIncomingEvent($this->_ctx);
		}
		
		die('ERROR: factory event type out of range: ' . $eventType);
	}

}
