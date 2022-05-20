<?php
namespace app\base\socket;

class DefaultEncoder
{
	public $pattern;

	public function __construct($pattern = null)
	{
		$this->_pattern = $pattern;
	}
	
	public function detect($text)
	{
		if (!$this->pattern) {
			return null;
		}

		$len = strlen($this->pattern);
		if (substr($text, 0, $len) === $this->pattern) {
			return null;
		}

		return false;
	}

	public function encode($text)
	{
		return $text;
	}

	public function decode($text)
	{
		return $text;
	}
}
