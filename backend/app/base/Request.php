<?php
namespace app\base;

class Request {
	public static function post($param = null) {
		if (!$param) {
			return $_POST;
		}
		if (isset($_POST[$param])) {
			return $_POST[$param];
		}
		return null;
	}

	public static function get($param = null) {
		if (!$param) {
			return $_GET;
		}
		if (isset($_GET[$param])) {
			return $_GET[$param];
		}
		return null;
	}

	public static function isPost() {
		return !empty($_POST);
	}

	public static function cookie($name) {
		if (isset($_COOKIE[$name])) {
			return $_COOKIE[$name];
		}
		return null;
	}

	public static function ip() {
		return $_SERVER['REMOTE_ADDR'];
	}

	public static function userAgent() {
		return $_SERVER['HTTP_USER_AGENT'];
	}
}
