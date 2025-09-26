<?php
header("Content-Type: application/json");
session_start();

// Connexion PDO
$host = "localhost";
$dbname = "plant_manager";
$username = "root";   // adapte selon ton setup
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Connexion échouée : " . $e->getMessage()]);
    exit;
}

// petite fonction utilitaire
function jsonResponse($data) {
    echo json_encode($data);
    exit;
}

// récupération action
$action = $_GET['action'] ?? $_POST['action'] ?? null;
if (!$action) {
    jsonResponse(["error" => "Aucune action fournie"]);
}

/**
 * 1. Ajouter une plante
 */
if ($action === "add_plant") {
    $name = $_POST['name'] ?? null;
    $species = $_POST['species'] ?? null;
    $purchase_date = $_POST['purchase_date'] ?? null;
    $water_amount = $_POST['water_amount'] ?? null;
    $water_interval_days = $_POST['water_interval_days'] ?? 7;
    $image_path = $_POST['image_path'] ?? null;
    $user_id = $_SESSION['user_id'];
    if (!$name) jsonResponse(["error" => "Le nom de la plante est requis"]);
    $stmt = $pdo->prepare("INSERT INTO plants (user_id, name, species, purchase_date, image_path, water_amount, water_interval_days) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $species, $purchase_date, $image_path, $water_amount, $water_interval_days]);
    $id = $pdo->lastInsertId();
    jsonResponse(["ok" => true, "id" => $id]);
}

/**
 * 2. Lister les plantes
 */
if ($action === "list_plants") {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM plants WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Ajoute la propriété 'due' à chaque plante
    foreach ($plants as &$p) {
        $ref = $p['last_watered'] ?? $p['created_at'];
        $next = date('Y-m-d H:i:s', strtotime($ref . ' +' . intval($p['water_interval_days']) . ' days'));
        $p['due'] = (strtotime($next) <= time());
    }
    jsonResponse(["ok" => true, "plants" => $plants]);
}

/**
 * 3. Marquer une plante comme arrosée
 */
if ($action === "water_plant") {
    $plant_id = $_POST['plant_id'] ?? $_GET['plant_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $note = $_POST['note'] ?? null;
    if (!$plant_id) jsonResponse(["error" => "ID plante manquant"]);
    // Insérer dans l'historique
    $stmt = $pdo->prepare("INSERT INTO watering_history (plant_id, amount, note) VALUES (?, ?, ?)");
    $stmt->execute([$plant_id, $amount, $note]);
    // Mettre à jour la date d'arrosage de la plante
    $stmt = $pdo->prepare("UPDATE plants SET last_watered = NOW() WHERE id = ?");
    $stmt->execute([$plant_id]);
    jsonResponse(["ok" => true]);
}

/**
 * 4. Récupérer l'historique d'arrosage d'une plante
 */
if ($action === "watering_history") {
    $plant_id = $_POST['plant_id'] ?? $_GET['plant_id'] ?? null;
    if (!$plant_id) jsonResponse(["error" => "ID plante manquant"]);
    $stmt = $pdo->prepare("SELECT * FROM watering_history WHERE plant_id = ? ORDER BY watered_at DESC");
    $stmt->execute([$plant_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(["ok" => true, "history" => $history]);
}

/**
 * 5. Supprimer une plante
 */
if ($action === "delete_plant") {
    $plant_id = $_POST['plant_id'] ?? $_GET['plant_id'] ?? null;
    if (!$plant_id) jsonResponse(["error" => "ID plante manquant"]);
    // Suppression (l'historique est supprimé via ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM plants WHERE id = ?");
    $stmt->execute([$plant_id]);
    jsonResponse(["ok" => true]);
}

// Auth: inscription
if ($action === "register") {
    $name = $_POST['name'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $photo = $_POST['photo'] ?? null;
    if (!$email || !$password) jsonResponse(["error" => "Email et mot de passe requis"]);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) jsonResponse(["error" => "Email déjà utilisé"]);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, photo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hash, $photo]);
    $_SESSION['user_id'] = $pdo->lastInsertId();
    jsonResponse(["ok" => true]);
}

// Auth: connexion
if ($action === "login") {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    if (!$email || !$password) jsonResponse(["error" => "Email et mot de passe requis"]);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !password_verify($password, $user['password'])) jsonResponse(["error" => "Identifiants invalides"]);
    $_SESSION['user_id'] = $user['id'];
    jsonResponse(["ok" => true, "user" => ["id"=>$user['id'], "name"=>$user['name'], "email"=>$user['email'], "photo"=>$user['photo']]]);
}

// Auth: déconnexion
if ($action === "logout") {
    session_destroy();
    jsonResponse(["ok" => true]);
}

// Profil utilisateur connecté
if ($action === "me") {
    if (!isset($_SESSION['user_id'])) jsonResponse(["user"=>null]);
    $stmt = $pdo->prepare("SELECT id, name, email, photo, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    jsonResponse(["user" => $user]);
}

// Modifier profil
if ($action === "update_profile") {
    if (!isset($_SESSION['user_id'])) jsonResponse(["error"=>"Non connecté"]);
    $name = $_POST['name'] ?? null;
    $photo = $_POST['photo'] ?? null;
    $password = $_POST['password'] ?? null;
    $fields = [];
    $params = [];
    if ($name) { $fields[] = 'name=?'; $params[] = $name; }
    if ($photo) { $fields[] = 'photo=?'; $params[] = $photo; }
    if ($password) { $fields[] = 'password=?'; $params[] = password_hash($password, PASSWORD_DEFAULT); }
    if ($fields) {
        $params[] = $_SESSION['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET ".implode(',', $fields)." WHERE id = ?");
        $stmt->execute($params);
    }
    jsonResponse(["ok"=>true]);
}

jsonResponse(["error" => "Action inconnue"]);
