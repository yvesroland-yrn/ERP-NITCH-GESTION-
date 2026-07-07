<?php
// ============================================================
//  NightERP Pro — Enregistrement de l'historique
//  Fichier : includes/historique.php
// ============================================================

function logAction(string $action, string $detail = '', string $module = ''): void {
    try {
        $db  = getDB();
        $uid = $_SESSION['user_id'] ?? null;
        $nom = $_SESSION['user']['nom'] ?? 'Système';
        $st  = $db->prepare("INSERT INTO historique (utilisateur_id, utilisateur_nom, action, detail, module) VALUES (?,?,?,?,?)");
        $st->execute([$uid, $nom, $action, $detail, $module]);
    } catch (Exception $e) {
        // Ne pas bloquer l'app pour un log raté
    }
}
