<?php
// api/send_notifications.php
$host = 'localhost'; $db='plant_manager'; $user='root'; $pass='';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);


// Récupérer plantes dues avec email du propriétaire
$stmt = $pdo->query('SELECT p.*, u.email FROM plants p JOIN users u ON p.user_id = u.id');
$plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
$due = [];
foreach ($plants as $p) {
    $ref = $p['last_watered'] ?? $p['created_at'];
    $next = date('Y-m-d H:i:s', strtotime($ref . ' +' . intval($p['water_interval_days']) . ' days'));
    if (strtotime($next) <= time()) $due[] = $p;
}
if (empty($due)) exit;


// Envoyer un mail à l'utilisateur propriétaire de la plante
foreach ($due as $d) {
    $to = $d['email'] ?? null;
    if (!$to) continue;
    $subject = "Rappel d'arrosage: {$d['name']}";
    $message = "Votre plante '{$d['name']}' a besoin d'arrosage. Fréquence: {$d['water_interval_days']} jours. Quantité: {$d['water_amount']}\nConnectez-vous à votre application pour marquer l'arrosage.";
    mail($to, $subject, $message);
}