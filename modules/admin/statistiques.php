<?php
// ============================================================
//  NightERP Pro — Statistiques & Analyse
//  Fichier : modules/admin/statistiques.php
//  Accès   : Administrateur uniquement
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireLogin();
if (!isAdmin()) redirect('../../modules/dashboard.php', 'Accès refusé.', 'error');

$db   = getDB();
$root = rootPath(__FILE__);

// --- STAT UTILISATEURS ---
$statUsers = $db->query("SELECT role, COUNT(*) as n FROM utilisateurs WHERE actif=1 GROUP BY role")->fetchAll();
$usersData = array_column($statUsers, 'n', 'role');

// --- STAT VENTES (7 derniers jours) ---
$ventes7j = $db->query("
    SELECT DATE(created_at) as jour, COUNT(*) as nb, COALESCE(SUM(total),0) as ca 
    FROM commandes 
    WHERE statut='Payée' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY jour
")->fetchAll();

$joursData = [];
$caData = [];
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $found = array_filter($ventes7j, fn($v)=>$v['jour']===$d);
    $found = array_values($found);
    $joursData[] = date('d/m', strtotime($d));
    $caData[] = $found ? (float)$found[0]['ca'] : 0;
}

// --- STAT COMMANDES PAR STATUT ---
$statCmd = $db->query("SELECT statut, COUNT(*) as n FROM commandes GROUP BY statut")->fetchAll();
$cmdStatut = array_column($statCmd, 'n', 'statut');

// --- STAT PRODUITS TOP 10 ---
$topProds = $db->query("
    SELECT p.nom, COALESCE(SUM(dc.qte),0) as qte_vendue, COALESCE(SUM(dc.qte*dc.prix),0) as revenu
    FROM produits p
    LEFT JOIN detail_commande dc ON dc.produit_id=p.id
    LEFT JOIN commandes c ON c.id=dc.commande_id AND c.statut='Payée'
    GROUP BY p.id, p.nom
    ORDER BY revenu DESC
    LIMIT 10
")->fetchAll();

// --- STAT CATEGORIES ---
$statCat = $db->query("
    SELECT cp.nom, COUNT(p.id) as nb_prod, COALESCE(SUM(dc.qte*dc.prix),0) as revenu
    FROM categories_produits cp
    LEFT JOIN produits p ON p.categorie_id=cp.id
    LEFT JOIN detail_commande dc ON dc.produit_id=p.id
    LEFT JOIN commandes c ON c.id=dc.commande_id AND c.statut='Payée'
    GROUP BY cp.id, cp.nom
    ORDER BY revenu DESC
")->fetchAll();

// --- STAT MODES DE PAIEMENT ---
$statPay = $db->query("
    SELECT mode_paiement, COUNT(*) as n, COALESCE(SUM(total),0) as ca
    FROM commandes 
    WHERE statut='Payée'
    GROUP BY mode_paiement
")->fetchAll();

// --- TOTAUX ---
$totalCA = $db->query("SELECT COALESCE(SUM(total),0) as t FROM commandes WHERE statut='Payée'")->fetch()['t'];
$totalCmd = $db->query("SELECT COUNT(*) as n FROM commandes WHERE statut='Payée'")->fetch()['n'];
$totalUsers = $db->query("SELECT COUNT(*) as n FROM utilisateurs WHERE actif=1")->fetch()['n'];
$totalProds = $db->query("SELECT COUNT(*) as n FROM produits")->fetch()['n'];

$pageTitle = '<i class="fa-solid fa-chart-line" style="color:var(--gold)"></i> Statistiques & Analyse';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa-solid fa-chart-line text-gold"></i> Statistiques & Analyse</h1>
  <p>Bilan complet de votre activité</p>
</div>

<!-- KPI CARDS -->
<div class="g4 mb20">
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-sack-dollar"></i> Chiffre d'affaires total</div>
    <div class="stat-value gold"><?= fmt($totalCA) ?></div>
    <div class="stat-delta delta-up"><i class="fa-solid fa-arrow-trend-up"></i> Tous les temps</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-receipt"></i> Commandes payées</div>
    <div class="stat-value"><?= $totalCmd ?></div>
    <div class="stat-delta" style="color:var(--txt2)">Transactions réussies</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-users"></i> Utilisateurs actifs</div>
    <div class="stat-value"><?= $totalUsers ?></div>
    <div class="stat-delta" style="color:var(--txt2)">Compte actif</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="fa-solid fa-box-open"></i> Total produits</div>
    <div class="stat-value"><?= $totalProds ?></div>
    <div class="stat-delta" style="color:var(--txt2)">Catalogues</div>
  </div>
</div>

<div class="g2 mb16">
  <!-- Graphique CA 7 jours -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-chart-line"></i> Chiffre d'affaires (7 jours)</div>
    </div>
    <div style="position:relative;height:250px;margin-top:12px">
      <canvas id="chartCA"></canvas>
    </div>
  </div>

  <!-- Graphique Statut Commandes -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-pie-chart"></i> Statut des commandes</div>
    </div>
    <div style="position:relative;height:250px;margin-top:12px">
      <canvas id="chartStatut"></canvas>
    </div>
  </div>
</div>

<div class="g2 mb16">
  <!-- Graphique Utilisateurs par rôle -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-users-gear"></i> Utilisateurs par rôle</div>
    </div>
    <div style="position:relative;height:250px;margin-top:12px">
      <canvas id="chartUsers"></canvas>
    </div>
  </div>

  <!-- Graphique Top Catégories -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-tags"></i> Revenu par catégorie</div>
    </div>
    <div style="position:relative;height:250px;margin-top:12px">
      <canvas id="chartCategories"></canvas>
    </div>
  </div>
</div>

<!-- TOP PRODUITS -->
<div class="card mb16">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-trophy"></i> Top 10 produits vendus</div>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Rang</th>
          <th>Produit</th>
          <th>Quantité vendue</th>
          <th>Revenu</th>
          <th>Pourcentage</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $maxRev = $topProds ? max(array_column($topProds, 'revenu')) : 1;
        $i = 1;
        foreach ($topProds as $prod): 
          $pct = $maxRev > 0 ? round($prod['revenu'] / $maxRev * 100) : 0;
        ?>
        <tr>
          <td><strong style="color:var(--gold)">#<?= $i++ ?></strong></td>
          <td><?= e($prod['nom']) ?></td>
          <td class="text-center"><span class="pill pill-blue"><?= (int)$prod['qte_vendue'] ?></span></td>
          <td class="text-gold fw700"><?= fmt($prod['revenu']) ?></td>
          <td>
            <div class="progress-track" style="height:20px">
              <div class="progress-fill" style="width:<?= $pct ?>%"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- MODES DE PAIEMENT -->
<div class="card mb16">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-credit-card"></i> Paiements par mode</div>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Mode de paiement</th>
          <th>Nombre de transactions</th>
          <th>Montant total</th>
          <th>Pourcentage du CA</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($statPay as $pay): 
          $pctCA = $totalCA > 0 ? round($pay['ca'] / $totalCA * 100) : 0;
        ?>
        <tr>
          <td><strong><?= ucfirst(e($pay['mode_paiement'] ?? 'Non défini')) ?></strong></td>
          <td class="text-center"><span class="pill pill-green"><?= $pay['n'] ?></span></td>
          <td class="text-gold fw700"><?= fmt($pay['ca']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-track" style="flex:1;height:20px">
                <div class="progress-fill gold" style="width:<?= $pctCA ?>%"></div>
              </div>
              <span><?= $pctCA ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- CATEGORIES -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-sitemap"></i> Analyse par catégorie</div>
  </div>
  <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Catégorie</th>
          <th>Nombre de produits</th>
          <th>Revenu généré</th>
          <th>Contribution</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $maxCatRev = $statCat ? max(array_column($statCat, 'revenu')) : 1;
        foreach ($statCat as $cat): 
          $contrib = $totalCA > 0 ? round($cat['revenu'] / $totalCA * 100) : 0;
        ?>
        <tr>
          <td><strong><?= e($cat['nom']) ?></strong></td>
          <td class="text-center"><span class="pill pill-purple"><?= $cat['nb_prod'] ?></span></td>
          <td class="text-gold fw700"><?= fmt($cat['revenu']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-track" style="flex:1;height:20px">
                <div class="progress-fill" style="background:var(--purple);width:<?= $contrib ?>%"></div>
              </div>
              <span><?= $contrib ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartColors = {
  gold: '#C9A84C',
  green: '#2ECC71',
  red: '#E74C3C',
  blue: '#3498DB',
  purple: '#9B59B6',
  orange: '#E67E22',
  dark: '#0A0A0F',
  card: '#12121A'
};

// Graphique CA 7 jours
new Chart(document.getElementById('chartCA'), {
  type: 'line',
  data: {
    labels: <?= json_encode($joursData) ?>,
    datasets: [{
      label: 'Chiffre d\'affaires',
      data: <?= json_encode($caData) ?>,
      borderColor: chartColors.gold,
      backgroundColor: 'rgba(201, 168, 76, 0.1)',
      tension: 0.4,
      fill: true,
      borderWidth: 2,
      pointRadius: 4,
      pointBackgroundColor: chartColors.gold,
      pointBorderColor: chartColors.card,
      pointBorderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9090A0' } },
      y: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9090A0' } }
    }
  }
});

// Graphique Statut Commandes
new Chart(document.getElementById('chartStatut'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_keys($cmdStatut)) ?>,
    datasets: [{
      data: <?= json_encode(array_values($cmdStatut)) ?>,
      backgroundColor: [chartColors.gold, chartColors.green, chartColors.red, chartColors.blue, chartColors.purple],
      borderColor: chartColors.card,
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { 
      legend: { labels: { color: '#F0F0F5' } }
    }
  }
});

// Graphique Utilisateurs par rôle
new Chart(document.getElementById('chartUsers'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_keys($usersData)) ?>,
    datasets: [{
      label: 'Nombre',
      data: <?= json_encode(array_values($usersData)) ?>,
      backgroundColor: [chartColors.red, chartColors.gold, chartColors.green, chartColors.blue, chartColors.purple, chartColors.orange].slice(0, count($usersData)),
      borderColor: chartColors.card,
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9090A0' } },
      y: { grid: { display: false }, ticks: { color: '#9090A0' } }
    }
  }
});

// Graphique Top Catégories
new Chart(document.getElementById('chartCategories'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($statCat, 'nom')) ?>,
    datasets: [{
      label: 'Revenu (F)',
      data: <?= json_encode(array_column($statCat, 'revenu')) ?>,
      backgroundColor: chartColors.purple,
      borderColor: chartColors.card,
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,.05)' }, ticks: { color: '#9090A0' } },
      y: { grid: { display: false }, ticks: { color: '#9090A0' } }
    }
  }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
