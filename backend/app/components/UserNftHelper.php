<?php
namespace app\components;

class UserNftHelper
{
	public static function getActiveList($db, $uid)
	{
		$sth = $db->getPdo()->prepare('select * from user_nft where user_id=:uid and active = 1');

		$sth->bindValue(':uid', $uid);
		
		return DbHelper::getList($sth);
	}
	
	public static function findOne($db, $uid, $nft_id)
	{
		$sth = $db->getPdo()->prepare('select * from user_nft where user_id=:uid and nft_id=:nft_id');
		
		$sth->bindValue(':uid', $uid);
		$sth->bindValue(':nft_id', $nft_id);

		return DbHelper::findOne($sth);
	}

	public static function update($db, $uid, $nft_id, $active)
	{
		$sth = $db->getPdo()
			->prepare('update user_nft set active=:active where user_id=:uid and nft_id=:nft_id');

		$sth->bindValue(':uid', $uid);
		$sth->bindValue(':nft_id', $nft_id);
		$sth->bindValue(':active', $active);

		return $sth->execute();
	}
		
	public static function add($db, $ship, $level, $nft_id)
	{
		$userNft = static::findOne($db, $ship->uid, $nft_id);

		if ($userNft) {
			if ($userNft['active']) {
				return true;
			} else {
				if (!static::update($db, $ship->uid, $nft_id, 1)) {
					return false;
				}
			}
		} else {
			$sth = $db->getPdo()->prepare('select * from nft where level=:level');
			$sth->bindValue(':level', $level);

			$nft = DbHelper::findOne($sth);
			if (!$sth) {
				return false;
			}

			$sth = $db->getPdo()->prepare(
				'insert into user_nft(user_id, nft_id, level, active, hp, def, res, atk, spd, acc, man)'
				. ' values(:uid, :nft_id, :level, 1, :hp, :def, :res, :atk, :spd, :acc, :man)'
			);

			$sth->bindValue(':uid', $ship->uid);
			$sth->bindValue(':nft_id', $nft_id);
			$sth->bindValue(':level', $level);
			$sth->bindValue(':hp', mt_rand($nft['min_hp'], $nft['max_hp']));
			$sth->bindValue(':def', mt_rand($nft['min_def'], $nft['max_def']));
			$sth->bindValue(':res', mt_rand($nft['min_res'], $nft['max_res']));
			$sth->bindValue(':atk', mt_rand($nft['min_atk'], $nft['max_atk']));
			$sth->bindValue(':spd', mt_rand($nft['min_spd'], $nft['max_spd']));
			$sth->bindValue(':acc', mt_rand($nft['min_acc'], $nft['max_acc']));
			$sth->bindValue(':man', mt_rand($nft['min_man'], $nft['max_man']));

			if (!$sth->execute()) {
				return false;
			}
		}

		return static::updateStats($db, $ship);
	}

	public static function remove($db, $ship, $nft_id)
	{
		if (!static::update($db, $ship->uid, $nft_id, 0)) {
			return false;
		}
		
		return static::updateStats($db, $ship);
	}

	public static function updatestats($db, $ship)
	{
		$sth = $db->getPdo()->prepare('select * from protoship where level=0');

		$proto = DbHelper::findOne($sth);
		if (!$proto) {
			return false;
		}

		$ship->max_hp = (int) $proto['hp'];
		$ship->max_def = (int) $proto['def'];
		$ship->max_res = (int) $proto['res'];
		$ship->min_atk = (int) $proto['min_atk'];
		$ship->max_atk = (int) $proto['max_atk'];
		$ship->spd = (int) $proto['spd'];
		$ship->acc = (int) $proto['acc'];
		$ship->man = (int) $proto['man'];

		$userNftList = static::getActiveList($db, $ship->uid);

		$ship->level = count($userNftList);

		foreach($userNftList as $unft) {
			$ship->max_hp += (int) $unft['hp'];
			$ship->max_def += (int) $unft['def'];
			$ship->max_res += (int) $unft['res'];
			$ship->min_atk += (int) $unft['atk'];
			$ship->max_atk += (int) $unft['atk'];
			$ship->spd += (int) $unft['spd'];
			$ship->acc += (int) $unft['acc'];
			$ship->man += (int) $unft['man'];
		}

		$ship->trim();

		$sth = $db->getPdo()->prepare(
			'update ship set level=:level, hp=:hp, max_hp=:max_hp, def=:def, max_def=:max_def, res=:res, max_res=:max_res, min_atk=:min_atk, max_atk=:max_atk,'
			. ' spd=:spd, acc=:acc, man=:man where user_id=:uid'
		);

		$sth->bindValue(':level', $ship->level);
		$sth->bindValue(':hp', $ship->hp);
		$sth->bindValue(':max_hp', $ship->max_hp);
		$sth->bindValue(':def', $ship->def);
		$sth->bindValue(':max_def', $ship->max_def);
		$sth->bindValue(':res', $ship->res);
		$sth->bindValue(':max_res', $ship->max_res);
		$sth->bindValue(':min_atk', $ship->min_atk);
		$sth->bindValue(':max_atk', $ship->max_atk);
		$sth->bindValue(':spd', $ship->spd);
		$sth->bindValue(':acc', $ship->acc);
		$sth->bindValue(':man', $ship->man);
		$sth->bindValue(':uid', $ship->uid);

		return $sth->execute();
	}
}
