<?php
namespace app\components;

class ResponseMap
{
	private $_responses = [];
	
	public function clear()
	{
		$this->_responses = [];
	}
	
	public function addResponse($uid, $response)
	{
		if (!isset($this->_responses[$uid])) {
			$this->_responses[$uid] = [];
		}

		if ($response) {
			$this->_responses[$uid][] = $response;
		}
	}

	public function hasResponses()
	{
		return !empty($this->_responses);
	}
	
	public function & getResponses($uid = null)
	{
		if (isset($this->_responses[$uid])) {
			return $this->_responses[$uid];
		}
		
		return $this->_responses;
	}
}
