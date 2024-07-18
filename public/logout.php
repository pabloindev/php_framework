<?php
define('ACCESS_ALLOWED', true);
include '../src/functions.php';
include '../src/config.php';

writeLog('LOGOUT', "OK for " . $_SESSION['email']);

// destroy session
clean_sessions();

// Reindirizzamento alla pagina di login
goto_url("login.php");
exit;