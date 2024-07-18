<?php
define('ACCESS_ALLOWED', true);
include 'functions.php';
include 'config.php';

// check session
if(  !isAuthenticated($arr_roles)  ){
    clean_sessions();
    goto_url("login.php?error=" . rawurlencode("Non si dispongono i permessi per vedere la pagina richiesta")); 
    exit;
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
                    <li><a class="<?php if($php_page == "home.php") echo 'active'; ?>" href="<?php echo conf("BASE_URL"); ?>/home.php">Home</a></li>
                    <li><a class="<?php if($php_page == "user_list.php") echo 'active'; ?>" href="<?php echo conf("BASE_URL"); ?>/user_list.php">Users</a></li>
                    <li><a href="<?php echo conf("BASE_URL"); ?>/logout.php">Logout</a></li>
                <ul>
            </div>
        </nav>
    </header>
    <main>