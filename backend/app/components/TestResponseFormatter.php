<?php
namespace app\components;

use app\events\Event;
use app\models\Zone;

class EventResponseFormatter
{
	public function formatEvent(Event $evt)
	{
		$out = null;
		if ($evt->msg && $evt->msg != 'null') {
			$out = "{$evt->msg}";
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
		$battle = $ctx->battles->get($ship->battle_id);
		$side = ord($battle->round[$battle->side]);

		$out = "side: $side hp: {$ship->hp} def: {$ship->def} res: {$ship->res}";
		foreach($responses as $resp) {
			$out .= "<br>" . $resp;
		}
		return $out;
	}
}
