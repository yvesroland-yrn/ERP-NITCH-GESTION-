<?php
// ============================================================
//  NightERP Pro — Caisse & Ventes
//  Fichier : modules/caisse/caisse.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('finances');
$db   = getDB();
$root = rootPath(__FILE__);

$ventes = $db->query("SELECT c.*, t.nom AS table_nom FROM commandes c LEFT JOIN tables_club t ON t.id = c.table_id WHERE c.statut='Payée' ORDER BY c.id DESC")->fetchAll();
$tot = array_sum(array_column($ventes,'total'));
$esp = array_sum(array_column(array_filter($ventes, fn($v)=>$v['mode_paiement']==='Espèces'),'total'));
$mob = array_sum(array_column(array_filter($ventes, fn($v)=>$v['mode_paiement']==='Mobile Money'),'total'));
$crt = array_sum(array_column(array_filter($ventes, fn($v)=>$v['mode_paiement']==='Carte bancaire'),'total'));

$pageTitle = '<i class="fa-solid fa-sack-dollar" style="color:var(--gold)"></i> Caisse & Ventes';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><i class="fa-solid fa-sack-dollar text-gold"></i> Caisse &amp; Ventes</h1><p>Suivi des encaissements</p></div>
<div class="g4 mb20">
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-sack-dollar"></i> Total encaissé</div><div class="stat-value gold"><?= fmt($tot) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-money-bill-wave"></i> Espèces</div><div class="stat-value"><?= fmt($esp) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-brands fa-google-pay"></i> Mobile Money</div><div class="stat-value"><?= fmt($mob) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-credit-card"></i> Carte bancaire</div><div class="stat-value"><?= fmt($crt) ?></div></div>
</div>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-list"></i> Journal des ventes</div></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Heure</th><th>Table</th><th>Articles</th><th>Montant</th><th>Mode paiement</th></tr></thead>
      <tbody>
        <?php if($ventes): foreach($ventes as $v):
          $details = getCommandeDetails($db, $v['id']);
          $resume = implode(', ', array_map(fn($i)=>($i['produit_nom'] ?? 'Produit').' ×'.$i['qte'],$details));
        ?>
        <tr>
          <td class="td-muted">#<?= str_pad($v['id'],4,'0',STR_PAD_LEFT) ?></td>
          <td class="td-muted"><?= dateStr($v['created_at']) ?></td>
          <td class="td-muted"><?= e($v['table_nom'] ?: ($v['table_id'] ? 'Table #'.$v['table_id'] : '—')) ?></td>
          <td class="td-muted" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($resume) ?></td>
          <td class="td-gold"><?= fmt($v['total']) ?></td>
          <td><?= badge($v['mode_paiement']??'—','blue') ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--txt2)">Aucune vente</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
