<?php
// ============================================================
//  NightERP Pro — Configuration générale
//  Fichier : config/config.php
// ============================================================

define('APP_NAME',    'NightERP Pro');
define('APP_VERSION', '1.0.0');

// Chemins absolus
define('ROOT_PATH',    dirname(__DIR__) . '/');
define('UPLOAD_PATH',  ROOT_PATH . 'assets/uploads/produits/');
define('UPLOAD_URL',   'assets/uploads/produits/');

// Taille max upload image produit (5 Mo)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMG_TYPES', ['image/jpeg','image/png','image/webp','image/gif']);

// Modules disponibles et leurs pages
define('MODULES', [
    'principal' => [
        'label' => 'Principal',
        'icon'  => 'fa-gauge-high',
        'pages' => ['dashboard','pos','tables','commandes'],
    ],
    'stock' => [
        'label' => 'Stock & Achats',
        'icon'  => 'fa-warehouse',
        'pages' => ['produits','stock','achats','fournisseurs'],
    ],
    'personnes' => [
        'label' => 'Personnes',
        'icon'  => 'fa-users',
        'pages' => ['personnel','reservations'],
    ],
    'finances' => [
        'label' => 'Finances',
        'icon'  => 'fa-sack-dollar',
        'pages' => ['caisse','depenses','comptabilite','rapports'],
    ],
]);

// Démarrage de session sécurisé
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/fonctions.php';
require_once ROOT_PATH . 'includes/historique.php';
