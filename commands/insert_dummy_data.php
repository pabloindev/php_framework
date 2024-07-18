<?php
//insert dummy data fo a specific deck
//please create a deck, get the id and then run this script
//remember to set also $id_deck
//remember to set also $numero_cards

//return;
require_once '../vendor/autoload.php';
$faker = Faker\Factory::create();

$pdo = new PDO('mysql:host=localhost;dbname=php_framework', 'root', 'root');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$users = 150; // Numero di cards da generare

// Funzione per generare stringhe casuali
function generateRandomString($length = 10) {
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function generateRandomDatetime() {
    $start = new DateTime('-1 year');
    $end = new DateTime();
    $timestamp = rand($start->getTimestamp(), $end->getTimestamp());
    $randomDate = new DateTime();
    $randomDate->setTimestamp($timestamp);
    return $randomDate->format('Y-m-d H:i:s');
}

// Inserisci 10 categorie nella tabella t_categories
// $categories = [];
// for ($i = 1; $i <= 10; $i++) {
//     $categoryName = 'Category ' . generateRandomString();
//     $categories[] = $categoryName;
//     $stmt = $pdo->prepare("INSERT INTO t_categories (id_category, id_deck, category) VALUES (:id_category, :id_deck, :category)");
//     $stmt->execute(['id_category' => $i, 'id_deck' => $id_deck, 'category' => $categoryName]);
// }

// Ottieni gli ID delle categorie appena inserite
//$categoryIds = $pdo->query("SELECT id_category FROM t_categories WHERE id_deck = $id_deck")->fetchAll(PDO::FETCH_COLUMN);

// Inserisci cards nella tabella cards e associa casualmente le categorie
for ($i = 1; $i <= $users; $i++) {
    $front = 'Front ' . generateRandomString();
    $back = 'Back ' . generateRandomString();
    $id_level = rand(1, 4); // Supponendo ci siano 4 livelli
    $time_revision = generateRandomDatetime();

    $stmt = $pdo->prepare("INSERT INTO users (name, email, email_verified_at, password, enabled) 
        VALUES (:name, :email, :email_verified_at, :password, :enabled)");
    $stmt->execute([
        'name' => $faker->name()
        , 'email' => $faker->email()
        , 'email_verified_at' => generateRandomDatetime()
        , 'password' => ""
        , 'enabled' => $faker->randomElement([0, 1])
    ]);

    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO users_roles (id_user, id_role) VALUES (:id_user, :id_role)");
    $stmt->execute(['id_user' => $id, 'id_role' => 2]);

}

echo "Dati inseriti con successo!";
?>