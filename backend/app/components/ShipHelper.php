<?php
namespace app\components;

class ShipHelper
{
	public static function createShip($db, $uid)
	{
		$sth = $db->getPdo()->prepare('select * from protoship where level=0');
		$proto = DbHelper::findOne($sth);
		if (empty($proto)) {
			return false;
		}

		$sth = $db->getPDO()->prepare(
			'insert into ship('
			. 'user_id, level, slots, num, title, active, hp, max_hp, def, max_def, res, max_res, min_atk, max_atk, spd, acc, man, abs'
			. ') values('
			. ':uid, 1, :slots, 1, :title, 1, :hp, :max_hp, :def, :max_def, :res, :max_res, :min_atk, :max_atk, :spd, :acc, :man, :abs)'
		);

		$sth->bindValue(':uid', $uid);
		$sth->bindValue(':title', static::_generate_ship_name());
		$sth->bindValue(':hp', $proto['hp']);
		$sth->bindValue(':max_hp', $proto['hp']);
		$sth->bindValue(':def', $proto['def']);
		$sth->bindValue(':max_def', $proto['def']);
		$sth->bindValue(':res', $proto['res']);
		$sth->bindValue(':max_res', $proto['res']);
		$sth->bindValue(':min_atk', $proto['min_atk']);
		$sth->bindValue(':max_atk', $proto['max_atk']);
		$sth->bindValue(':spd', $proto['spd']);
		$sth->bindValue(':acc', $proto['acc']);
		$sth->bindValue(':man', $proto['man']);
		$sth->bindValue(':abs', $proto['abs']);
		$sth->bindValue(':slots', $proto['slots']);

		if (!$sth->execute()) {
			return false;
		}

		$db->getRedis()->rpush('updated_ships', $uid);
		
		return true;
	}

	private static function _generate_ship_name()
	{
		$adj_m = [
			'Black', 'Red', 'Blue', 'White', 'Azure', 'Sky', 'Hell', 'Sea', 'Jolly',
			'Fearless', 'Headlong', 'Pompous', 'Loyal', 'Shy', 'Windy',
			'Fearsome', 'Atomic', 'Cowardly', 'Brave', 'Cunning', 'Greedy', 'Stern', 'Gold',
			'Hot', 'Unstoppable', 'Flying', 'Careless', 'Winged', 'Chubby', 'Immortal',
			'Lazy', 'Lucky', 'Speedy', 'Smart', 'Dangerous', 'Good', 'Puffing', 'Stingy',
			'Skinny', 'Sad', 'Courageous', 'Bony', 'Great', 'Mighty'
		];

		$noun_m = [
			'Baby', 'Dragon', 'Thug', 'Fatman', 'Pirate', 'Swirl', 'Wind', 'Strike', 'Hustler',
			'Automotive', 'Winner', 'Scout', 'Wanderer', 'Gramophone', 'Racer', 'Hornet', 'Ark',
			'Honey', 'Tomcat', 'Capricorn', 'Bunny', 'Tracer', 'Pursuer', 'Chief', 'Teapot',
			'Wolf', 'Eagle', 'Snake', 'Fox', 'Defender', 'Thief', 'Gambler', 'Crab Eater', 'Slayer',
			'Hawk', 'Crocodile', 'Omnibus', 'Lord', 'Toad'
		];

		$noun_f = [
			'Baby', 'Dragoness', 'Bandit', 'Fatty', 'Piratess', 'Widow', 'Goddess', 'Worker', 'Hustler',
			'Automotive', 'Winner', 'Scoutess', 'Wanderess', 'Thing', 'Racer', 'Bee', 'Vessel',
			'Cutey', 'Cat', 'Nanny', 'Bunny', 'Tracer', 'Death', 'Beauty', 'Song', 'Sprout',
			'Bitchwolf', 'Eagle', 'Snake', 'Vixen', 'Protector', 'Thief', 'Chan', 'Joy', 'Killer',
			'Falcon', 'Mistress', 'Saucer'
		];

		$adj = &$adj_m;
		$noun = &$noun_m;
		if (mt_rand(0, 1000) < 500) {
			$noun = &$noun_f;
		}

		return $adj[mt_rand(0, count($adj) - 1)] . ' ' . $noun[mt_rand(0, count($noun) - 1)];
	}
}
