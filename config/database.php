<?php
// ============================================================
//  NightERP Pro — Configuration de la base de données
//  Fichier : config/database.php
// ============================================================

define('DB_HOST',     'localhost');
define('DB_NAME',     'nighterp');
define('DB_USER',     'root');       // ← Changer selon votre serveur
define('DB_PASSWORD', '');           // ← Changer selon votre serveur
define('DB_CHARSET',  'utf8mb4');

// Connexion PDO (utilisée dans tout le projet)
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:30px;color:#E74C3C">
                    <h2>Erreur de connexion à la base de données</h2>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Vérifiez les paramètres dans <strong>config/database.php</strong></p>
                 </div>');
        }
    }
    return $pdo;
}
