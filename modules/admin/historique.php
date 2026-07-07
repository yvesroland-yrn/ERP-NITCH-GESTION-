<?php
// ============================================================
//  NightERP Pro — Historique des actions
//  Fichier : modules/admin/historique.php
//  Accès   : Administrateur uniquement
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireLogin();
if (!isAdmin()) redirect('../dashboard.php', 'Accès refusé.', 'error');
$db = getDB();

$hist = $db->query("SELECT * FROM historique ORDER BY id DESC LIMIT 200")->fetchAll();
$modIcons = ['POS'=>'fa-cash-register','Stock'=>'fa-warehouse','Produits'=>'fa-box-open','Commandes'=>'fa-receipt','Tables'=>'fa-chair','Personnel'=>'fa-id-badge','Achats'=>'fa-truck','Dépenses'=>'fa-file-invoice-dollar','Caisse'=>'fa-sack-dollar','Promotions'=>'fa-percent','Réservations'=>'fa-calendar','Admin'=>'fa-users-gear','Fournisseurs'=>'fa-handshake','Auth'=>'fa-right-to-bracket'];

$pageTitle = '<i class="fa-solid fa-clock-rotate-left" style="color:var(--gold)"></i> Historique des actions';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><i class="fa-solid fa-clock-rotate-left text-gold"></i> Historique des actions</h1><p>Traçabilité complète du système</p></div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Date/Heure</th><th>Utilisateur</th><th>Action</th><th>Détail</th><th>Module</th></tr></thead>
      <tbody>
        <?php if($hist): foreach($hist as $h): ?>
        <tr>
          <td class="td-muted" style="white-space:nowrap"><?= dateStr($h['created_at']) ?></td>
          <td><div class="flex items-center" style="gap:6px">
            <div class="user-av" style="width:24px;height:24px;font-size:10px"><?= strtoupper(substr($h['utilisateur_nom']??'?',0,1)) ?></div>
            <?= e($h['utilisateur_nom'] ?? 'Système') ?>
          </div></td>
          <td><strong><?= e($h['action']) ?></strong></td>
          <td class="td-muted"><?= e($h['detail'] ?: '—') ?></td>
          <td><span class="pill pill-blue" style="font-size:10px"><i class="fa-solid <?= $modIcons[$h['module']] ?? 'fa-circle' ?>"></i> <?= e($h['module'] ?: '—') ?></span></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--txt2)">Aucune action enregistrée</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
