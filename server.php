<?php
//Execute server with C:/"Program Files (x86)"/Ampps/php/php.exe server.php
//Start session (to get values with post)
session_start();

//Avoids hacking and exploits
function test_input($input) {
	$input = trim($input); //Strip unnecessary characters (extra space, tab, newline) 
	$input = stripslashes($input); //Remove backslashes (\)
	$input = htmlspecialchars($input); //Convert special characters to HTML entities to avoid $_SERVER["PHP_SELF"] Exploits
	return $input;
}

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

error_reporting(E_ALL);

/* Permitir al script esperar para conexiones. */
set_time_limit(0);

/* Activar el volcado de salida implícito, así veremos lo que estamo obteniendo mientras llega. */
ob_implicit_flush();

$address = "127.0.0.1";//$_REQUEST["serveraddr"];//'127.0.0.1'; //'192.168.1.53';
if (isset($_SESSION['serverOn']) and $_SESSION["serverOn"] == true) {
	echo "Server already ON!";
} else {
	$_SESSION["serverOn"] = true;
	$address = gethostbyname($address);
	$port = 9997;
	
	if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
		$_SESSION["serverOn"] = false;
		echo "socket_create() falló: razón: " . socket_strerror(socket_last_error()) . "\n";
	}

	if (socket_bind($sock, $address, $port) === false) {
		$_SESSION["serverOn"] = false;
		echo "socket_bind() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
	}

	if (socket_listen($sock, 5) === false) {
		$_SESSION["serverOn"] = false;
		echo "socket_listen() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
	}

	echo "Server ready\n";
	
	$lastBeh = "";

	do {
		if (($msgsock = socket_accept($sock)) === false) {
			echo "socket_accept() falló: razón: " . socket_strerror(socket_last_error($sock)) . "\n";
			break;
		} else {
			echo "Connection accepted\n";
		}

		$length = socket_read($msgsock, 4); //Read answer length
		//$length = str_split($length,4);
		$len = convertback($length); //Convert bytes to int

		$buf = "This is a buffer"; //Buffer
		$out = "";
		$total_bytes = 0;
		if ($len > 0) {
			do {
				if (false ==($bytes = socket_recv($msgsock, $buf, $len-$total_bytes, MSG_WAITALL))) {
					$out = "Connection_Error: (The socket could not read the data: " .socket_strerror(socket_last_error($msgsock)) .")";
					break;
				} else {
					$out = $out .$buf;
					$total_bytes += $bytes;
				}
			} while ($total_bytes < $len);
		}

		echo "Received: $out\n";
		if ($out == 'shutdown') {
			socket_write($msgsock, $length.$out, strlen($length.$out));
			socket_close($msgsock);
			break;
		} elseif ($out == 'beh') {
			$answer = $lastBeh;
			socket_write($msgsock, reply($answer), strlen($answer)+4);
			socket_close($msgsock);
			$lastBeh = "";
		} else {
			socket_write($msgsock, $length.$out, strlen($length.$out));
			socket_close($msgsock);
			$lastBeh = $out;
		}
	} while (test_input($_SESSION["serverOn"]));
	socket_close($sock);
	$_SESSION["serverOn"] = false;
	echo "Server closed\n";
}
?>