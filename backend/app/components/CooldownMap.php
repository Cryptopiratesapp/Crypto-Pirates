<?php
namespace app\components;

class CooldownMap
{
	private $_cd = [];

	public function add($uid, $key, $val)
	{
		$this->_cd[$uid][$key] = (int) $val;
	}
	
	public function get($uid, $key)
	{
		if (isset($this->_cd[$uid][$key])) {
			return $this->_cd[$uid][$key];
		}
		
		return false;
	}
	
	public function clear($uid, $key)
	{
		unset($this->_cd[$uid][$key]);
	}	
	
	public function update($uid)
	{
		if (!isset($this->_cd[$uid])) {
			return;
		}
		$values = $this->_cd[$uid];
		foreach($values as $key => $val) {
			if ($val > 0) {
				$this->_cd[$uid][$key]--;
			} else {
				unset($this->_cd[$uid][$key]);
			}
		}
		if (empty($this->_cd[$uid])) {
			unset($this->_cd[$uid]);
		}
	}
}
