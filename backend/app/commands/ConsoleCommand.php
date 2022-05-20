<?php
namespace app\commands;

use app\components\Context;
use app\components\UserNftHelper;
use app\events\ArriveEvent;
use app\events\Event;
use app\models\Actor;
use app\models\Battle;
use app\models\Console;
use app\models\Zone;

class ConsoleCommand extends Command
{
	/** @param Context $ctx */
	public function run($ctx, & $params = null)
	{
		$cmd = $params[0];
		switch ($cmd) {
			case Console::SET:
				return $this->_set($ctx->ship, $params);
			case Console::HOP:
				return $this->_hop($ctx, $params);
			case Console::DOCK:
				return $this->_dock($ctx, $params);
			case Console::FOE:
				return $this->_foe($ctx, $params);
			case Console::EVT:
				return $this->_evt($ctx, $params);
			case Console::NFT:
				return $this->_nft($ctx, $params);
			default:
				return $this->result('ERROR: command not found: ' . $cmd);
		}
	}
	
	private function _nft($ctx, $params)
	{
		$mode = $params[1];
		$ship = $ctx->ship;

		if ($mode == 'add') {
			if ($ship->level > 9) {
				return $this->result("ship level is 10, cannot install more nfts");
			}

			$grade = $params[2];
			$nft_id = $params[3];	
			if (UserNftHelper::add($ctx->db, $ship, $grade, $nft_id)) {
				return $this->result("installed nft grade $grade id $nft_id");
			}

			return $this->result("failed to install nft grade $grade id $nft_id");
		} else if ($mode == 'del') {
			$nft_id = $params[2];
			if (UserNftHelper::remove($ctx->db, $ship, $nft_id)) {
				return $this->result("removed nft id $nft_id");
			}
			
			return $this->result("failed to remove nft grade $grade id $nft_id");
		}

		return $this->result("ERROR: wrong nft command: $mode");
	}

	private function _set($model, $params, $base = 1)
	{
		$attribute = $params[$base];
		$val = $params[$base + 1];
		$mode = 0;
		if ($val[0] == '+') {
			$mode = 1;
			$val = (int) substr($val, 1);
		} else if ($val[0] == '-') {
			$mode = -1;
			$val = (int) substr($val, 1);
		} else {
			$val = (int) $val;
		}
		if (!$mode) {
			$model->$attribute = $val;
		} else if ($mode > 0) {
			$model->$attribute += $val;
		} else {
			$model->$attribute -= $val;
			if ($model->$attribute < 0) {
				$model->$attribute = 0;
			}
		}

		$target = $base == 1 ? 'self' : 'foe';

		return $this->result("$target $attribute is now {$model->$attribute}");
	}

	/**
	 * 
	 * @param Context $ctx
	 * @param type $params
	 * @return type
	 */
	public function _hop($ctx, $params)
	{
		$ship = $ctx->ship;
		if ($ship->zone !== Zone::ZONE_EXPLORE) {
			return $this->result('not in navigation zone');
		}
		
		$hop = (int) $params[1];
		if ($hop < 0) {
			return $this->result('hop cannot be negative');
		}
		
		if ($ctx->docks->exists($hop)) {
			return $this->result('cannot hop to dock position, use dock command');
		}
		
		$ship->hops = $hop;
		
		return $this->result("ship hop is now $hop");
	}

	/**
	 * 
	 * @param Context $ctx
	 * @param type $params
	 * @return type
	 */
	public function _dock($ctx, $params)
	{
		$ship = $ctx->ship;
		if ($ship->zone !== Zone::ZONE_EXPLORE) {
			return $this->result('not in navigation zone');
		}
		
		$name = $params[1];
		
		$docks = $ctx->docks->getList();
		foreach($docks as $dock) {
			if ($dock->name == $name) {
				$evt = new ArriveEvent($ctx, $ctx->ship, ['dock' => $dock]);
				$evt->run();

				return new Result($evt, null);
			}
		}
		
		return $this->result("dock $name not found");
	}

	/**
	 * @param Context $ctx
	 * @param type $params
	 * @return type
	 */
	public function _foe($ctx, $params)
	{
		$ship = $ctx->ship;
		if ($ship->zone !== Zone::ZONE_BATTLE) {
			return $this->result('not in battle zone');
		}
		
		/** @var Battle $battle */
		$battle = $ctx->battles->get($ship->battle_id);

		$enemy = $battle->getEnemy(Actor::TYPE_PLAYER, $ship->uid);

		return $this->_set($enemy, $params, 2);
	}

	public function _evt($model, $params)
	{
		
		if ($model->zone !== Zone::ZONE_EXPLORE) {
			return $this->result('not in navigation zone');
		}
	}

	public function _spawn($model, $params)
	{
		if ($model->zone !== Zone::ZONE_EXPLORE) {
			return $this->result('not in navigation zone');
		}
	}

	public function validate($ctx)
	{
		return true;
	}
	
	public function result($msg)
	{
		return new Result(
			null,
			't=' . Event::TYPE_INFO . '&m=~ ' . $msg
		);
	}	
}
