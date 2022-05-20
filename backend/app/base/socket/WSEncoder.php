<?php
namespace app\base\socket;

class WSEncoder
{
	public $enabled = false;

	public function detect($text)
	{
		echo "*** WS detect\n";
		$map = $this->parseUpgradeRequest($text);
		if ($map !== false) {
			$key = $map['Sec-WebSocket-Key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
			$key = base64_encode(hash('sha1', $key, true));
			$out = "HTTP/1.1 101 Switching Protocols\r\n"
					. "Upgrade: websocket\r\n"
					. "Connection: Upgrade\r\n"
					. "Sec-WebSocket-Accept: " . $key . "\r\n\r\n";

			return $out;
		}

		return false;
	}

    public function parseUpgradeRequest($text)
    {
        $map = [];
        $parts = explode("\r\n", $text);
        foreach($parts as $part) {
            $keyval = explode(':', $part);
            if (count($keyval) != 2) {
                continue;
            }
            $key = $keyval[0];
            $map[$key] = trim($keyval[1]);
        }

        if (
            isset($map['Connection']) && strpos($map['Connection'], 'Upgrade') !== false
            && isset($map['Upgrade']) && $map['Upgrade'] === 'websocket'
            && isset($map['Sec-WebSocket-Key'])
        ) {
            return $map;
        }

        return false;
    }

	public function encode($text)
	{
		$len = strlen($text);
		$opcode = 8;
		$fin = 1;
		$mask = 0;
		$first_byte = $fin | ($opcode << 4);
		$second_byte = $mask << 7;
		if ($len < 126) {
			$second_byte |= $len;
			$encoded = chr($first_byte) . chr($second_byte);
		} else {
			$second_byte |= 126;
			$encoded = chr($first_byte) . chr($second_byte) . chr($len >> 8) . chr($len & 255);
		}

		return $encoded . $text;
	}
	
    public function decode($text)
    {
/*
      0                   1                   2                   3
      0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
     +-+-+-+-+-------+-+-------------+-------------------------------+
     |F|R|R|R| opcode|M| Payload len |    Extended payload length    |
     |I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
     |N|V|V|V|       |S|             |   (if payload len==126/127)   |
     | |1|2|3|       |K|             |                               |
     +-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
     |     Extended payload length continued, if payload len == 127  |
     + - - - - - - - - - - - - - - - +-------------------------------+
     |                               |Masking-key, if MASK set to 1  |
     +-------------------------------+-------------------------------+
     | Masking-key (continued)       |          Payload Data         |
     +-------------------------------- - - - - - - - - - - - - - - - +
     :                     Payload Data continued ...                :
     + - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
     |                     Payload Data continued ...                |
     +---------------------------------------------------------------+
 */
        $opcode = ord($text[0]);
        $fin = $opcode & 1;
        $opcode >>= 4;
        $len = ord($text[1]);
        $mask = $len & 0b10000000;
        $len &= 0b01111111;
		$offset = 2;
		if ($len == 126) {
			$len = ord($text[2]) | (ord($text[3]) << 8);
			$offset = 4;
		} else if ($len == 127) {
			$w0 = ord($text[2]) | (ord($text[3]) << 8) | (ord($text[4]) << 16) | (ord($text[5]) << 24);
			$w1 = ord($text[6]) | (ord($text[7]) << 8) | (ord($text[8]) << 16) | (ord($text[9]) << 24);
			$len = $w0 | ($w1 << 32);
			$offset = 10;
		}

        //echo "opcode = $opcode, fin = $fin, mask=$mask, len=$len\n";
        $key = [ord($text[$offset++]), ord($text[$offset++]), ord($text[$offset++]), ord($text[$offset++])];

		$decoded = '';
		for ($i = 0; $i < $len; $i++) {
    		$decoded .= chr(ord($text[$offset++]) ^ $key[$i % 4]);
		}

		return $decoded;
    }
}
