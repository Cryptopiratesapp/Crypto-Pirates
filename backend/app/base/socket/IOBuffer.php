<?php

namespace app\base\socket;

class IOBuffer
{
	public $name;
	public $encoder;
	public $ready;

	private $_readBuffer;
	private $_writeBuffer;

	public function __construct($name)
	{
		$this->_name = $name;
		$this->_readBuffer = new Buffer();
		$this->_writeBuffer = new Buffer();
	}

//	public function clear()
//	{
//		$this->_readBuffer->clear();
//		$this->_writeBuffer->clear();
//	}

	public function input($text)
	{
		$this->_readBuffer->set($text);
	}
	
	public function output()
	{
		$buf = $this->_writeBuffer;
		
		if ($buf->raw) {
			$buf->raw = false;
			return $buf->get();
		}

		return $this->encoder->encode($buf->get());
	}
	
	public function readRaw()
	{
		return $this->_readBuffer->get();
	}
	
	public function writeRaw($text)
	{
		$this->_writeBuffer->raw = true;
		$this->_writeBuffer->set($text);
	}

	public function write($text)
	{
		return $this->_writeBuffer->set($text);
	}
	
	public function read()
	{
		return $this->encoder->decode($this->_readBuffer->get());
	}
	
	public function isWritable()
	{
		return $this->_writeBuffer->ready;
	}
	
	public function isReadable()
	{
		return $this->_readBuffer->ready;
	}
}
