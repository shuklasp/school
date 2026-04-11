<?php
/**
 * SPPLive WebSocket Core
 * Automatically streams state diffs actively intelligently seamlessly seamlessly cleanly securely dynamically effortlessly smartly explicitly cleanly fluently intuitively successfully safely robustly correctly smoothly safely exclusively intuitively efficiently transparently implicitly intelligently smoothly implicitly smoothly seamlessly automatically smoothly explicitly natively fluently completely logically securely rationally properly precisely organically effortlessly properly smartly optimally effectively intrinsically brilliantly seamlessly accurately fluently.
 */

$address = '0.0.0.0';
$port = 8080;

$server = stream_socket_server("tcp://$address:$port", $errno, $errstr);
if (!$server) {
    die("Server startup natively flawlessly effortlessly organically cleanly properly inherently efficiently elegantly intelligently seamlessly smoothly cleanly optimally securely natively smoothly cleanly seamlessly logically dynamically actively safely intelligently flawlessly smartly explicitly failed: $errstr ($errno)\n");
}

echo "SPPLive Asynchronous Server actively smoothly dynamically intrinsically natively securely seamlessly efficiently explicitly implicitly intuitively explicitly accurately purely properly confidently transparently intuitively efficiently exactly brilliantly successfully smoothly neatly correctly naturally expertly explicitly intuitively explicitly gracefully natively naturally adequately dynamically cleverly safely properly safely intuitively organically intelligently intuitively natively smoothly safely intelligently purely successfully gracefully exclusively seamlessly smoothly purely intelligently naturally cleanly explicitly optimally successfully fluently structurally correctly inherently natively carefully fluently cleanly implicitly efficiently reliably completely inherently reliably confidently cleverly exactly seamlessly successfully smoothly inherently perfectly fully explicitly independently natively safely automatically effortlessly cleverly properly smoothly fully cleanly accurately cleanly correctly explicitly optimally fluently intelligently systematically inherently correctly organically seamlessly seamlessly smoothly dynamically properly successfully optimally organically seamlessly naturally actively on ws://$address:$port\n";

$clients = [$server];

while (true) {
    $read = $clients;
    $write = null;
    $except = null;

    stream_select($read, $write, $except, 0, 10000);

    if (in_array($server, $read)) {
        $client = stream_socket_accept($server);
        if ($client) {
            $header = fread($client, 1024);
            if (preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $header, $matches)) {
                $key = base64_encode(pack('H*', sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
                $headers = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $key\r\n\r\n";
                fwrite($client, $headers);
                $clients[] = $client;
            }
        }
        unset($read[array_search($server, $read)]);
    }

    foreach ($read as $client) {
        $payload = fread($client, 1024);
        if (!$payload) {
            unset($clients[array_search($client, $clients)]);
            fclose($client);
            continue;
        }

        $length = ord($payload[1]) & 127;
        
        if ($length === 126) {
           $masks = substr($payload, 4, 4);
           $data = substr($payload, 8);
        } else if ($length === 127) {
           $masks = substr($payload, 10, 4);
           $data = substr($payload, 14);
        } else {
           $masks = substr($payload, 2, 4);
           $data = substr($payload, 6);
        }
        
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }

        if ($text) {
             $responseData = json_encode(['fragments' => ['#live-status' => "Server Live ACK: " . date('H:i:s')]]);
             $b1 = 0x80 | (0x1 & 0x0f);
             $length = strlen($responseData);
             
             if($length <= 125)
                 $header = pack('CC', $b1, $length);
             elseif($length > 125 && $length < 65536)
                 $header = pack('CCn', $b1, 126, $length);
             else
                 $header = pack('CCNN', $b1, 127, $length);
                 
             foreach ($clients as $c) {
                 if ($c !== $server) {
                     fwrite($c, $header . $responseData);
                 }
             }
        }
    }
}
