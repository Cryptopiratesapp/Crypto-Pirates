<?php
namespace app\components;

use app\events\Event;
use app\events\PassiveLoseEvent;
use app\events\RoutEvent;
use app\events\WinEvent;
use app\models\Actor;
use app\models\Battle;
use app\models\Zone;
use Exception;
use PDO;

class BattleEngine
{
	private $_battles = [];
	private $_slotMap = [];
	private $_slot = 0;
	private $_interval = 15;
	/** @property Context $_ctx */
	private $_ctx;
	private $_selectAllSth;
	private $_selectBattleSth;
	private $_insertBattleSth;
	private $_updateBattleSth;
	private $_endBattleSth;

	private $_playerStrategy;
	private $_mobStrategy;

	public function __construct($ctx, $playerStrategy, $mobStrategy)
	{
		$this->_ctx = $ctx;
		$pdo = $ctx->pdo;
		$this->_selectAllSth = $pdo->prepare('select * from battle where slot is not null');
		$this->_selectBattleSth = $pdo->prepare('select * from battle where id=:id');
		$this->_insertBattleSth = $pdo->prepare('insert into battle(slot, date_db, round, data) values(:slot, now(), :round, :data)');
		$this->_updateBattleSth = $pdo->prepare('update battle set slot=:slot, round=:round, side=:side, turn=:turn, data=:data where id=:id');
		$this->_endBattleSth = $pdo->prepare('update battle set slot=null where id=:id');

		$this->_interval = $ctx->getInterval(Zone::ZONE_BATTLE);
		$this->_slot = 0;
		
		$this->_playerStrategy = $playerStrategy;
		$this->_mobStrategy = $mobStrategy;
	}

	public function load()
	{
		$this->_battles = [];
		$this->_slotMap = [];

		$res = $this->_selectAllSth->execute();
		if (!$res) {
			throw new Exception('Error loading battles from db');
		}

		while ($data = $this->_selectAllSth->fetch(PDO::FETCH_ASSOC)) {
			$battle = new Battle($this->_ctx);
			$battle->init($data);
			$this->_battles[$battle->id] = $battle;
			$this->addToSlot($battle);
		}

		$this->_selectAllSth->closeCursor();

		return count($this->_battles);
	}

	public function get($id)
	{
		if (!isset($this->_battles[$id])) {
			return false;
		}
		return $this->_battles[$id];
	}

	public function nextSlot()
	{
		$this->_slot++;
		if ($this->_slot == $this->_interval) {
			$this->_slot = 0;
		}
	}

	public function getIntervalSlot()
	{
		// as slot space size == interval, every battle stays in the same slot after interval advance.
		// processed battles do not get processed twice so it must be safe.
		// this is stub for possible future case when not all battles have the same interval.
		return $this->_slot;
	}

	public function createBattle(& $actors)
	{
		$battle = new Battle($this->_ctx);
		$battle->slot = $this->getIntervalSlot();
		$battle->setActors($actors);
		$this->_insertBattleSth->bindValue(':slot', $battle->slot);
		$this->_insertBattleSth->bindValue(':round', $battle->round);
		$this->_insertBattleSth->bindValue(':data', $battle->serializeData());
		$this->_insertBattleSth->execute();
		$battle->id = $this->_ctx->pdo->lastInsertId();
		$this->_battles[$battle->id] = $battle;
		$this->addToSlot($battle, false);

		return $battle;
	}

	public function updateSlot($battle, $new_slot)
	{
		if ($battle->slot != $new_slot) {
			$this->removeFromSlot($battle);
			$battle->slot = $new_slot;
			$this->addToSlot($battle);
		}
	}

	public function addToSlot($battle, $status = true)
	{
		$this->_slotMap[$battle->slot][$battle->id] = $status;
	}
	
	public function removeFromSlot($battle)
	{
		unset ($this->_slotMap[$battle->slot][$battle->id]);
		if (empty($this->_slotMap[$battle->slot])) {
			unset ($this->_slotMap[$battle->slot]);
		}
	}
	
	public function getAtSlot($slot)
	{
		if (isset($this->_slotMap[$slot])) {
			return $this->_slotMap[$slot];
		}

		return [];
	}
	
	public function save($battle)
	{
		$this->_updateBattleSth->bindValue(':id', $battle->id);
		$this->_updateBattleSth->bindValue(':slot', $battle->slot);
		$this->_updateBattleSth->bindValue(':round', $battle->round);
		$this->_updateBattleSth->bindValue(':side', $battle->side);
		$this->_updateBattleSth->bindValue(':turn', $battle->turn);
		$this->_updateBattleSth->bindValue(':data', $battle->serializeData());
		$this->_updateBattleSth->execute();
	}

	public function update()
	{
		$battles = $this->getAtSlot($this->_slot);

		foreach($battles as $battle_id => $val) {
			if ($val === false) {
				// battle just created, don't process it now
				$this->_slotMap[$this->_slot][$battle_id] = true;
				continue;
			}
			/** @var Battle $battle */
			$battle = $this->get($battle_id);
			$events = [];
			$event = $this->processBattle($battle);
			if ($event) {
				$events[] = $event;
				while($event->chainEvent) {
					$event = $event->chainEvent;
					$event->run();
					$events[] = $event;
				}
			}

			if ($battle->endEvent) {
				$this->endBattle($battle);
				$actors = $battle->getActors();
				$activeActor = $battle->getActiveActor();
				$activeModel = $activeActor->model;
				$ctx = $this->_ctx;
				// battle was ended by active actor
				if ($battle->endEvent->type == Event::TYPE_ATTACK || $battle->endEvent->type == Event::TYPE_VOLLEY_SHOT) {
					// attack event was made by player, not monster
					// active actor receives win event,
					// others receive passive lose event;
					$event = new WinEvent($ctx, $activeModel, $battle->endEvent->args);
					$event->run();
					$events[] = $event;
					foreach($actors as $actor) {
						if ($actor->isPlayer && $actor->realId != $activeActor->realId) {
							$event = new PassiveLoseEvent($ctx, $actor->model, ['from' => $activeModel]);
							$event->run();
							$events[] = $event;
						}
					}
				} else if ($battle->endEvent->type == Event::TYPE_FLEE) {
					// end by flee: active actor already has flee event,
					// others receive rout event;
					foreach($actors as $actor) {
						if ($actor->isPlayer && $activeActor->id !== $actor->id) {
							$event = new RoutEvent($ctx, $actor->model, ['target' => $activeModel]);
							$event->run();
							$events[] = $event;
						}
					}
				} else if ($battle->endEvent->type == Event::TYPE_DAMAGE) {
					// end by monster damage: active actor is monster,
					// other receives passive lose event
					foreach($actors as $actor) {
						if ($actor->isPlayer) {
							$event = new PassiveLoseEvent($ctx, $actor->model, ['from' => $activeModel]);
							$event->run();
							$events[] = $event;
						}
					}
				}
			} else {
				$battle->nextTurn();
				$new_slot = $this->getIntervalSlot();
				$this->updateSlot($battle, $new_slot);
				$this->save($battle);
			}

			$this->_ctx->flush($events);
		}

		$this->nextSlot();
	}

	/**
	 * @param Battle $battle
	 */
	public function processBattle($battle)
	{
		
		$actor = $battle->getActiveActor();
		$event = null;

		if ($actor->isPlayer) {
			$event = $this->_playerStrategy->run($this->_ctx, $actor, $battle);
		} else {
			$event = $this->_mobStrategy->run($this->_ctx, $actor, $battle);
		}

		return $event;
	}

	public function endBattle($battle)
	{
		$this->removeFromSlot($battle);
		unset($this->_battles[$battle->id]);

		$this->_endBattleSth->bindValue(':id', $battle->id);
		$this->_endBattleSth->execute();
	}
}
