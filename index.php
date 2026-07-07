<?php
// ============================================================
//  NightERP Pro — Point d'entrée
//  Fichier : index.php
// ============================================================
require_once __DIR__ . '/config/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: modules/dashboard.php');
} else {
    header('Location: auth/login.php');
}
exit;
