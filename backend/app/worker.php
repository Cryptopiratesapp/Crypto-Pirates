<?php
require 'base/Db.php';

//require 'models/Protoship.php';
require 'models/Ship.php';
require 'models/Mode.php';
require 'models/Zone.php';
require 'models/EventMessage.php';
require 'models/ZoneEvent.php';
require 'models/ServerVar.php';
require 'models/Mob.php';
require 'models/Dock.php';
require 'models/Battle.php';
require 'models/Actor.php';
require 'models/Console.php';

require 'components/Calc.php';
require 'components/ChanceProvider.php';
require 'components/DbHelper.php';
require 'components/UserNftHelper.php';
require 'components/EventResponseFormatter.php';
require 'components/Context.php';
require 'components/DockMap.php';
require 'components/ShipMap.php';
require 'components/MobMap.php';
require 'components/BattleEngine.php';
require 'components/ResponseMap.php';
require 'components/CooldownMap.php';
require 'components/UserDock.php';

require 'commands/Command.php';
require 'commands/Result.php';
require 'commands/CommandFactory.php';
require 'commands/StateCommand.php';
require 'commands/ModeCommand.php';
require 'commands/RepairCommand.php';
require 'commands/NavCommand.php';
require 'commands/VolleyShotCommand.php';
require 'commands/FleeCommand.php';
require 'commands/ConsoleCommand.php';

require 'events/Event.php';
require 'events/EventWrapper.php';
require 'events/StateEvent.php';
require 'events/ModeEvent.php';
require 'events/DefaultEvent.php';
require 'events/ArriveEvent.php';
require 'events/DepartEvent.php';
require 'events/NavEvent.php';

require 'events/EncounterEvent.php';
require 'events/GainGoldEvent.php';
require 'events/LossGoldEvent.php';
require 'events/GainResEvent.php';
require 'events/LossResEvent.php';
require 'events/OverloadEvent.php';
require 'events/AttackEvent.php';
require 'events/VolleyShotEvent.php';
require 'events/DamageEvent.php';
require 'events/RepairEvent.php';
require 'events/RepairEventFail.php';
require 'events/AutoRepairEvent.php';
require 'events/FullRepairEvent.php';
require 'events/EvadeEvent.php';
require 'events/MissEvent.php';
require 'events/WinEvent.php';
require 'events/FleeEvent.php';
require 'events/FleeFailEvent.php';
require 'events/ErrorEvent.php';
require 'events/WormholeEvent.php';
require 'events/MeteorEvent.php';
require 'events/PvpAssaultEvent.php';
require 'events/PvpIncomingEvent.php';
require 'events/PassiveDamageEvent.php';
require 'events/PassiveLoseEvent.php';
require 'events/RoutEvent.php';

require 'components/factory/EventFactory.php';
require 'components/factory/FactoryMethod.php';
require 'components/factory/ExploreAbstractFactory.php';
require 'components/factory/DockAbstractFactory.php';
require 'components/factory/DeadAbstractFactory.php';

require 'components/strategy/ExploreStrategy.php';
require 'components/strategy/PlayerBattleStrategy.php';
require 'components/strategy/MobBattleStrategy.php';
require 'components/strategy/DockStrategy.php';
require 'components/strategy/DeadStrategy.php';

require 'components/state/State.php';
require 'components/state/ExploreState.php';
require 'components/state/DockState.php';
require 'components/state/DeadState.php';

require 'components/ShipHelper.php';

use app\base\Db;
use app\commands\CommandFactory;
use app\commands\StateCommand;
use app\components\BattleEngine;
use app\components\ChanceProvider;
use app\components\Context;
use app\components\CooldownMap;
use app\components\DockMap;
use app\components\EventResponseFormatter;
use app\components\factory\FactoryMethod;
use app\components\MobMap;
use app\components\ResponseMap;
use app\components\ShipMap;
use app\components\strategy\MobBattleStrategy;
use app\components\strategy\PlayerBattleStrategy;
use app\components\UserDock;
use app\models\EventMessage;
use app\models\ServerVar;
use app\models\Zone;
use app\models\ZoneEvent;

$config = require '../config.php';
$db = new Db($config['db']);

$ctx = new Context($db);
$ctx->responses = new ResponseMap();
$ctx->cds = new CooldownMap();
$ctx->factoryMethod = new FactoryMethod($ctx);
$ctx->commandFactory = new CommandFactory();
$ctx->responseFormatter = new EventResponseFormatter();
$ctx->chanceProvider = new ChanceProvider();
$ctx->userDock = new UserDock($ctx->db->getPdo());
$ctx->messages = new EventMessage($db);
$ctx->zoneEvents = new ZoneEvent($db);
$ctx->serverVars = new ServerVar($db);
$ctx->docks = new DockMap($ctx);
$ctx->mobs = new MobMap($ctx);
// ^^^ $ctx->reload() loads all this stuff ^^^
$ctx->reload();

$ctx->ships = new ShipMap($ctx);
$ctx->battles = new BattleEngine(
	$ctx,
	new PlayerBattleStrategy($ctx),
	new MobBattleStrategy($ctx)
);

$reload_cnt = 0;
$cnt = $ctx->ships->load();
echo "*** loaded $cnt ships\n";

$cnt = $ctx->battles->load();
echo "*** loaded $cnt battles\n";

$mem = memory_get_usage();
echo "*** total allocated memory: " . $mem . "\n";

$time = time();
$ctx->tick = 0;

while(true) {

	while($uid = $ctx->redis->lpop('updated_ships')) {
		echo "*** reloading $uid\n";
		$ctx->ships->reload($uid);
	}

	$ctx->reset();

	process_state_requests($ctx);
	$ships = $ctx->ships->getAtSlot($ctx->slot);
	$cnt = count($ships);

	//echo "got $cnt at slot $ctx->slot\n";

	foreach ($ships as $uid => $val) {

		// if ship was indirectly processed while processing other ship
		if ($ctx->isProcessed($uid)) {
			continue;
		}

		$ship = $ctx->ships->get($uid);
		if ($ship->zone == Zone::ZONE_BATTLE) {
			die("battle ship must not be in ship map\n");
		}
		$ctx->processed++;

		$ctx->setShip($ship);
		if ($ship->zone == Zone::ZONE_EXPLORE) {
			$ctx->ships->removeFromHop($ship);
		}

		$result = $ctx->processRequests();
		$events = [];
		if ($result) {
			if ($result->evt) {
				$events[] = $result->evt;
			}
		} else {
			$state = $ctx->factoryMethod->getStateFactory($ship->zone)->getState($ctx);
			$events = $state->getEvents();
		}

		$ctx->flush($events);
		$ctx->cds->update($ship->uid);
	}

//	if (++$reload_cnt > 10) {
//		$ctx->reload();
//		echo '*** reload mem: ' . $mem . ' / ' . memory_get_usage() . "\n";
//		$reload_cnt = 0;
//	}
	
	$ctx->battles->update();

	while(time() < $time) {
		$cnt = $ctx->saveShips(200);
		if (!$cnt) {
			break;
		}
	}
	
	$ctx->nextSlot();
	$ctx->redis->set('worker_updated', time());
	
	$t = microtime(true);
	if ((int) $t < $time) {
		$diff = floor(($time - $t) * 1000000);
		//echo "usleep $diff\n";
		usleep($diff);
	}

	$time++;
	$ctx->tick++;
}

function et(& $mt)
{
	$m = microtime(true);
	$m2 = $m - $mt;
	$mt = $m;
	
	return $m2;
}

/** @param Context $ctx */
function process_state_requests($ctx)
{
	/**
	 * State request does not change ship state so ship will be processed in normal order
	 * don't put it to processed and don't save responses
	 */
	$cmd = new StateCommand();
	/** 
	 * @todo make sure no infinite requests can be stored in redis
	 * and no infinite requests are read here
	 */
	while($uid = $ctx->redis->lpop('state_requests')) {
		$ship = $ctx->ships->get($uid);
		$ctx->setShip($ship);
		echo "*** process state request for {$ship->uid}\n";
		// kill any current non-state requests because they must be invalid
		$ctx->redis->hset($ctx->redisKey, 'request', null);
		$result = $cmd->run($ctx);
		$resp = $ctx->responseFormatter->formatEvent($result->evt);
		$responses = [$resp];

		// assuming that state request has single origin ship
		// and no ship change occurs during command run
		// we can safely use current $uid in response output
		$ctx->redis->rpush(
			'responses', $ctx->responseFormatter->formatResponse($ctx, $uid, $responses)
		);
		$ctx->processed++;
		$ctx->setProcessed($ship->uid);
	}
}
