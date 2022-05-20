<?php
namespace app\base;

class Security {
	public static $password_length = 8;
	public static $algo = 'sha256';

	private static $pool = [
		'0' => '0123456789',
		'a' => 'abcdefghijklmnopqrstuvwxyz',
		'A' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'&' => '!@#$%^&_',
		'*' => '*()+-=,./"\\\''
	];

	public static function init($params)
	{
		if (isset($params['password_length'])) {
			self::$password_length = $params['password_length'];
		}
		if (isset($params['algo'])) {
			self::$algo = $params['algo'];
		}
	}

	public static function getRandomString($length, $mask = null)
	{
		$s = '';
		if (is_null($mask)) {
			$s = implode('', self::$pool);
		} else {
			for ($i = 0; $i < strlen($mask); $i++) {
				$c = $mask[$i];
				if (isset(self::$pool[$c])) {
					$s .= self::$pool[$c];
				}
			}
		}

		return substr(str_shuffle($s), 0, $length);
	}

	public static function hashPassword($salt, $input)
	{
		return hash(self::$algo, $input . $salt, true);
	}
}
