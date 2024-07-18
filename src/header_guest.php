<?php
define('ACCESS_ALLOWED', true);
include 'functions.php';
include 'config.php';

$ip = $_SERVER["REMOTE_ADDR"];  //ip client

if(  !is_page_enabled($ip)  )
{
    // too many login attempts
    die("Your are allowed 3 attempts in " . conf("FAILED_OPERATION_INTERVAL"));
}

/* Returns The Current PHP File Name */
$php_page = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title_page ?></title>
    <link rel="stylesheet" href="assets/fonts/bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="shortcut icon" href="assets/img/favicon.ico">
    <?php if (isset($my_style)) { echo $my_style; } ?>
</head>
<body class="<?php echo $body_class; ?>">
    <header>
        <nav>
            <div><h1><?php echo conf("APP_NAME"); ?></h1></div>
            <div>
                <ul class="menu">
                    <li><a class="<?php if($php_page == "login.php") echo 'active'; ?>" href="<?php echo conf("BASE_URL"); ?>/login.php">Login</a></li>
                    <li><a class="<?php if($php_page == "register.php") echo 'active'; ?>" href="<?php echo conf("BASE_URL"); ?>/register.php">Register</a></li>
                <ul>
            </div>
        </nav>
    </header>
    <main>