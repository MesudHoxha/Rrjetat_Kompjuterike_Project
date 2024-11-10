<?php

define("COLOR_RESET", "\033[0m");
define("COLOR_GREEN", "\033[32m");
define("COLOR_RED", "\033[31m");
define("COLOR_YELLOW", "\033[33m");
define("COLOR_BLUE", "\033[34m");
define("COLOR_CYAN", "\033[36m");

$server_ip = "10.180.71.217";
$server_port = 12345;
$max_clients = 2;
$client_sockets = [];
$client_types = [];
$client_last_activity = [];
$waiting_clients = [];
$inactivity_limit = 60;
$log_file = "server_logs.txt";
$messages_file = "client_messages.txt";

$server_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$server_socket) {
    die("Nuk u krijua socket-i: " . socket_strerror(socket_last_error()) . "\n");
}

socket_bind($server_socket, $server_ip, $server_port) or die("Nuk u arrit lidhja me IP dhe portin\n");
socket_listen($server_socket) or die("Serveri nuk mund të dëgjojë për lidhjet\n");

echo COLOR_GREEN . "Serveri po dëgjon në IP: $server_ip dhe portin: $server_port" . COLOR_RESET . "\n";

function log_request($message) {
    global $log_file;
    $timestamp = date("Y-m-d H:i:s");
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

function save_message($message) {
    global $messages_file;
    file_put_contents($messages_file, "$message\n", FILE_APPEND);
}

function convert_access_type($access_type) {
    return $access_type === "READ_ONLY" ? "Normal Client" : "Full Access Client";
}

while (true) {
   foreach ($client_sockets as $index => $socket) {
        if (time() - $client_last_activity[$index] > $inactivity_limit) {
            echo COLOR_RED . "Klienti në pozicionin $index është inaktiv. Po mbyllet lidhja." . COLOR_RESET . "\n";
            socket_close($socket);
            unset($client_sockets[$index], $client_types[$index], $client_last_activity[$index]);
            log_request("Lidhja me klientin në pozicionin $index u mbyll për inaktivitet.");
            
            if (!empty($waiting_clients)) {
                $new_client_socket = array_shift($waiting_clients);
                $client_sockets[] = $new_client_socket;
                $client_last_activity[] = time();
                socket_getpeername($new_client_socket, $client_ip);
                echo COLOR_GREEN . "Klienti në pritje me IP $client_ip u lidh pas lirimit të një vendi." . COLOR_RESET . "\n";
                log_request("Klienti në pritje me IP $client_ip u lidh pas lirimit të një vendi.");
                socket_write($new_client_socket, "ACTIVE\n");
                
                $client_type = trim(socket_read($new_client_socket, 1024, PHP_NORMAL_READ));
                $client_types[] = $client_type;
            }
        }
    }

    $read_sockets = $client_sockets;
    $read_sockets[] = $server_socket;
    $write = null;
    $except = null;

    if (socket_select($read_sockets, $write, $except, 0) < 1) {
        continue;
    }

    if (in_array($server_socket, $read_sockets)) {
        $new_socket = socket_accept($server_socket);
        if ($new_socket) {
            if (count($client_sockets) < $max_clients) {
                $client_sockets[] = $new_socket;
                $client_last_activity[] = time();
                socket_getpeername($new_socket, $client_ip);
                echo COLOR_BLUE . "Lidhje e re nga klienti me IP: $client_ip" . COLOR_RESET . "\n";
                log_request("Lidhje e re nga klienti me IP: $client_ip");
                
                $client_type = trim(socket_read($new_socket, 1024, PHP_NORMAL_READ));
                $client_types[] = $client_type;
                echo COLOR_CYAN . "Lloji i klientit: " . convert_access_type($client_type) . COLOR_RESET . "\n";
                socket_write($new_socket, "ACTIVE\n");
            } else {
                socket_write($new_socket, "WAIT\n");
                $waiting_clients[] = $new_socket;
                echo COLOR_YELLOW . "Një klient i ri është vendosur në pritje." . COLOR_RESET . "\n";
            }
        }
    }

    foreach ($client_sockets as $index => $socket) {
        if (in_array($socket, $read_sockets)) {
            $data = trim(@socket_read($socket, 1024, PHP_NORMAL_READ));
            if ($data === false || $data === "") {
                echo COLOR_RED . "Klienti në pozicionin $index u largua nga serveri." . COLOR_RESET . "\n";
                log_request("Klienti në pozicionin $index u largua.");
                socket_close($socket);
                unset($client_sockets[$index], $client_types[$index], $client_last_activity[$index]);
                
                if (!empty($waiting_clients)) {
                    $new_client_socket = array_shift($waiting_clients);
                    $client_sockets[] = $new_client_socket;
                    $client_last_activity[] = time();
                    socket_getpeername($new_client_socket, $client_ip);
                    echo COLOR_GREEN . "Klienti në pritje me IP $client_ip u lidh pas lirimit të një vendi." . COLOR_RESET . "\n";
                    log_request("Klienti në pritje me IP $client_ip u lidh pas lirimit të një vendi.");
                    socket_write($new_client_socket, "ACTIVE\n");
                    
                    $client_type = trim(socket_read($new_client_socket, 1024, PHP_NORMAL_READ));
                    $client_types[] = $client_type;
                }
                
                continue;
            }

            if (!empty($data)) {
                $client_last_activity[$index] = time();
                
                if ($data === "READ_ONLY" || $data === "FULL_ACCESS") {
                    if ($client_types[$index] !== $data) {
                        $old_access = convert_access_type($client_types[$index]);
                        $new_access = convert_access_type($data);
                        echo COLOR_CYAN . "Klienti në pozicionin $index ka ndryshuar qasjen nga $old_access në $new_access" . COLOR_RESET . "\n";
                        log_request("Klienti në pozicionin $index ka ndryshuar qasjen nga $old_access në $new_access");
                        $client_types[$index] = $data;
                    }
                } else {
                    save_message("Mesazh nga klienti $index: $data");
                    echo COLOR_GREEN . "Mesazh nga klienti $index: $data" . COLOR_RESET . "\n";

                    if ($data == "READ_FILE") {
                        $file_content = file_get_contents("server_file.txt");
                        socket_write($socket, "Përmbajtja e skedarit: $file_content\n");
                    } elseif ($data == "EXECUTE_CMD" && $client_types[$index] === "FULL_ACCESS") {
                        $output = shell_exec("dir"); 
                        socket_write($socket, "Rezultati i komandës: $output\n");
                    } elseif (strpos($data, "WRITE_FILE:") === 0 && $client_types[$index] === "FULL_ACCESS") {
                        $message = substr($data, strlen("WRITE_FILE:"));
                        file_put_contents("server_file.txt", $message . PHP_EOL, FILE_APPEND);
                        socket_write($socket, "Mesazhi u shkrua në skedar.\n");
                    } else {
                        socket_write($socket, "Serveri: Mesazhi juaj u pranua.\n");
                    }
                }
            }
        }
    }
}
?>
