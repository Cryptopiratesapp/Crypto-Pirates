<?php
namespace app\components;

use app\commands\Command;
use app\events\Event;
use app\models\Battle;
use app\models\Zone;

class EventResponseFormatter
{
	public function formatEvent(Event $evt)
	{
		$out = null;
		if ($evt->msg && $evt->msg != 'null') {
			$out = "t={$evt->type}";
			foreach ($evt->params as $key => $value) {
				$out .= "&$key=$value";
			}
			$out .= "&m={$evt->msg}";
		}
		return $out;
	}
	
	/**
	 * @param Context $ctx
	 * @param [] $responses
	 * @return string
	 */
	public function formatResponse($ctx, $uid, & $responses)
	{
		$ship = $ctx->ships->get($uid);
		$interval = $ctx->getInterval($ship->zone);
		
		$out = [
			'tick' => $ctx->tick,
			'interval' => $interval,
			'level' => $ship->level,
			'hop' => $ship->hops,
			'max_hops' => $ship->max_hops,
			'dir' => $ship->dir,
			'gold' => $ship->gold,
			'res' => $ship->res,
			'max_res' => $ship->max_res,
			'hp' => $ship->hp,
			'max_hp' => $ship->max_hp,
			'def' => $ship->def,
			'max_def' => $ship->max_def,
		];

		if ($ship->zone == Zone::ZONE_BATTLE) {
			/** @var Battle $battle */
			$battle = $ctx->battles->get($ship->battle_id);
			$out['side'] = ord($battle->round[$battle->side]);
			$cds = [];
			$cd_volley_shot = $ctx->cds->get($uid, Event::TYPE_VOLLEY_SHOT);
			if ($cd_volley_shot !== false) {
				/** 
				 * @todo: use Event type instead of Command type because shorter
				 * but needs to process in jslib to pass to client
				 */
				$cds[Command::TYPE_VOLLEY_SHOT] = $cd_volley_shot;
			}
			if ($cds) {
				$out['cd'] = $cds;
			}
		}

		$out['responses'] = & $responses;
		$response = $ship->uid . ' ' . json_encode($out, JSON_UNESCAPED_UNICODE);
		//echo "*** $response\n";
		return $response;
	}
}
