<?php
namespace app\base\socket;

class Buffer
{
    public $data = null;
    public $pos;
    public $ready = false;
	public $raw = false;
	
	public function clear()
	{
		$this->ready = false;
		$this->pos = 0;
		$this->data = null;
	}

	public function set($text)
	{
		$this->pos = 0;
		$this->data = $text;
		$this->ready = true;
	}
	
	public function get()
	{
		$this->pos = 0;
		$this->ready = false;

		return $this->data;
	}
}
