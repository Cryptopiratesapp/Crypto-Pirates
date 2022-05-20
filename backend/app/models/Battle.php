<?php
namespace app\models;

use app\components\Context;

class Battle
{
	public $id;
	public $slot;
	public $round;
	public $turn;
	public $side;
	public $endEvent = null;
	/**
	 * @property Context $_ctx
	 */
	private $_ctx;
	private $_actors;

	public function __construct($ctx)
	{
		$this->_ctx = $ctx;
	}
	
	public function init($data)
	{
		$this->id = $data['id'];
		$this->slot = $data['slot'];
		$this->round = $data['round'];
		$this->turn = $data['turn'];
		$this->side = $data['side'];
		$data = json_decode($data['data'], true);
		$this->_actors = [];
		$ctx = $this->_ctx;

		foreach ($data as $actordata) {
			$type = $actordata['type'];
			$id = $actordata['id'];
			$actor = null;
			if ($type == Actor::TYPE_PLAYER) {
				$actor = new Actor($type, $ctx->ships->get($id));
			} else {
				$actor = new Actor($type, $ctx->mobs->get($id));
				// update actor hp and def for mob
				$actor->hp = $actordata['hp'];
				$actor->def = $actordata['def'];
			}
			// don't use actor ids as keys
			$this->_actors[] = $actor;
		}
	}

	public function setActors($actors)
	{
		$this->_actors = $actors;
		$this->initRound();
	}
	
	public function initRound()
	{
		$this->side = 0;
		$chances = [];
		foreach($this->_actors as $pos => $actor) {
			$chances[$pos] = mt_rand(0, $actor->spd);
		}
		
		arsort($chances);
		
		$this->round = '';
		foreach($chances as $pos => $ch) {
			$this->round .= chr($pos);
		}
	}

	public function serializeData()
	{
		$data = [];
		foreach($this->_actors as $actor) {
			$data[] = $actor->serialize();
		}

		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	public function nextTurn()
	{
		$this->turn++;
		$this->side++;
		if ($this->side == count($this->_actors)) {
			$this->initRound();
		}
	}

	public function isActiveTurn($uid)
	{
		$actor = $this->getActiveActor();

		return $actor->isPlayer && $actor->realId == $uid;
	}
	
	/**
	 * 
	 * @return Actor
	 */
	public function getActiveActor()
	{
		// round is actor positions sorted in order in binary byte string
		// side is position inside round
		$actor_pos = ord($this->round[$this->side]);

		return $this->_actors[$actor_pos];
	}
	
	public function getEnemy($self_type, $self_id)
	{
		foreach($this->_actors as $actor) {
			if ($actor->type != $self_type || $actor->realId != $self_id) {
				return $actor;
			}
		}
		
		return null;
	}
	
	public function & getActors()
	{
		return $this->_actors;
	}
//	
//	public function getActorModel(& $actor)
//	{
//		$model = null;
//		if ($actor->isPlayer) {
//			$model = $this->_ctx->ships->get($actor->realId);
//		} else {
//			$model = $this->_ctx->mobs->get($actor->realId);
//		}
//		
//		return $model;
//	}
}
