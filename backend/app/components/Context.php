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

class Context
{
	const MAX_SLOTS = 60;

	public $db;
	public $pdo;
	public $redis;
	public $redisKey;
	public $ship;
	public $listenerReady;
	public $slot;
	public $tick;

	public $factoryMethod;
	/** @property EventResponseFormatter $responseFormatter */
	public $responseFormatter;
	/** @property EventMessage $messages */
	public $messages;
	/** @property ChanceProvider $chanceProvider */
	public $chanceProvider;
	/** @property ZoneEvent $zoneEvents */
	public $zoneEvents;
	/** @property ServerVar $serverVars */
	public $serverVars;
	/** @property CommandFactory $commandFactory */
	public $commandFactory;
	/** @property DockMap $docks */
	public $docks;
	/** @property MobMap $mobs */
	public $mobs;
	/** @property ShipMap $ships */
	public $ships;
	/** @property BattleEngine $battles */
	public $battles;
	/** @property ResponseMap $responses */
	public $responses;
	/** @property CooldownMap $cds */
	public $cds;
	/** @property UserDock $userDock */
	public $userDock;

	private $_processed_ships;
	public $processed;
	
	private $_updatedShips;

	private $_insertLogSth;

	public function __construct($db)
	{
		$this->db = $db;
		$this->redis = $db->getRedis();
		$pdo = $db->getPdo();
		$this->pdo = $pdo;
		$this->_insertLogSth = $pdo->prepare('insert into game_log(date_db, type, user_id, msg) values(now(), :type, :uid, :msg)');
		$this->slot = 0;
		$this->_updatedShips = [];
	}

	public function reset()
	{
		$this->responses->clear();
		$this->_processed_ships = [];
		$this->processed = 0;
		$this->ship = null;
		$this->listenerReady = $this->redis->get('listener') === '0';
		$this->redis->set('listener', 1);

		//echo "*** reset listener ready: " . $this->listenerReady . "\n";
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

	function processRequests()
	{
		$request = $this->getRequest();
		$result = null;

		if ($request) {
			echo "*** request {$this->ship->uid}: $request\n";
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
						't=' . Event::TYPE_ERROR . '&m=* Bad command: ' . $request
					);
				}
			}
		}

		if ($result) {
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
		$lang = 'en';

		return $this->chanceProvider->getItem(
			$this->messages->getMessages($lang, $zone, $mode, $type),
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

	public function reload()
	{
		$this->messages->reload();
		$this->zoneEvents->reload();
		$this->serverVars->reload();
		$this->docks->reload();
		$this->mobs->reload();
		
		$this->chanceProvider->reset();
		$this->messages->registerChances($this->chanceProvider);
		$this->zoneEvents->registerChances($this->chanceProvider);
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
		$finalize = [];
		foreach($events as $event) {
			$uid = $event->model->uid;
			$finalize[$uid] = true;
			/** @todo bulk insert */
//			if ($event->msg && $event->msg != 'null') {
//				$uid = $event->model->uid;
//				$this->_insertLogSth->bindValue(':uid', $uid);
//				$this->_insertLogSth->bindValue(':type', $event->type);
//				$this->_insertLogSth->bindValue(':msg', $event->msg);
//				if (!$this->_insertLogSth->execute()) {
//					fwrite(STDERR, "could not save log for uid=$uid\n");
//				}
//			}
			
			if ($this->isPlayerReady($uid)) {
				$resp = $this->responseFormatter->formatEvent($event);
				//echo "*** resp: $resp, listener ready: {$this->listenerReady}\n";
				if ($this->listenerReady) {
					//echo "*** listener ready\n";
					//echo "*** add response\n";
					$this->responses->addResponse($uid, $resp);
				}
			}
		}

		foreach($finalize as $uid => $val) {
			$this->finalize($uid);
		}
	
		if ($this->responses->hasResponses()) {
			$allresponses = $this->responses->getResponses();
			foreach($allresponses as $uid => $responses) {
				$response = $this->responseFormatter->formatResponse($this, $uid, $responses);
				$this->redis->rpush(
					'responses', $response //$this->responseFormatter->formatResponse($this, $uid, $responses)
				);
				echo date('m-d H:i:s') . " u:$response\n";
			}
			$this->responses->clear();
		}
	}

	public function finalize($uid)
	{
		$ship = $this->ships->get($uid);
		$interval = $this->getInterval($ship->zone);
		$new_slot = $this->getIntervalSlot($interval);
		switch($ship->zone) {
			case Zone::ZONE_BATTLE:
				$this->ships->removeFromSlot($ship);
				break;
			case Zone::ZONE_EXPLORE:
				$this->ships->addToHop($ship);
			case Zone::ZONE_DOCK:
			case Zone::ZONE_DEAD:
				$this->ships->updateSlot($ship, $new_slot);
		}

		$this->_updatedShips[$uid] = true;
		//$this->ships->save($ship);
	}
	
	public function saveShips($cnt)
	{
		$ucnt = count($this->_updatedShips);
		if ($cnt > $ucnt) {
			$cnt = $ucnt;
		}
		$i = $cnt;
		while($i) {
			$i--;
			$uid = array_key_first($this->_updatedShips);
			$ship = $this->ships->get($uid);
			$this->ships->save($ship);
			unset($this->_updatedShips[$uid]);
		}

		return $cnt;
	}
}
