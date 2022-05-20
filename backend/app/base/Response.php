<?php
namespace app\base;

class Response {
	public static function setCookie($name, $value) {
		return setcookie($name, $value, 0, '/');
	}

	public static function deleteCookie($name) {
		return setcookie($name, null, time() - 3600, '/');
	}

	public static function redirect($url) {
		header('Location: ' . $url);
	}
}
