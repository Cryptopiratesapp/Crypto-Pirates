<?php
namespace app\base\socket;

class SocketServer
{
    public $socket;
	public $buffers = [];
    private $sockets = [];
	private $_dummy_read = [];
	private $_dummy_write = [];
	private $_dummy_except = [];

    public function start($addr, $port)
    {
        $errno = null;
        $errstr = null;

//		$context = stream_context_create();
//		stream_context_set_option($context, 'ssl', 'local_cert', '../ssl/cert.pem');
//		stream_context_set_option($context, 'ssl', 'local_pk', '../ssl/pkey.pem');
//		stream_context_set_option($context, 'ssl', 'passphrase', '123qwe');
//		stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
//		stream_context_set_option($context, 'ssl', 'verify_peer', false);

        $socket = stream_socket_server(
            "tcp://$addr:$port",
            $errno, $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
			//,$context
        );
        if (!$socket) {
            return new SocketServerError($errno, $errstr);
        }

        stream_set_blocking($socket, 0);
        $this->sockets['main'] = $socket;
        $this->socket = $socket;

        return true;
    }

    public function incomingConnection($timeout = 0)
	{
        $name = null;
		$cn = @stream_socket_accept($this->socket, $timeout, $name);
		if ($cn) {
			echo "$name connected\n";
			stream_set_blocking($cn, 0);
			$this->sockets[$name] = $cn;
			$this->buffers[$name] = new IOBuffer($name);
		}

        return $cn;
    }

	public function closeConnection($name)
	{
		if ($name && array_key_exists($name, $this->sockets)) {
			fclose($this->sockets[$name]);
		    unset($this->sockets[$name]);
			unset($this->buffers[$name]);
	        echo "Connection closed: $name\n";
		} else {
			echo "Connection close error: name not found: $name\n";
		}
	}

    public function update()
    {
        if (count($this->sockets) < 2) {
			echo "*** waiting for connections\n";
            $cn = $this->incomingConnection(60);
			if (!$cn) {
				return false;
			}
        }

        $read = $this->sockets;

		$res = stream_select($read, $this->_dummy_write, $this->_dummy_except, 1);
        if ($res > 0) {
			foreach ($read as $name => $sock) {
                if (feof($sock)) {
					echo "*** $name socket eof\n";
                    $this->closeConnection($name);
                    continue;
                }

                if ($sock === $this->socket) {
                    $sock = $this->incomingConnection(0);
                    continue;
                }

                $text = fread($sock, 2048);
                echo $name . ': ' . trim($text) . PHP_EOL;
				$buf = $this->buffers[$name];
				$buf->input($text);
			}
		}

		$write = [];
		foreach ($this->buffers as $name => $buf) {
			if ($buf->isWritable()) {
				$write[$name] = $this->sockets[$name];
			}
		}
	
		if (count($write)) {
			$res = stream_select($this->_dummy_read, $write, $this->_dummy_except, 1);
			if ($res > 0) {
				foreach ($write as $name => $sock) {
					if (feof($sock)) {
						echo "socketserver write: socket eof, name=$name\n";
						$this->closeConnection($name);
						continue;
					}
					$out = $this->buffers[$name]->output();
					if (!$out) {
						echo "*** write: name=$name, buffer output is null, unexpected\n";
					} else {
						fwrite($sock, $out);
					}
				}
			}
		}
		
		return true;
    }

	public function & getWritableBuffers()
	{
		$out = [];
		foreach ($this->buffers as $name => $buf) {
			if ($buf->isWritable()) {
				$out[$name] = $buf;
			}
		}
	
		return $out;
	}

	public function & getReadableBuffers()
	{
		$out = [];
		foreach ($this->buffers as $name => $buf) {
			if ($buf->isReadable()) {
				$out[$name] = $buf;
			}
		}

		return $out;
	}
}
