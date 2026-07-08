<?php
// ============================================================
//  NightERP Pro — Point de Vente (POS)
//  Fichier : modules/caisse/pos.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('principal');

$db   = getDB();
$root = rootPath(__FILE__);

// --- ENCAISSER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $cartJson = $_POST['cart']     ?? '[]';
    $payMode  = $_POST['pay_mode'] ?? 'Espèces';
    $discount = floatval($_POST['discount'] ?? 0);
    $tableId  = intval($_POST['table_id']   ?? 0) ?: null;

    $cartItems = json_decode($cartJson, true) ?? [];
    if ($cartItems) {
        $sousTotal = 0;
        foreach ($cartItems as $it) {
            $pid = intval($it['id'] ?? 0);
            $qty = intval($it['qty'] ?? 1);
            $prix = floatval($it['prix'] ?? 0);
            $prod = $db->prepare("SELECT * FROM produits WHERE id=?");
            $prod->execute([$pid]);
            $prodRow = $prod->fetch();
            if ($prodRow) {
                $sousTotal += $prix * $qty;
            }
        }
        $total = $sousTotal ;
        // $total = $sousTotal * (1 - $discount / 100);
        $numero = 'CMD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
        $payModeDb = in_array($payMode, ['Espèces','Mobile Money','Carte bancaire','Mixte'], true) ? $payMode : 'Espèces';
        $statutDb = 'Payée';
        $st = $db->prepare("INSERT INTO commandes (table_id,client_id,numero,promotion_id,total,mode_paiement,statut,utilisateur_id) VALUES (?,?,?,?,?,?,?,?)");
        $st->execute([$tableId, null, $numero, null, $total, $payModeDb, $statutDb, $_SESSION['user_id']]);
        $orderId = $db->lastInsertId();
        foreach ($cartItems as $it) {
            $pid = intval($it['id'] ?? 0); 
            $qty = intval($it['qty'] ?? 1);
            $prix = floatval($it['prix'] ?? 0);
            $db->prepare("INSERT INTO detail_commande (commande_id,produit_id,prix,qte,remise,sous_total) VALUES (?,?,?,?,?,?)")
               ->execute([$orderId, $pid, $prix, $qty, 0, $prix * $qty]);
        }
        if ($tableId) {
          $db->prepare("UPDATE tables_club SET statut='Occupée' WHERE id=?")->execute([$tableId]);
        }
        logAction('Vente encaissée', count($cartItems) . ' article(s) — ' . fmt($total) . ' — ' . $payMode, 'POS');
        redirect('receipt.php?order=' . (int)$orderId);
    }
}

// --- Données POS ---
$produits     = $db->query("SELECT p.*, cp.nom AS categorie_nom FROM produits p LEFT JOIN categories_produits cp ON cp.id=p.categorie_id ORDER BY cp.nom, p.nom")->fetchAll();
$categories   = array_unique(array_column($produits, 'categorie_nom'));
$tables       = $db->query("SELECT * FROM tables_club ORDER BY zone,nom")->fetchAll();

$pageTitle = '<i class="fa-solid fa-cash-register" style="color:var(--gold)"></i> Point de Vente';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <h1><i class="fa-solid fa-cash-register text-gold"></i> Point de Vente</h1>
  <p>Caisse &amp; encaissement rapide</p>
</div>

<div class="pos-grid">
  <!-- GAUCHE : catalogue -->
  <div class="pos-left">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap">
      <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:var(--r2);padding:8px 12px;flex:1;min-width:180px">
        <i class="fa-solid fa-magnifying-glass" style="color:var(--txt3)"></i>
        <input type="text" placeholder="Rechercher un produit..." oninput="filterPOS(this.value)"
               style="background:none;border:none;color:var(--txt);font-size:13px;outline:none;width:100%">
      </div>
      <select id="pos-table" style="background:var(--card2);border:1px solid var(--border);border-radius:var(--r2);padding:9px 12px;font-size:13px;color:var(--txt);outline:none">
        <option value="">-- Sélectionner une table --</option>
        <?php foreach($tables as $t): ?>
        <option value="<?= $t['id'] ?>"><?= e($t['zone']) ?> — <?= e($t['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Catégories -->
    <div class="pos-cats">
      <button class="cat-btn active" onclick="filterPOSCat('all',this)">
        <i class="fa-solid fa-border-all"></i> Tous
      </button>
      <?php foreach($categories as $cat): ?>
      <button class="cat-btn" onclick="filterPOSCat(<?= json_encode($cat) ?>,this)"><?= e($cat) ?></button>
      <?php endforeach; ?>
    </div>

    <!-- Produits -->
    <div class="products-grid">
      <?php foreach($produits as $p): ?>
      <?php
        $imgUrl = $p['image'] && file_exists(UPLOAD_PATH.$p['image'])
                  ? $root . UPLOAD_URL . $p['image']
                  : null;
        $stock = getProduitStock($db, $p['id']);
      ?>
       <div class="prod-card" data-cat="<?= e($p['categorie_nom'] ?? 'Autre') ?>"
         data-id="<?= $p['id'] ?>" data-nom="<?= e($p['nom']) ?>" data-prix="<?= $p['prix_vente'] ?>" data-img="<?= e($imgUrl ?: '') ?>">
        <?php if ($imgUrl): ?>
          <img src="<?= e($imgUrl) ?>" class="prod-img" style="margin:0 auto 8px;display:block" alt="<?= e($p['nom']) ?>">
        <?php else: ?>
          <div style="font-size:36px;margin-bottom:8px">📦</div>
        <?php endif; ?>
        <div class="prod-name"><?= e($p['nom']) ?></div>
        <div class="prod-price"><?= fmt($p['prix_vente']) ?></div>
        <div class="prod-stock">Stock : <?= $stock ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- DROITE : Panier -->
  <div class="pos-right">
    <div class="card-title" style="margin-bottom:14px">
      <i class="fa-solid fa-cart-shopping text-gold"></i> Panier
    </div>
    <div id="cart-items" style="flex:1;overflow:auto;min-height:200px">
      <div style="text-align:center;padding:30px;color:var(--txt2)">
        <i class="fa-solid fa-cart-plus" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>Panier vide
      </div>
    </div>
    <div class="cart-summary">
      <div class="cart-row"><span>Sous-total</span><span id="cart-subtotal">0 F</span></div>
      <div class="cart-row">
        <span>Remise</span>
        <span style="display:flex;align-items:center;gap:6px">
          <input type="number" value="0" min="0" max="100" id="cart-discount" oninput="POS.updateTotal()"
                 style="width:50px;background:transparent;border:1px solid var(--border);border-radius:4px;padding:2px 6px;color:var(--txt);font-size:12px">%
        </span>
      </div>
      <div class="cart-row total"><span><i class="fa-solid fa-sigma"></i> Total</span><span class="text-gold" id="cart-total">0 F</span></div>

      <div class="pay-modes">
        <button class="pay-btn active" id="pay-espèces"       onclick="POS.setPayMode('Espèces')"><i class="fa-solid fa-money-bill-wave"></i>Espèces</button>
        <button class="pay-btn"        id="pay-mobile_money"  onclick="POS.setPayMode('Mobile Money')"><i class="fa-brands fa-google-pay"></i>Mobile Money</button>
        <button class="pay-btn"        id="pay-carte_bancaire"onclick="POS.setPayMode('Carte bancaire')"><i class="fa-solid fa-credit-card"></i>Carte</button>
        <button class="pay-btn"        id="pay-mixte"         onclick="POS.setPayMode('Mixte')"><i class="fa-solid fa-shuffle"></i>Mixte</button>
      </div>

      <button class="btn btn-primary" style="width:100%" onclick="POS.submit()">
        <i class="fa-solid fa-check"></i> Encaisser
      </button>
    </div>
  </div>
</div>

<!-- Formulaire caché pour soumettre la vente -->
<form id="pos-form" method="POST" style="display:none">
  <?= csrfField() ?>
  <input type="hidden" id="hidden-cart"  name="cart">
  <input type="hidden" id="hidden-pay"   name="pay_mode">
  <input type="hidden" id="hidden-disc"  name="discount">
  <input type="hidden" id="hidden-table" name="table_id">
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
