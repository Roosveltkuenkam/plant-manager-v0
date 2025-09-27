<?php
// Connexion PDO à la base de données
$host = 'localhost';
$db   = 'plant_manager'; // À adapter si besoin
$user = 'root'; // À adapter si besoin
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Inclure PHPMailer via Composer
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Exemple: récupérer uniquement les plantes à arroser (en retard)
$stmt = $pdo->query("SELECT p.*, u.email 
                     FROM plants p 
                     JOIN users u ON p.user_id = u.id
                     WHERE DATE_ADD(p.last_watered, INTERVAL p.water_interval_days DAY) <= CURDATE()");

$plants = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($plants as $d) {
    try {
        $mail = new PHPMailer(true);

        // Config Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'coopfinance0@gmail.com';
        $mail->Password = 'ibep wnkh emud jojw'; // app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Expéditeur
        $mail->setFrom('coopfinance0@gmail.com', 'Plant Manager');

        // Destinataire
        if (!empty($d['email'])) {
            $mail->addAddress($d['email']);
        } else {
            continue; // saute si pas d’email
        }

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = "Rappel d'arrosage: {$d['name']}";
        $mail->Body = "
            Votre plante <b>{$d['name']}</b> a besoin d'arrosage.<br>
            Fréquence: {$d['water_interval_days']} jours.<br>
            Quantité: {$d['water_amount']}
        ";

        $mail->send();
        echo "Mail envoyé à {$d['email']}<br>";
    } catch (Exception $e) {
        echo "Erreur d'envoi vers {$d['email']} : {$mail->ErrorInfo}<br>";
    }
}

