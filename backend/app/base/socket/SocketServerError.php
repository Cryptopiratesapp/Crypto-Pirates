<?php
namespace app\base\socket;

class SocketServerError
{
    public $errNo;
    public $errStr;

    public function __construct($errNo, $errStr)
    {
        $this->errNo = $errNo;
        $this->errStr = $errStr;
    }

    public function __toString()
    {
        return $this->errNo . ':' . $this->errStr;
    }
}
