<?php
// ============================================================
//  NightERP Pro — En-tête & Sidebar
//  Fichier : includes/header.php
// ============================================================
requireLogin();
$user   = $_SESSION['user'];
$isAdm  = ($user['role'] === 'administrateur');
$pageTitleRaw  = $pageTitle ?? 'NightERP';
$pageTitleText = strip_tags((string)$pageTitleRaw);
$pageTitleHtml = $pageTitleRaw;

$canPrincipal = $isAdm || !empty($user['perm_principal']);
$canStock     = $isAdm || !empty($user['perm_stock']);
$canPersonnes = $isAdm || !empty($user['perm_personnes']);
$canFinances  = $isAdm || !empty($user['perm_finances']);

if (!isset($root)) {
    $root = rootUrl();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitleText) ?> — NightERP Pro</title>
<link rel="stylesheet" href="<?= $root ?>assets/css/app.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside id="sidebar">
  <div class="logo">
    <div class="logo-img">
      <img src="<?= $root ?>assets/images/logo.png" alt="NightERP" class="app-logo" onerror="this.onerror=null;this.src='<?= $root ?>assets/images/logo.svg'" />
    </div>
    <div class="logo-txt">
      <h2>NightERP</h2>
      <p>Club &amp; Bar Pro</p>
    </div>
  </div>

  <nav style="overflow:auto;flex:1">

    <?php if ($canPrincipal): ?>
    <div class="nav-group">
      <div class="nav-label">Principal</div>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/dashboard.php">
        <i class="fa-solid fa-gauge-high"></i><span>Tableau de bord</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'pos.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/caisse/pos.php">
        <i class="fa-solid fa-cash-register"></i><span>Point de Vente</span>
        <span class="nav-badge green">POS</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'tables.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/tables/tables.php">
        <i class="fa-solid fa-chair"></i><span>Tables &amp; Zones</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'reservations.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/reservations/reservations.php">
        <i class="fa-solid fa-calendar-check"></i><span>Réservations</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'commandes.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/commandes/commandes.php">
        <i class="fa-solid fa-receipt"></i><span>Commandes</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($canStock): ?>
    <div class="nav-group">
      <div class="nav-label">Stock &amp; Achats</div>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'produits.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/produits/produits.php">
        <i class="fa-solid fa-box-open"></i><span>Produits</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'stock.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/stock/stock.php">
        <i class="fa-solid fa-warehouse"></i><span>Stock</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'achats.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/achats/achats.php">
        <i class="fa-solid fa-truck"></i><span>Achats</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'fournisseurs.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/fournisseurs/fournisseurs.php">
        <i class="fa-solid fa-handshake"></i><span>Fournisseurs</span>
      </a>
    </div>
    <?php endif; ?>

    

    <?php if ($canFinances): ?>
    <div class="nav-group">
      <div class="nav-label">Finances</div>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'caisse.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/caisse/caisse.php">
        <i class="fa-solid fa-sack-dollar"></i><span>Caisse</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'depenses.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/depenses/depenses.php">
        <i class="fa-solid fa-file-invoice-dollar"></i><span>Dépenses</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'comptabilite.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/comptabilite/comptabilite.php">
        <i class="fa-solid fa-book"></i><span>Comptabilité</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'rapports.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/rapports/rapports.php">
        <i class="fa-solid fa-chart-line"></i><span>Rapports</span>
      </a>
    </div>
    <?php endif; ?>

    <?php if ($isAdm): ?>
    <div class="nav-group">
      <div class="nav-label">Administration</div>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'statistiques.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/admin/statistiques.php">
        <i class="fa-solid fa-chart-line"></i><span>Statistiques</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'promotions.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/promotions/promotions.php">
        <i class="fa-solid fa-percent"></i><span>Promotions</span>
      </a>
      <a class="nav-item <?= in_array(basename($_SERVER['PHP_SELF']), ['utilisateurs.php','permissions.php']) ? 'active' : '' ?>"
         href="<?= $root ?>modules/admin/utilisateurs.php">
        <i class="fa-solid fa-users-gear"></i><span>Utilisateurs &amp; Rôles</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'personnel.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/personnel/personnel.php">
        <i class="fa-solid fa-id-badge"></i><span>Personnel</span>
      </a>
      <a class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'historique.php' ? 'active' : '' ?>"
         href="<?= $root ?>modules/admin/historique.php">
        <i class="fa-solid fa-clock-rotate-left"></i><span>Historique</span>
      </a>
    </div>
    <?php endif; ?>

  </nav>

  <div class="user-bar">
    <div class="user-av"><?= strtoupper(substr($user['nom'], 0, 2)) ?></div>
    <div class="user-info">
      <strong><?= e($user['nom']) ?></strong>
      <span><?= e(ucfirst($user['role'])) ?></span>
    </div>
    <a href="<?= $root ?>auth/logout.php" class="logout-btn" title="Déconnexion">
      <i class="fa-solid fa-right-from-bracket"></i>
    </a>
  </div>
</aside>

<!-- ===== TOPBAR ===== -->
<header id="topbar">
  <div class="tb-title"><?= $pageTitleHtml ?></div>
  <span class="tb-badge tb-live"><i class="fa-solid fa-circle"></i> En service</span>
  <div class="tb-time" id="tb-time"></div>
  <button id="theme-toggle" class="btn-icon" title="Basculer thème">
    <i class="fa-solid fa-moon"></i>
  </button>
</header>

<!-- ===== CONTENU PRINCIPAL ===== -->
<main id="main">
<?= flash() ?>
