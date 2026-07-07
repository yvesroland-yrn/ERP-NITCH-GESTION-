<?php
// ============================================================
//  NightERP Pro — Tableau de bord
//  Fichier : modules/dashboard.php
// ============================================================
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db   = getDB();
$user = $_SESSION['user'];
$isAdm = ($user['role'] === 'administrateur');

// --- Ventes du jour ---
$ca_jour = $db->query("SELECT COALESCE(SUM(total),0) as t FROM commandes WHERE DATE(created_at)=CURDATE() AND statut='Payée'")->fetch()['t'];
$nb_fact = $db->query("SELECT COUNT(*) as n FROM commandes WHERE DATE(created_at)=CURDATE() AND statut='Payée'")->fetch()['n'];

// --- Tables ---
$tables_total = $db->query("SELECT COUNT(*) as n FROM tables_club")->fetch()['n'];
$tables_occ   = $db->query("SELECT COUNT(*) as n FROM tables_club WHERE statut='Occupée'")->fetch()['n'];

// --- Alertes stock ---
$alertes_stock = $db->query("SELECT p.*, cp.nom AS categorie_nom FROM produits p LEFT JOIN categories_produits cp ON cp.id=p.categorie_id ORDER BY p.nom")->fetchAll();
foreach ($alertes_stock as &$p) { $p['stock'] = getProduitStock($db, $p['id']); }
$alertes_stock = array_filter($alertes_stock, fn($p)=>$p['stock'] <= 0);

// --- Activité récente ---
$activites = $db->query("SELECT * FROM historique ORDER BY id DESC LIMIT 6")->fetchAll();

// --- Top produits ---
$commandes = $db->query("SELECT id FROM commandes WHERE statut='Payée'")->fetchAll();
$prodCounts = [];
foreach ($commandes as $cmd) {
    $items = getCommandeDetails($db, $cmd['id']);
    foreach ($items as $it) {
        $k = $it['produit_nom'] ?? '';
        if (!isset($prodCounts[$k])) $prodCounts[$k] = ['rev'=>0,'qty'=>0];
        $prodCounts[$k]['rev'] += ($it['prix'] ?? 0) * ($it['qte'] ?? 0);
        $prodCounts[$k]['qty'] += ($it['qte'] ?? 0);
    }
}
arsort($prodCounts);
$topProduits = array_slice($prodCounts, 0, 5, true);
$maxRev = $topProduits ? max(array_column($topProduits,'rev')) : 1;

$pageTitle = '<i class="fa-solid fa-gauge-high" style="color:var(--gold)"></i> Tableau de bord';
$root = rootPath(__FILE__);
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Tableau de bord</h1>
  <p>Vue en temps réel de votre établissement</p>
</div>

<!-- KPI -->
<div class="g4 mb20">
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-sack-dollar"></i> CA du jour</div>
    <div class="stat-value gold"><?= fmt($ca_jour) ?></div>
    <div class="stat-delta delta-up"><i class="fa-solid fa-arrow-trend-up"></i> Soirée en cours</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-receipt"></i> Factures</div>
    <div class="stat-value"><?= $nb_fact ?></div>
    <div class="stat-delta" style="color:var(--txt2)">Ce jour</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-chair"></i> Tables occupées</div>
    <div class="stat-value"><?= $tables_occ ?> / <?= $tables_total ?></div>
    <div class="stat-delta" style="color:var(--txt2)">
      <?= $tables_total > 0 ? round($tables_occ/$tables_total*100) : 0 ?>% d'occupation
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-triangle-exclamation"></i> Alertes stock</div>
    <div class="stat-value text-danger"><?= count($alertes_stock) ?></div>
    <div class="stat-delta delta-down"><i class="fa-solid fa-arrow-trend-down"></i> Produits critiques</div>
  </div>
</div>

<div class="g2 mb16">
  <!-- Top produits -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-trophy"></i> Top produits vendus</div>
    </div>
    <?php if ($topProduits): ?>
      <?php $i=1; foreach ($topProduits as $nom => $data): ?>
      <div class="progress-wrap">
        <div class="progress-label">
          <span><?= $i++ ?>. <?= e($nom) ?></span>
          <span><?= fmt($data['rev']) ?></span>
        </div>
        <div class="progress-track">
          <div class="progress-fill" style="width:<?= $maxRev > 0 ? round($data['rev']/$maxRev*100) : 0 ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted" style="text-align:center;padding:20px">Aucune vente enregistrée</p>
    <?php endif; ?>
  </div>

  <!-- Alertes stock -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-triangle-exclamation"></i> Alertes &amp; Notifications</div>
    </div>
    <?php if ($alertes_stock): ?>
      <?php foreach ($alertes_stock as $p): ?>
      <div class="alert alert-<?= $p['stock'] == 0 ? 'danger' : 'warning' ?>">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
          <strong><?= e($p['nom']) ?></strong> —
          <?= $p['stock'] == 0 ? 'RUPTURE DE STOCK' : 'Stock faible : ' . $p['stock'] . ' (min : ' . $p['stock_min'] . ')' ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Tous les stocks sont OK</div>
    <?php endif; ?>
  </div>
</div>

<!-- Activité récente -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-bolt"></i> Activité récente</div>
  </div>
  <?php if ($activites): ?>
    <?php foreach ($activites as $a): ?>
    <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)">
      <div style="width:34px;height:34px;border-radius:8px;background:var(--green2);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">
        <i class="fa-solid fa-bolt"></i>
      </div>
      <div style="flex:1;font-size:12px;color:var(--txt2)">
        <strong style="color:var(--txt)"><?= e($a['action']) ?></strong><br>
        <?= e($a['detail']) ?>
      </div>
      <div style="font-size:10px;color:var(--txt3)"><?= dateStr($a['created_at']) ?></div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="text-muted" style="text-align:center;padding:20px">Aucune activité récente</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
