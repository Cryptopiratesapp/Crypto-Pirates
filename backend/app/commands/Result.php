<?php

namespace app\commands;

class Result
{
	public $evt;
	public $response;

	public function __construct($evt, $response)
	{
		$this->evt = $evt;
		$this->response = $response;
	}
}
