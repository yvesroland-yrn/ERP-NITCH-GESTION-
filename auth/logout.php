<?php
require_once __DIR__ . '/../config/config.php';
logAction('Déconnexion', '', 'Auth');
session_destroy();
header('Location: ../auth/login.php');
exit;
