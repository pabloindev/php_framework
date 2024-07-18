<?php
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

$conf = parse_ini_file(realpath(__DIR__ . '/../.env'), true,  INI_SCANNER_TYPED);


$conn = NULL;
try {
    // Connessione al database
    $dsn = "mysql:host=" . $conf["DB_HOST"] . ";dbname=" . $conf["DB_NAME"];
    $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    );
    $conn = new PDO($dsn, $conf["DB_USER"], $conf["DB_PASS"], $options);
    //echo "Connessione riuscita";
} catch (PDOException $e) {
    // Gestione errore
    die("connection to db failed: " . $e->getMessage());
}




// Verifica che la directory per la sessione esista
if (!is_dir(conf("SESSION_PATH"))) {
    mkdir(conf("SESSION_PATH"), 0777, true);
}

// Specifica la directory per i file di sessione
session_save_path(conf("SESSION_PATH"));

// Imposta la durata della sessione a 2 ore
ini_set('session.gc_maxlifetime', 2 * 60 * 60); // 2 ore in secondi

// Imposta il tempo di vita del cookie di sessione a 2 ore
session_set_cookie_params(2 * 60 * 60);

// start session
session_start();
