<?php
namespace app\components;
use PDO;

class DbHelper
{
	public static function findOne(\PDOStatement $sth)
	{
		$sth->execute();
		$data = $sth->fetch(PDO::FETCH_ASSOC);
		$sth->closeCursor();
		
		return $data;
	}
	
	public static function getList(\PDOStatement $sth, $key = null)
	{
		$sth->execute();
		$out = [];
		if ($key) {
			while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
				$out[$row[$key]] = $row;
			}
		} else {
			while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
				$out[] = $row;
			}
		}
		$sth->closeCursor();

		return $out;
	}
}
