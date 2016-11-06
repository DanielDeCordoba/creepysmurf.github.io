<?php
//Start session (to get values with post)
session_start();

//Data manipulation functions
function convert($num){ //Returns number in format '\x00\x00\x00\x00'
	//return "\\" .dechex($num/256/256/256/16).dechex(($num/256/256/256)%16) ."\\" .dechex($num/256/256/16).dechex(($num/256/256)%16) ."\\" .dechex($num/256/16).dechex(($num/256)%16) ."\\" .dechex($num/16).dechex(($num)%16);
	return chr($num/256/256/256) .chr($num/256/256) .chr($num/256) .chr($num);
}
function convertback($word){ #Only uses the first 4 characters of word
	return ord($word[3])+ord($word[2])*256+ord($word[1])*256*256+ord($word[0])*256*256*256;
}
function reply($replystring){ #Sends the length of the word in format '\x00\x00\x00\x00' followed by the word
	$length = strlen($replystring);
	return convert($length) .$replystring;
}

error_reporting(0);

$ip = "127.0.0.1";
$port = 9997;
try {
	$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	if ($socket === false) {
		//Socket error
		$out = "Connection_Error: (The socket could not be created)";
	} else {
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0, "usec"=>50000)); //Time Out = 50ms
		if (!socket_connect($socket, $ip, $port)) {
			//Connect error
			$out = "Connection_Error: (The socket could not connect: " .socket_strerror(socket_last_error($socket)) .") (" .$ipaux[$i] ." ," .$portaux[$i] .")";
			socket_close($socket);
			//header('Location: restart.html');
		}else{
			//connection successful
			$mssg = reply("beh");
			socket_write($socket, $mssg, strlen($mssg)); //Send
			$length = socket_read($socket, 4); //Read answer length
			$len = convertback($length); //Convert byted to int
			$buf = "This is a buffer"; //Buffer
			$out = "";
			$total_bytes = 0;
			if ($len > 0) {
				do {
					if (false ==($bytes = socket_recv($socket, $buf, $len-$total_bytes, MSG_WAITALL))) {
						$out = "Connection_Error: (The socket could not read the data: " .socket_strerror(socket_last_error($socket)) .")";
						break;
					} else {
						$out = $out .$buf;
						$total_bytes += $bytes;
					}
				} while ($total_bytes < $len);
			}
			socket_close($socket); //Close socket
		}
	}
} catch (Exception $e) {
	$out = "Connection_Error: ". $e->getMessage();
}
echo $out;
?>