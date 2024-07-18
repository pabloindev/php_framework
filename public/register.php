<?php
$body_class="login"; //aggiunto al body come classe 
$title_page="Login"; //title della pagina
?>
<?php ob_start(); ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
$error = '';  // error message
//$ip nella header_guest


if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{   
    $name = $_POST['name']; 
    $email = $_POST['email'];
    $password = $_POST['password'];
    $arr_err = [];

    // verifico il campo nome è valido
    if(  ss($name) || !isLengthValid($name, 150, 4)  )
    {
        $arr_err[] = "Name non valido - si prega di inserire un name di lunghezza compresa tra 4 e 150 caratteri";
    }
    // verifico se l'email è valida
    if(  ss($email) || !isValidEmail($email) || !isLengthValid($email, 150, 7)  )
    {
        $arr_err[] = "Email non valida - si prega di inserire una email valida e compresa tra i 7 e 150 caratteri";
    }
    // verifico se la password è valida
    if(  ss($password) || !isValidPassword($password)  )
    {
        $arr_err[] = "Password non valida - si prega di inserire una password superiore a 6 caratteri, e inserire almeno un numero e una lettera";
    }
    // verifico se nel sistema esiste già un utente con la stessa email
    if(  count($arr_err) === 0 )
    {
        $conta = getValueQuery("SELECT count(*) as conta FROM users WHERE email = :email", [":email" => $email], "conta");
        if(  intval($conta) > 0 )
        {
            $arr_err[] = "Utente già registrato, si prega di utilizzare una diversa email";
        }
    }
    if(  count($arr_err) === 0 )
    {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, enabled) VALUES (:name, :email, :password, 1)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->execute();
        $id = $conn->lastInsertId();
        
        $stmt = $conn->prepare("INSERT INTO users_roles (id_user, id_role) VALUES (:id_user, 2)"); // abilito ruolo user
        $stmt->bindParam(':id_user', $id);
        $stmt->execute();

        // set session message
        add_flash_message('msg', FlashMessageType::Success, "Utente con email:$email creato - prego effettuare il login");

        //write log
        writeLog('REGISTER', "OK for $email");

        // redirect to login
        goto_url("login.php");
        exit;
    }
    else 
    {
        writeLog('REGISTER', "failed for " . $email);
        fialed_page_actions($ip);
        $error = implode("<br />", $arr_err);
    }
}
?>
<h2>Register</h2>
<form method="post" >
    <div>
        <label for="name" >Name:</label>
        <input type="text" id="name" name="name" required />
    </div>
    <div>
        <label for="email" >Email:</label>
        <input type="text" id="email" name="email" required />
    </div>
    <div>
        <label for="password" >Password:</label>
        <input type="password" id="password" name="password" required />
    </div>
    <div>
        <div class="g-recaptcha" data-sitekey="<?php echo conf("SITE_KEY"); ?>" data-callback="enableSubmit"></div>
    </div>
    <div>
        <button class="btn primary" id="register-button" disabled><i class="bi bi-r-circle"></i> Register</button>
    </div>
</form>
<?php if ($error) { echo "<div class='alert alert-danger'>$error</div>"; } ?>
<?php print_flash("msg"); ?>

<?php ob_start(); ?>
<script>
    function enableSubmit(token) {
        document.getElementById('register-button').disabled = false;
    }
    document.addEventListener("DOMContentLoaded", function(){
        
    });
</script>
<?php 
$my_script = ob_get_clean();
include '../src/footer_guest.php';
