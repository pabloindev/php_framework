<?php
$body_class="login"; //aggiunto al body come classe 
$title_page="Login"; //title della pagina
?>
<?php ob_start(); ?>
<style>
    form {
        display:grid;
        gap:10px;
        label {display:inline-block;width:80px;}
    }
</style>
<?php $my_style = ob_get_clean(); ?>
<?php
include '../src/header_guest.php';
$error = $_GET["error"] ?? '';  // error message
//$ip nella header_guest

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to get user info 
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email=:email AND enabled=1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    // get result 
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch();
        $id = $row['id'];
        $hashed_password = $row['password'];
        
        // check password
        if (password_verify($password, $hashed_password)) {
            
            // update last login timestamp
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // get roles
            $stmt = $conn->prepare("SELECT id_role from users_roles where id_user = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);


            // clear old attemps
            $stmt = $conn->prepare("DELETE FROM ips where ip = :ip");
            $stmt->bindParam(':ip', $ip);
            $stmt->execute();

            // Set session
            $_SESSION['email'] = $email;
            $_SESSION['id'] = $id;
            $_SESSION['roles'] = array_column($roles, 'id_role'); // array semplice contente solo gli id

            // set session message
            add_flash_message('msg', FlashMessageType::Success, 'Login OK');

            //write log
            writeLog('LOGIN', "OK for $email");

            // redirect to home.php
            goto_url("home.php");
            exit;
        } else {
            writeLog('LOGIN', "failed for " . $email);
            fialed_page_actions($ip);
            $error = 'User not found';
        }
    } else {
        writeLog('LOGIN', "failed for " . $email);
        fialed_page_actions($ip);
        $error = 'User not found';
    }
}
?>
<h2>Login</h2>
<form method="post" >
    <div>
        <label for="email" >Email:</label>
        <input type="text" id="email" name="email" required />
    </div>
    <div>
        <label for="password" >Password:</label>
        <input type="password" id="password" name="password" required />
    </div>
    <div>
        <button class="btn primary"><i class="bi bi-box-arrow-in-left"></i> Login</button>
    </div>
</form>
<?php if ($error) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
<?php print_flash("msg"); ?>

<?php ob_start(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function(){
    });
</script>
<?php 
$my_script = ob_get_clean();
include '../src/footer_guest.php';
