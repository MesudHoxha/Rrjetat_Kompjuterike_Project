<?php

define("COLOR_RESET", "\033[0m");
define("COLOR_WHITE", "\033[37m");
define("COLOR_RED", "\033[31m");
define("COLOR_GREEN", "\033[32m");
define("COLOR_YELLOW", "\033[33m");
define("COLOR_BLUE", "\033[34m");
define("COLOR_CYAN", "\033[36m");

$server_ip = "10.180.71.217";
$server_port = 12345;
$full_access_password = "sekret123";

function get_client_type() {
    global $full_access_password;
    
    echo COLOR_BLUE . "Zgjidhni llojin e klientit:\n" . COLOR_RESET;
    echo "1. Normal Client (READ_ONLY)\n";
    echo "2. Full Access Client (FULL_ACCESS)\n";
    echo COLOR_YELLOW . "Zgjedhja juaj (1 ose 2): " . COLOR_RESET;
    $client_type_choice = trim(fgets(STDIN));

    if ($client_type_choice == "2") {
        echo COLOR_YELLOW . "Shkruani fjalëkalimin për qasje të plotë: " . COLOR_RESET;
        $password = trim(fgets(STDIN));

        if ($password === $full_access_password) {
            echo COLOR_GREEN . "Keni zgjedhur Full Access Client (FULL_ACCESS).\n" . COLOR_RESET;
            return "FULL_ACCESS";
        } else {
            echo COLOR_RED . "Fjalëkalimi i pasaktë. Qasja do të jetë vetëm READ_ONLY.\n" . COLOR_RESET;
            return "READ_ONLY";
        }
    } else {
        echo COLOR_GREEN . "Keni zgjedhur Normal Client (READ_ONLY).\n" . COLOR_RESET;
        return "READ_ONLY";
    }
}

$client_type = get_client_type();

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die(COLOR_RED . "Nuk mund të krijohet socket-i\n" . COLOR_RESET);
}

if (!socket_connect($socket, $server_ip, $server_port)) {
    die(COLOR_RED . "Nuk mund të lidhet me serverin.\n" . COLOR_RESET);
}

socket_write($socket, "$client_type\n", strlen("$client_type\n"));

while (true) {
    $response = trim(socket_read($socket, 1024));
    
    if ($response === "WAIT") {
        echo COLOR_YELLOW . "Prisni... nuk jeni aktiv akoma sepse nuk ka vende të lira.\n" . COLOR_RESET;
        sleep(2); 
    } elseif ($response === "ACTIVE") {
        echo COLOR_GREEN . "Jeni aktiv! Tani mund të përdorni komandat.\n" . COLOR_RESET;
        break;
    } else {
        echo COLOR_CYAN . "Mesazh nga serveri: $response\n" . COLOR_RESET;
    }
}

while (true) {
    if ($client_type === "FULL_ACCESS") {
        echo COLOR_WHITE . "Shkruani '" . COLOR_RED . "READ_FILE" . COLOR_WHITE . "' për të lexuar skedarin, '" . COLOR_RED . "WRITE_FILE" . COLOR_WHITE . "' për të shtuar mesazh në skedar, '" . COLOR_RED . "EXECUTE_CMD" . COLOR_WHITE . "' për të ekzekutuar komandë, '" . COLOR_RED . "CHANGE_ACCESS" . COLOR_WHITE . "' për të ndryshuar qasjen ose '" . COLOR_RED . "EXIT" . COLOR_WHITE . "' për të dalë: " . COLOR_RESET;
    } else {
        echo COLOR_WHITE . "Shkruani '" . COLOR_RED . "READ_FILE" . COLOR_WHITE . "' për të lexuar skedarin, '" . COLOR_RED . "CHANGE_ACCESS" . COLOR_WHITE . "' për të ndryshuar qasjen ose '" . COLOR_RED . "EXIT" . COLOR_WHITE . "' për të dalë: " . COLOR_RESET;
    }
    
    $input = trim(fgets(STDIN));
    
    if ($input === 'EXIT') {
        echo COLOR_RED . "Duke dalë...\n" . COLOR_RESET;
        break;
    } elseif ($input === 'READ_FILE') {
        socket_write($socket, "READ_FILE\n", strlen("READ_FILE\n"));
        $response = socket_read($socket, 1024);
        echo COLOR_CYAN . "Përgjigjja nga serveri: $response\n" . COLOR_RESET;
    } elseif ($input === 'WRITE_FILE' && $client_type === "FULL_ACCESS") {
        echo COLOR_YELLOW . "Shkruani mesazhin për të shtuar në skedar: " . COLOR_RESET;
        $message = trim(fgets(STDIN));
        socket_write($socket, "WRITE_FILE:$message\n", strlen("WRITE_FILE:$message\n"));
        $response = socket_read($socket, 1024);
        echo COLOR_CYAN . "Përgjigjja pas shkrimit: $response\n" . COLOR_RESET;
    } elseif ($input === 'EXECUTE_CMD' && $client_type === "FULL_ACCESS") {
        socket_write($socket, "EXECUTE_CMD\n", strlen("EXECUTE_CMD\n"));
        $response = socket_read($socket, 1024);
        echo COLOR_CYAN . "Rezultati i komandës: $response\n" . COLOR_RESET;
    } elseif ($input === 'CHANGE_ACCESS') {
        echo COLOR_YELLOW . "Po ndryshoni qasjen e klientit...\n" . COLOR_RESET;
        $client_type = get_client_type();
        socket_write($socket, "$client_type\n", strlen("$client_type\n"));
        echo COLOR_GREEN . "Qasja u ndryshua me sukses në: $client_type\n" . COLOR_RESET;
    } else {
        echo COLOR_RED . "Komanda e pavlefshme. Ju lutem provoni përsëri.\n" . COLOR_RESET;
    }
}

socket_close($socket);
?>
