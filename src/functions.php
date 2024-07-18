<?php
if (!defined('ACCESS_ALLOWED')) {
    die('Direct access not permitted');
}

// ------------------------------ OTHER ------------------------------
function dd($variable, $bExit = true)
{
    echo "<pre>";
    print_r($variable);
    echo "</pre>";
    if($bExit)
        die();
}

function conf($key)
{
    global $conf;
    return $conf[$key] ?? null;
}

// ------------------------------ STRING ------------------------------

function isNullOrEmptyOrWhitespace($str) {
    return (!isset($str) || trim($str) === '');
}

function ss($str) {
    return isNullOrEmptyOrWhitespace($str);
}

// custom trim per gestire il null passato alla funzione nativa trim()
function pid_trim(?string $value)
{
    return trim($value ?? '') ;
} 

// custom htmlspecialchars per gestire il null passato alla funzione nativa htmlspecialchars()
function pid_htmlspecialchars(?string $value)
{
    return htmlspecialchars($value ?? '') ;
} 

// No trailing separator - ex. C:\www\logs\myscript.txt
//$logFile = joinPathParts([getcwd(), 'logs', 'myscript.txt']);
function joinPathParts($parts)
{
    return implode(
        DIRECTORY_SEPARATOR, 
        array_map(
            function($s){
                return rtrim($s,DIRECTORY_SEPARATOR);
        }, 
        $parts)
    );
}


/**
 * Check if the given string is a valid email address.
 * 
 * @param string $email The email address to validate.
 * @return bool Returns true if the email address is valid, false otherwise.
 */
function isValidEmail($email)
{
    if ($email === null) {
        return false;
    }

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidDateTimeLocal($datetime) {
    $format = 'Y-m-d\TH:i';
    $d = DateTime::createFromFormat($format, $datetime);
    return $d && $d->format($format) === $datetime;
}

/**
 * Check if the length of the string is within the specified range.
 * 
 * @param string $str The string to check.
 * @param int $maxLength The maximum length of the string.
 * @param int $minLength The minimum length of the string. Default is 0.
 * @return bool Returns true if the string length is within the range, false otherwise.
 */
function isLengthValid($str, $maxLength, $minLength = 0)
{
    if ($str === null) {
        return false;
    }

    $length = strlen($str);

    return $length >= $minLength && $length <= $maxLength;
}

/**
 * Check if the given string is a valid password.
 * 
 * @param string $password The password to validate.
 * @param bool $requireSpecialChar If true, the password must contain at least one special character. Default is false.
 * @return bool Returns true if the password is valid, false otherwise.
 */
function isValidPassword($password, $requireSpecialChar = false)
{
    if ($password === null) {
        return false;
    }

    // Check for minimum length
    if (strlen($password) < 6) {
        return false;
    }

    // Check for at least one letter and one number
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/\d/', $password)) {
        return false;
    }

    // Check for special character if required
    // list special chars
    // !@#$%^&*()-_=+{};:,<.>
    if ($requireSpecialChar && !preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
        return false;
    }

    return true;
}

/**
 * Encrypts a given variable.
 * 
 * @param mixed $data The data to be encrypted.
 * @return string The encrypted string.
 */
function encrypt($data) {
    $data = (string)$data; // Convert any variable type to string
    $encryption_key = hash('sha256', conf("APP_ENCRYPTION_KEY")); // Ensure the key is 32 bytes
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')); // Generate a random Initialization Vector (IV)
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv); // Encrypt the data using AES-256-CBC
    return base64_encode($iv . $encrypted); // Combine IV and encrypted data, then encode in base64
}

/**
 * Decrypts a given encrypted string.
 * 
 * @param string $data The encrypted data to be decrypted.
 * @return string|null The decrypted string if successful, or NULL if decryption fails.
 */
function decrypt($data) {
    $data = base64_decode($data); // Decode the base64 encoded data
    $encryption_key = hash('sha256', conf("APP_ENCRYPTION_KEY")); // Ensure the key is 32 bytes
    $iv_length = openssl_cipher_iv_length('aes-256-cbc'); // Get the IV length
    $iv = substr($data, 0, $iv_length); // Extract the IV from the data
    $encrypted = substr($data, $iv_length); // Extract the encrypted data
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryption_key, 0, $iv); // Decrypt the data using AES-256-CBC
    return $decrypted === false ? NULL : $decrypted; // Return the decrypted data or NULL if decryption fails
}

// ------------------------------ URL FUNCTIONS ------------------------------
function goto_url($url) {
    header('Location: ' . $url);
}

function get_crrent_url(){
    $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    return $url;
}

function delKeyValFromQueryString($url, $chiave) {
    // Separare l'URL in parte principale e query string
    $partiUrl = parse_url($url);
    
    // Se non c'è query string, restituisce l'URL originale
    if (!isset($partiUrl['query'])) {
        return $url;
    }

    // Analizzare la query string in un array associativo
    parse_str($partiUrl['query'], $parametri);
    
    // Rimuovere la coppia chiave-valore specificata
    if (isset($parametri[$chiave])) {
        unset($parametri[$chiave]);
    }

    // Ricostruire la query string
    $nuovaQueryString = http_build_query($parametri);

    // Ricostruire l'URL completo
    $nuovoUrl = $partiUrl['scheme'] . '://' . $partiUrl['host'];
    if (isset($partiUrl['path'])) {
        $nuovoUrl .= $partiUrl['path'];
    }
    if ($nuovaQueryString) {
        $nuovoUrl .= '?' . $nuovaQueryString;
    }
    if (isset($partiUrl['fragment'])) {
        $nuovoUrl .= '#' . $partiUrl['fragment'];
    }

    return $nuovoUrl;
}

// ------------------------------ CSRF Token ------------------------------
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Usa questa funzione per generare il token nel form
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ------------------------------ LOGIN LOGUT ------------------------------
function isAuthenticated($arr_roles = [])  : bool 
{
    if(  count($arr_roles) === 0  )
    {
        return isset($_SESSION['id']) && is_numeric($_SESSION['id']);
    }
    else 
    {
        foreach($_SESSION['roles'] as $role)
        {
            if(in_array($role, $arr_roles))
            {
                return true;
            }
        }
        return false;
    }
}

// update ips table with the ip of the current user
function fialed_page_actions($ip)
{
    global $conn;
    
    // insert record into ips 
    $stmt = $conn->prepare("INSERT INTO ips (ip ,time_login) VALUES (:ip, CURRENT_TIMESTAMP)");
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
}

// mi dice se far vedere o meno la pagina a seconda se l'utente ha fatto troppe azioni errate
function is_page_enabled($ip)
{
    global $conn;
    
    //get number of failed login
    $stmt = $conn->prepare("SELECT COUNT(*) as conta FROM ips WHERE ip = :ip AND time_login > (now() - interval " . conf("FAILED_OPERATION_INTERVAL") . ")");
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
    $row = $stmt->fetch();
    
    if(  intval($row["conta"]) >= intval(conf("FAILED_OPERATION_NUMBER"))  ){
        return false;
    } else {
        return true;
    }
}

// ------------------------------ DIR AND FILES ------------------------------
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return false;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    return rmdir($dir);
}


// ------------------------------ GOOGLE CAPTCHA ------------------------------
function captcha_enabled()
{
    $RECAPTCHA_SITEKEY = conf("RECAPTCHA_SITEKEY");
    $RECAPTCHA_SECRETKEY = conf("RECAPTCHA_SECRETKEY");

    if(  !ss($RECAPTCHA_SITEKEY) && !ss($RECAPTCHA_SECRETKEY)  ) {
        return true;
    } else {
        return false;
    }
}

function captcha_check()
{
    $request = \MVC3Space\Request::getInstance();
    $recaptchaResponse = $request->input("g-recaptcha-response");

    // Your secret key
    $secret = conf("RECAPTCHA_SECRETKEY");

    // Make the request to verify the reCAPTCHA response
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptchaResponse}");
    $responseKeys = json_decode($response, true);

    if(intval($responseKeys["success"]) !== 1) {
        return 'reCAPTCHA verification failed. Please try again';
    } else {
        // Process the form data
        //echo 'reCAPTCHA verification successful. Form data can be processed.';
        return "";
    }
}

// ------------------------------ MAIL ------------------------------
function send_email($to, $subject, $message, $headers)
{
    if(conf("MAIL_PRINTLOG") === true)
    {
        MVC3Space\Log::info('to: ' . $to);
        MVC3Space\Log::info('subject: ' . $subject);
        MVC3Space\Log::info('message: ' . $message);
        MVC3Space\Log::info('headers: ' . $headers);
        return [];
    }
    else 
    {
        //mail($to, $subject, $message, $headers);
        //$errors = smtp_mail_nodep($to, $subject, $message);
        $errors = smtp_mail_phpmailer($to, $subject, $message);
        return $errors;
    }
}

/**
 * Send an email using PHPMailer.
 * 
 * composer require phpmailer/phpmailer
 * 
 * @param string $to Recipient email address.
 * @param string $subject Subject of the email.
 * @param string $message Body of the email.
 * @return array Returns an array with error messages if any, or an empty array if the email was sent successfully.
 */
function smtp_mail_phpmailer($to, $subject, $message) {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $errors = [];

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = conf('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = conf('MAIL_USERNAME');
        $mail->Password = conf('MAIL_PASSWORD');
        $mail->SMTPSecure = 'ssl';  // or PHPMailer::ENCRYPTION_SMTPS
        $mail->Port = conf('MAIL_PORT');

        // Recipients
        $mail->setFrom(conf('MAIL_FROM_ADDRESS'), conf('MAIL_FROM_NAME'));
        $mail->addAddress($to);

        // Content
        $mail->isHTML(false); // Send as plain text
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
    } catch (Exception $e) {
        $errors[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    return $errors;
}


// --------------------------------- LOGGING ---------------------------------
/**
 * Function to write a log message.
 *
 * @param string $entryType The type of message.
 * @param string $message The log message.
 */
function writeLog($entryType, $message)
{
    // Check if the log folder exists, otherwise create it
    // echo "<pre>";
    // print(LOG_PATH_FOLDER);
    // print($message);
    // echo "</pre>";
    // die();
    if (!file_exists(conf("LOG_PATH_FOLDER"))) {
        mkdir(conf("LOG_PATH_FOLDER"), 0777, true);
    }

    // Construct the log file path
    $logFilePath = conf("LOG_PATH_FOLDER") . '/' . conf("LOG_FILE_NAME");

    //Clear cache and check filesize again
    clearstatcache();

    // Check if the log file exceeds the maximum allowed size
    if (file_exists($logFilePath) && filesize($logFilePath) > convertSize(conf("LOG_FILE_MAXBYTES"))) {
        rotateLog($logFilePath);
    }

    // Get the current date and time with milliseconds
    $dateTime = date('Y-m-d H:i:s') . substr((string)microtime(), 1, 4);

    // Create the log entry
    $logEntry = $dateTime . ' - ' . $entryType . ' - ' . $message . PHP_EOL;

    // Write the log entry to the file
    file_put_contents($logFilePath, $logEntry, FILE_APPEND);
}

/**
 * Function to rotate the log file.
 *
 * @param string $logFilePath The log file path.
 */
function rotateLog($logFilePath)
{
    $timestamp = date('Ymd_His');
    $newName = $logFilePath . '.' . $timestamp;
    rename($logFilePath, $newName);
}

/**
 * Function to convert a file size to bytes.
 *
 * @param string $size The size with unit (e.g., '5MB').
 * @return int The size in bytes.
 */
function convertSize($size)
{
    $unit = strtoupper(substr($size, -2));
    $number = (int)substr($size, 0, -2);
    
    switch ($unit) {
        case 'TB':
            return $number * pow(1024, 4);
        case 'GB':
            return $number * pow(1024, 3);
        case 'MB':
            return $number * pow(1024, 2);
        case 'KB':
            return $number * 1024;
        case 'B':
        default:
            return $number;
    }
}


// ------------------------------ FLASH SESSION ------------------------------
enum FlashMessageType: string {
    case Danger = 'danger';
    case Success = 'success';
    case Warning = 'warning';
    case Info = 'info';
}

function add_flash_message(string $id, FlashMessageType $type, string $message): void {
    // Initialize the flash_messages array in session if not already set
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    // Add or update the message with the given ID
    $_SESSION['flash_messages'][$id] = ['type' => $type, 'message' => $message];
}

function clear_flash_messages(): void {
    // Unset the flash_messages array in the session
    unset($_SESSION['flash_messages']);
}

function get_flash_message(string $id): ?array {
    // Check if there are any flash messages in the session
    if (!isset($_SESSION['flash_messages']) || !isset($_SESSION['flash_messages'][$id])) {
        return null;
    }

    // Get the message with the given ID
    $msg = $_SESSION['flash_messages'][$id];
    
    // Remove the message from the session
    unset($_SESSION['flash_messages'][$id]);

    // Add the correct alert class based on type
    $msg['class'] = match ($msg['type']) {
        FlashMessageType::Danger => 'alert alert-danger',
        FlashMessageType::Success => 'alert alert-success',
        FlashMessageType::Warning => 'alert alert-warning',
        FlashMessageType::Info => 'alert alert-info',
        default => 'alert alert-secondary'
    };

    return $msg;
}

function clean_sessions()
{
    unset($_SESSION['email']);
    unset($_SESSION['id']);
    unset($_SESSION['roles']);
    session_destroy();
}

// ------------------------------ POST SUBMIT ------------------------------
// faccio il merge dell'array $_POST con i dati recuperati nella card?scheda
function updateArrayValues(&$originalArray, $newData) {
    foreach ($newData as $key => $value) {
        if (is_array($value) && isset($originalArray[$key]) && is_array($originalArray[$key])) {
            $originalArray[$key] = $value; // Aggiorna l'array
        } elseif (array_key_exists($key, $originalArray)) {
            $originalArray[$key] = $value; // Aggiorna il valore
        }
    }
}

// ------------------------------ PER HTML ------------------------------
// print errors for submit
function print_errors($error)
{
    if (is_string($error) && !ss($error)) 
    { 
        echo "<div class='alert alert-danger'>$error</div>"; 
    } 
    elseif (is_array($error))
    {
        foreach($error as $er)
        {
            echo "<div class='alert alert-danger'>$er</div>";
        }
    }
}

// print message between views
function print_flash($key)
{
    $flashMessage = get_flash_message($key);
    if (  $flashMessage && !ss($flashMessage['message'])  ) 
    {
        echo '<div class="' . $flashMessage['class'] . '">' . $flashMessage['message'] . '</div>';
    }
}


// ------------------------------ SQL PDO ------------------------------
// Funzione per eseguire una query con PDO
function getRowsQuery($query, $params)
{
    global $conn;
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getRowQuery($query, $params)
{
    global $conn;
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC); // Restituisce la prima riga come array associativo
}
function getValueQuery($query, $params, $column)
{
    global $conn;
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC); // Restituisce la prima riga come array associativo
    return $row[$column] ?? null; // Restituisce il valore della colonna specificata o null se non esiste
}

function executeTransaction(callable $callback, ...$params) {
    
    global $conn;
    try {
        $conn->beginTransaction();
        
        // Esegui la callback
        $callback($conn, ...$params);

        $conn->commit();
        return "";
    } catch (PDOException $e) {
        $conn->rollBack();
        return $e;
    }
}

// ------------------------------ TABELLA RISULTATI ------------------------------
// Funzione per generare i link di paginazione
function generatePaginationLinks($totalPages, $currentPage)
{
    $paginationHtml = '<nav class="pagination"><ul>';
    $startPage = max(1, $currentPage - conf("PAGING_NUMBERLINK_LEFT_RIGHT"));
    $endPage = min($totalPages, $currentPage + conf("PAGING_NUMBERLINK_LEFT_RIGHT"));

    // Link alla prima pagina
    $paginationHtml .= '<li><a href="#" data-page="1">&laquo; First</a></li>';

    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = $i == $currentPage ? ' active' : '';
        $paginationHtml .= '<li class="' . $activeClass . '"><a href="#" data-page="' . $i . '">' . $i . '</a></li>';
    }

    // Link all'ultima pagina
    $paginationHtml .= '<li><a href="#" data-page="' . $totalPages . '">Last &raquo;</a></li>';

    $paginationHtml .= '</ul></nav>';
    return $paginationHtml;
}

// mi ritorna l'indice dei record che sto visualizzando
function calculateRecordIndices($currentPage, $rowsPerPage, $totalRecords)
{
    $firstRecordIndex = ($currentPage - 1) * $rowsPerPage + 1;
    $lastRecordIndex = min($totalRecords, $currentPage * $rowsPerPage);
    return [$firstRecordIndex, $lastRecordIndex];
}

// ------------------------------ OTHER ------------------------------

// get max id from t_categories for a specific deck
function get_max_id_from_categories($id_deck, $conn) {
    if($id_deck)
    {
        $query = "SELECT max(id_category)+1 as conta FROM t_categories WHERE id_deck = :id_deck";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_deck', $id_deck, PDO::PARAM_INT);
        $stmt->execute();
        $conta = $stmt->fetchColumn();
        return intval($conta);
    }
    else 
        return 1;
}

// get the name of the deck from id
function get_deck_name($id_deck, $conn) {
    $query = "SELECT deck FROM decks WHERE id_deck = :id_deck";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_deck', $id_deck, PDO::PARAM_INT);
    $stmt->execute();
    $deck_name = $stmt->fetchColumn();
    return $deck_name;
}

// get the list of categories from a specific deck
function get_categories($id_deck, $conn) {
    $query = "SELECT id_category, category FROM t_categories WHERE id_deck = :id_deck";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_deck', $id_deck, PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $res;
}


// get data for a specific card
function getCardDetails($id_card, $conn) {
    $stmt = $conn->prepare("SELECT front, back, id_level FROM cards WHERE id_card = :id_card");
    $stmt->bindParam(':id_card', $id_card, PDO::PARAM_INT);
    $stmt->execute();
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    //get categories
    $query = "SELECT id_category FROM categories WHERE id_card = :id_card";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_card', $id_card, PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $card['id_categories'] = array_column($res, 'id_category');

    return $card; // ['front' => '', 'back' => '', 'id_level' => ''];
}

// get data for a specific card
function getCardAttachmets($id_card, $conn) {
    $stmt = $conn->prepare("SELECT * FROM attachments WHERE id_card = :id_card");
    $stmt->execute(['id_card' => $id_card]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $attachments;
}


// get all levels
function getLevels($conn) {
    $query = "SELECT id_level, level FROM t_levels";
    $stmt = $conn->query($query);
    $levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $levels;
}

// get all categories from a specific deck
function getCategories($id_deck, $conn) {
    $query = "SELECT id_category, category FROM t_categories where id_deck = :id_deck";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_deck', $id_deck, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

// delete a specific card
function deletecard($id_card, $conn)
{
    $msgflash = "";

    $stmt = $conn->prepare("DELETE FROM cards WHERE id_card = :id_card");
    $stmt->bindParam(':id_card', $id_card, PDO::PARAM_INT);
    $stmt->execute();
    $msgflash .= "card $id_card deleted from cards" . "<br />";

    $stmt = $conn->prepare("DELETE FROM categories WHERE id_card = :id_card");
    $stmt->bindParam(':id_card', $id_card, PDO::PARAM_INT);
    $stmt->execute();
    $msgflash .= "deleted" . $stmt->rowCount() . " records from categories " . "<br />";
    
    
    $attachments = getCardAttachmets($id_card, $conn);
    foreach ($attachments as $attachment) {
        if(file_exists($attachment['path'])){
            $resunlink = unlink($attachment['path']); // delete single file
            if($resunlink){
                $msgflash .= "unlink file " . $attachment['path'] . " OK" . "<br />";
            } else  {
                $msgflash .= "unlink file " . $attachment['path'] . " FAILURE " . "<br />";
            }
        } else {
            $msgflash .= "file " . $attachment['path'] . " does not exist" . "<br />";
        }
    }

    // delete attachments of the card 
    $stmt = $conn->prepare("DELETE FROM attachments WHERE id_card = :id_card");
    $stmt->bindParam(':id_card', $id_card, PDO::PARAM_INT);
    $stmt->execute();
    $msgflash .= "deleted" . $stmt->rowCount() . " records from attachments " . "<br />";

    //delete folder on disk
    deleteDirectory(conf("ATTACH_FOLDER") . '/' . $id_card);
    $msgflash .= "trying to delete folder " . conf("ATTACH_FOLDER") . '/' . $id_card . "<br />";

    return $msgflash;
}