<?php
// ============================================================
//  NightERP Pro — Rapports & Statistiques
//  Fichier : modules/rapports/rapports.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('finances');
$db = getDB();

$ventes = $db->query("SELECT * FROM commandes WHERE statut='Payée'")->fetchAll();
$ca     = array_sum(array_column($ventes,'total'));
$ticket = count($ventes) > 0 ? $ca / count($ventes) : 0;

$prodCnt = [];
foreach ($ventes as $v) {
    $items = getCommandeDetails($db, $v['id']);
    foreach ($items as $it) {
        $k = $it['produit_nom'] ?? '';
        if (!isset($prodCnt[$k])) $prodCnt[$k] = ['qty'=>0,'rev'=>0];
        $prodCnt[$k]['qty'] += $it['qte'] ?? 0;
        $prodCnt[$k]['rev'] += ($it['prix'] ?? 0) * ($it['qte'] ?? 0);
    }
}
arsort($prodCnt);
$top10 = array_slice($prodCnt, 0, 10, true);
$maxRev = $top10 ? max(array_column($top10,'rev')) : 1;

// Catégories (pour le graphique)
$catData = [];
foreach ($ventes as $v) {
    $items = getCommandeDetails($db, $v['id']);
    foreach ($items as $it) {
        $cat = $it['produit_nom'] ?? '';
        if ($cat) {
            $catData[$cat] = ($catData[$cat] ?? 0) + ($it['prix'] ?? 0) * ($it['qte'] ?? 0);
        }
    }
}

$staff  = $db->query("SELECT * FROM personnel ORDER BY ca_genere DESC")->fetchAll();
$maxCA  = $staff ? max(array_column($staff,'ca_genere')) : 1;
$maxCA  = max($maxCA, 1);

$clientsVip = 0; // module clients VIP retiré
$pageTitle = '<i class="fa-solid fa-chart-line" style="color:var(--gold)"></i> Rapports & Statistiques';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><i class="fa-solid fa-chart-line text-gold"></i> Rapports &amp; Statistiques</h1><p>Analyse de performance</p></div>

<div class="kpi-row mb20">
  <div class="kpi-chip"><div class="kpi-v"><?= fmt($ca) ?></div><div class="kpi-l">CA total</div></div>
  <div class="kpi-chip"><div class="kpi-v"><?= fmt($ticket) ?></div><div class="kpi-l">Ticket moyen</div></div>
  <div class="kpi-chip"><div class="kpi-v"><?= count($ventes) ?></div><div class="kpi-l">Nb ventes</div></div>
  <div class="kpi-chip"><div class="kpi-v"><?= count($staff) ?></div><div class="kpi-l">Employés</div></div>
</div>

<div class="g2 mb16">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fa-solid fa-chart-pie"></i> Ventes par catégorie</div></div>
    <?php if($catData): $totCat = array_sum($catData); foreach($catData as $cat => $val): ?>
    <div class="progress-wrap">
      <div class="progress-label"><span><?= e($cat) ?></span><span><?= fmt($val) ?></span></div>
      <div class="progress-track"><div class="progress-fill" style="width:<?= $totCat>0?round($val/$totCat*100):0 ?>%"></div></div>
    </div>
    <?php endforeach; else: ?>
    <p class="text-muted" style="text-align:center;padding:20px">Aucune donnée</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fa-solid fa-ranking-star"></i> Top 10 produits</div></div>
    <?php if($top10): $i=1; foreach($top10 as $nom => $d): ?>
    <div class="progress-wrap">
      <div class="progress-label"><span><?= $i++ ?>. <?= e($nom) ?></span><span><?= fmt($d['rev']) ?></span></div>
      <div class="progress-track"><div class="progress-fill" style="width:<?= $maxRev>0?round($d['rev']/$maxRev*100):0 ?>%"></div></div>
    </div>
    <?php endforeach; else: ?>
    <p class="text-muted" style="text-align:center;padding:20px">Aucune donnée</p>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-users"></i> Performance du personnel</div></div>
  <div class="tbl-wrap"><table>
    <thead><tr><th>Employé</th><th>Nb ventes</th><th>CA généré</th><th>Ticket moyen</th><th>Performance</th></tr></thead>
    <tbody>
      <?php if($staff): foreach($staff as $s): ?>
      <tr>
        <td><strong><?= e($s['nom']) ?></strong><div class="td-muted"><?= e($s['role']) ?></div></td>
        <td><?= $s['nb_ventes'] ?></td>
        <td class="td-gold"><?= fmt($s['ca_genere']) ?></td>
        <td class="td-muted"><?= $s['nb_ventes']>0 ? fmt($s['ca_genere']/$s['nb_ventes']) : '—' ?></td>
        <td><div class="progress-track" style="width:100px;display:inline-block"><div class="progress-fill" style="width:<?= round($s['ca_genere']/$maxCA*100) ?>%"></div></div></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--txt2)">Aucune donnée</td></tr>
      <?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
