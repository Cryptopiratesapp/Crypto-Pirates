<?php
namespace app\components;

use app\commands\Command;
use app\commands\CommandFactory;
use app\commands\Result;
use app\events\Event;
use app\models\EventMessage;
use app\models\ServerVar;
use app\models\Zone;
use app\models\ZoneEvent;

class TestContext {
	const MAX_SLOTS = 60;

	public $db;
	public $pdo;
	public $redis;
	public $redisKey;
	public $ship;
	public $listenerReady;
	public $slot;

	public $factoryMethod;
	/** @property EventResponseFormatter $responseFormatter */
	public $responseFormatter;
	/** @property EventMessage $messages */
	public $messages;
	/** @property ChanceProvider $chanceProvider */
	public $chanceProvider;
	/** @property MonsterMap $mobs */
	public $mobs;
	/** @property ZoneEvent $zoneEvents */
	public $zoneEvents;
	/** @property ServerVar $serverVars */
	public $serverVars;
	/** @property CommandFactory $commandFactory */
	public $commandFactory;
	/** @property ShipMap $ships */
	public $ships;
	/** @property BattleEngine $battles */
	public $battles;
	/** @property ResponseMap $responses */
	public $responses;

	private $_processed_ships;
	public $processed;
	
	private $_insertLogSth;

	public function __construct($db)
	{
		$this->db = $db;
		$this->redis = $db->getRedis();
		$pdo = $db->getPdo();
		$this->pdo = $pdo;
		$this->_insertLogSth = $pdo->prepare('insert into game_log(date_db, type, user_id, msg) values(now(), :type, :uid, :msg)');
		$this->slot = 0;
	}

	public function reset()
	{
		$this->responses->clear();
		$this->_processed_ships = [];
		$this->processed = 0;
		$this->ship = null;
		$this->listenerReady = $this->redis->get('listener') === '0';
	}

	public function getInterval($zone)
	{
		return $this->serverVars->getInt('turn_time_' . $zone);
	}
	
	public function getRemainingInterval($ship)
	{
		$diff = $ship->slot - $this->slot;
		// ships are processed regularly, so their slots are always actual
		if ($diff < 0) {
			// ship is behind
			$diff = self::MAX_SLOTS - $this->slot + $ship->slot;
		}

		return $diff;
	}

	public function nextSlot()
	{
		$this->slot++;
		if ($this->slot == self::MAX_SLOTS) {
			$this->slot = 0;
		}
	}

	public function getIntervalSlot($interval)
	{
		$slot = $this->slot + $interval;
		if ($slot >= self::MAX_SLOTS) {
			$slot -= self::MAX_SLOTS;
		}

		return $slot;
	}

	public function setShip($ship)
	{
		$this->ship = $ship;
		$this->redisKey = "u:{$ship->uid}";
	}
	
	public function getRequest()
	{
		return $this->redis->hget($this->redisKey, 'request');
	}
	
	public function clearRequest()
	{
		$this->redis->hset($this->redisKey, 'request', null);
	}

	function process_requests()
	{
		$request = $this->getRequest();
		$result = null;

		if ($request) {
			$this->clearRequest();
			if ($request !== Command::TYPE_NOP) {
				$chunks = explode(' ', $request);
				$type = array_shift($chunks);
				$cmd = $this->commandFactory->getCommand($type);
				if ($cmd && $cmd->validate($this)) {
					$result = $cmd->run($this, $chunks);
				} else {
					$result = new Result(
						null,
						't=' . Event::TYPE_ERROR . '&m=Неверная команда: ' . $request
					);
				}
			}
		}

		if ($result) {
			if ($result->evt) {
				$result->evt->run();
			}
			if ($result->response) {
				$this->responses->addResponse($this->ship->uid, $result->response);
			}
		}
	
		return $result;
	}
	
	public function isPlayerReady($uid)
	{
		return $this->redis->hget("u:$uid", 'sock') !== false
			&& $this->redis->hget("u:$uid", 'ready') !== false;
	}

	public function getMessage($zone, $mode, $type)
	{
		return $this->chanceProvider->getItem(
			$this->messages->getMessages($zone, $mode, $type),
			ChanceProvider::CP_POOLED
		);
	}

	public function getEvent()
	{
		return $this->chanceProvider->getItem(
			$this->zoneEvents->getEvents($this->ship->zone, $this->ship->mode),
			ChanceProvider::CP_UNPOOLED
		); 
	}
	
	public function getActualZone($ship)
	{
		return $ship->zone;
	}

	public function getCd()
	{
		return null;
	}

	public function reload()
	{
		$this->messages->reload();
		$this->zoneEvents->reload();
		$this->serverVars->reload();
		$this->mobs->reload();
	}

	public function isProcessed($uid)
	{
		return isset($this->_processed_ships[$uid]);
	}
	
	public function setProcessed($uid)
	{
		$this->_processed_ships[$uid] = true;
	}

	public function flush(& $events)
	{
		foreach($events as $event) {
			$uid = $event->model->uid;
			$resp = $this->responseFormatter->formatEvent($event);
			$this->responses->addResponse($uid, $resp);
		}
	}
}
