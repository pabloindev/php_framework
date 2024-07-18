<?php
$body_class="user_card";
$title_page="User card";
$arr_roles=[1]; //pagina accessibile solo a chi possiede il profilo 1 = admin
?>
<?php ob_start(); ?><style></style><?php $my_style = ob_get_clean(); ?>
<?php
include '../src/header.php';

// get data from querystring
$op = $_GET['op'] ?? false;
$id = isset($_GET['idcrypt']) ? decrypt($_GET['idcrypt']) : false;
$back = $_GET['back'] ?? ""; // link ti ritorno
$arr_err = []; //errori di validazione dei dati

// check parametri passati via querystring
if(  !in_array($op, ["u","d","i"]) || !is_numeric($id) )
{
    add_flash_message('msg', FlashMessageType::Warning, "parametri non corretti");
    goto_url("user_list.php"); 
    exit;
}

// remove user
if ($op == 'd') {
    
    $err = executeTransaction(function($conn, $id) {
        $stmt = $conn->prepare("DELETE FROM users_roles where id_user = :id_user");
        $stmt->bindParam(':id_user', $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM users where id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    },  $id);

    if(  ss($err)  ) {
        add_flash_message('msg', FlashMessageType::Success, "Utente cancellato correttamente");
    } else {
        add_flash_message('msg', FlashMessageType::Warning, "Errore nella funzione delete: " . $err);
    }
    
    // get back to the list of card
    goto_url("user_list.php"); 
    exit;
}

// recupero i dati (sia che sia un primo caricamento o meno)
$user = getRowQuery("SELECT u.id, u.name, u.email, u.enabled, '' as password
    , DATE_FORMAT(u.email_verified_at, '%Y-%m-%dT%H:%i') as email_verified_at
    , DATE_FORMAT(u.last_login, '%d/%m/%Y - %H:%i') as last_login
    , DATE_FORMAT(u.created_at, '%d/%m/%Y - %H:%i') as created_at
    , DATE_FORMAT(u.updated_at, '%d/%m/%Y - %H:%i') as updated_at
    From users u
    where id = :id", [":id" => $id]);
$user = ($user ===false ? []: $user); //getisco il caso in cui non trovo l'utente creando un array vuoto
$temp = getRowsQuery("SELECT id_role FROM users_roles WHERE id_user = :id", [":id" => $id]);
$user_roles = array_column($temp, "id_role");
$t_roles = getRowsQuery("SELECT id, role FROM t_roles", []);

// se sono un insert 
if ($op === 'i') {
    //valori di default
    $user = ["id" => -1
        , "name" => ""
        , "email" => ""
        , "enabled" => 0
        , "password" => ""
        , "email_verified_at" => null
        , "last_login" => null
        , "created_at" => null
        , "updated_at" => null
    ];
}

// submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // aggiorno i dati con quelli arrivati via submit
    updateArrayValues($user, $_POST);
    $user_roles = $_POST["roles"];
    
    // validazione dei dati passati
    if(  ss($user["name"]) || !isLengthValid($user["name"], 150, 4) )
    {
        $arr_err[] = "Il campo nome deve essere presente e deve avere una lunghezza compresa tra 4 e 150 caratteri";
    }
    if(  !isValidEmail($user["email"]) || !isLengthValid($user["email"], 150, 8)  )
    {
        $arr_err[] = "Il campo email deve essere presente e deve avere una lunghezza compresa tra 8 e 150 caratteri";
    }
    if(  !ss($user["email_verified_at"]) && !isValidDateTimeLocal($user["email_verified_at"])  )
    {
        $arr_err[] = "Il campo email_verified_at se presente deve essere un datetime valido nel formato Y-m-d\TH:i";
    }
    if(  !in_array($user["enabled"], [0,1])  )
    {
        $arr_err[] = "Il campo enabled deve essere presente e valere o 1 o 0";
    }
    if(  !is_array($user_roles) || count($user_roles) === 0  )
    {
        $arr_err[] = "Il campo user_roles deve essere presente e avere un valore selezionato";
    }
    if(  count($arr_err) === 0 )
    {
        $conta = getValueQuery("SELECT count(*) as conta 
            FROM users 
            WHERE email = :email and id != :id"
            , [":id" => $id, ":email" => $user["email"]]
            , "conta"
        );
        if(  intval($conta) > 0 )
        {
            $arr_err[] = "Il campo email è già presente, si prega di utilizzare una diversa email";
        }
    }
    if(  count($arr_err) === 0 )
    {
        //nessun problema sui dati - procedo a salvare
        $msgflash = ""; 

        // eseguo l'insert o l'update solo se non ho avuto errori
        $err = executeTransaction(function($conn, $user, $user_roles, $op) {
            global $id; // globale perchè la modifico
            global $msgflash; // globale perchè la modifico
    
            if ($op == 'i') {
                // insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, email_verified_at, enabled) VALUES (:name, :email, :email_verified_at, :enabled)");
                $stmt->bindParam(':name', $user["name"]);
                $stmt->bindParam(':email', $user["email"]);
                $stmt->bindParam(':email_verified_at', $user["email_verified_at"]);
                $stmt->bindParam(':enabled', $user["enabled"]);
                $stmt->execute();
                $id = $conn->lastInsertId();
                $msgflash .= "user id $id created" . "<br />";
            } elseif ($op == 'u') {
                // update current user
                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, email_verified_at = :email_verified_at, enabled = :enabled WHERE id = :id");
                $stmt->bindParam(':name', $user["name"]);
                $stmt->bindParam(':email', $user["email"]);
                $stmt->bindValue(':email_verified_at', $user["email_verified_at"], ss($user["email_verified_at"]) ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindParam(':enabled', $user["enabled"]);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                $msgflash .= "user id $id updated" . "<br />";
            }
    
            if(  !ss($user["password"])  )
            {
                $password_hash = password_hash($user["password"], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->bindParam(':password', $password_hash);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
            }
        
            //update roles
            $stmt = $conn->prepare("DELETE FROM users_roles WHERE id_user = :id_user");
            $stmt->execute(['id_user' => $id]);
            $msgflash .= "deleted " . $stmt->rowCount() . " roles " . "<br />";
            foreach($user_roles as $id_role)
            {
                $stmt = $conn->prepare("INSERT INTO users_roles (id_user, id_role) VALUES (:id_user, :id_role)");
                $stmt->bindParam(':id_user', $id);
                $stmt->bindParam(':id_role', $id_role);
                $stmt->execute();
            }
            $msgflash .= "inserted " . count($user_roles) . " roles " . "<br />";
        }, $user, $user_roles, $op);
    
        if(  ss($err)  ) {
            add_flash_message('msg', FlashMessageType::Success, $msgflash);
        } else {
            add_flash_message('msg', FlashMessageType::Warning, "Errore nella funzione di aggiornamento/insert: " . $err);
        }
        
        // get back to the list of card
        header('Location: user_list.php');
        exit;
    }
}
?>
<h2>Users: <?php echo $user["email"]; ?></h2>
<form method="post" enctype="multipart/form-data" class="formFields">
    
    <div class="mb-4">
        <button class="btn primary inline-block"><i class="bi bi-check"></i> Save</button>
        <a class="btn black inline-block" href="<?php echo $back; ?>"><i class="bi bi-arrow-return-left"></i> Back</a>
    </div>

    <?php if(count($arr_err) > 0): ?>
        <div>
            <?php print_errors($arr_err); ?>
        </div>
    <?php endif; ?>


    <div class="form-row">
        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" name="name" value="<?php echo pid_htmlspecialchars($user['name']); ?>" />
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="text" name="email" value="<?php echo pid_htmlspecialchars($user['email']); ?>" />
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="text" name="password" value="" />
        </div>
        <div class="form-group">
            <label for="enabled">Enabled:</label>
            <select name="enabled">
                <option value="1" <?php if($user["enabled"] == 1) echo "selected"; ?>>Si</option>
                <option value="0" <?php if($user["enabled"] == 0) echo "selected"; ?>>No</option>
            </select>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="email_verified_at">Email verified at:</label>
            <input type="datetime-local" name="email_verified_at" value="<?php echo pid_htmlspecialchars($user['email_verified_at']); ?>" />
        </div>
        <div class="form-group">
            <label for="last_login">Last login:</label>
            <input type="text" name="last_login" disabled value="<?php echo pid_htmlspecialchars($user['last_login']); ?>" />
        </div>
        <div class="form-group">
            <label for="created_at">Created at:</label>
            <input type="text" name="created_at" disabled value="<?php echo pid_htmlspecialchars($user['created_at']); ?>" />
        </div>
        <div class="form-group">
            <label for="updated_at">Updated at</label>
            <input type="text" name="updated_at" disabled value="<?php echo pid_htmlspecialchars($user['updated_at']); ?>" />
        </div>        
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="id_level">Roles:</label>
            <select id="roles" name="roles[]" size="2" multiple>
                <?php foreach ($t_roles as $r):  ?>
                    <option value="<?php echo $r['id']; ?>" <?php if (in_array($r['id'], $user_roles)) echo 'selected'; ?> >
                        <?php echo pid_htmlspecialchars($r['role']); ?>
                    </option>
                <?php endforeach; ?>
            </select>    
        </div>
        
    </div>

</form>

<?php ob_start(); ?>
<script>
    document.addEventListener("DOMContentLoaded", function(){
	
    });
</script>
<?php 
$my_script = ob_get_clean();
include '../src/footer.php';