<?php
// ============================================================
//  NightERP Pro — Stock & Inventaire
//  Fichier : modules/stock/stock.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('stock');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'ajuster') {
        $pid    = intval($_POST['produit_id'] ?? 0);
        $type   = $_POST['type'] ?? 'Entrée';
        $qty    = intval($_POST['quantite'] ?? 0);
        $motif  = trim($_POST['motif'] ?? 'Manuel');

        if ($pid && $qty > 0) {
            $p = $db->prepare("SELECT * FROM produits WHERE id=?"); $p->execute([$pid]); $prod = $p->fetch();

            if ($prod) {
                $delta = $type === 'Sortie' ? -$qty : $qty;
                $st = $db->prepare("SELECT id, stock_actuel FROM stock WHERE produit_id=? LIMIT 1");
                $st->execute([$pid]);
                $stockRow = $st->fetch();
                if ($stockRow) {
                    $newStock = max(0, $stockRow['stock_actuel'] + $delta);
                    $db->prepare("UPDATE stock SET stock_actuel=? WHERE id=?")->execute([$newStock, $stockRow['id']]);
                } else {
                    $newStock = max(0, $delta);
                    $db->prepare("INSERT INTO stock (libelle, produit_id, stock_actuel, seuil_alert) VALUES (?,?,?,?)")->execute([$prod['nom'], $pid, $newStock, 5]);
                }
                $db->prepare("INSERT INTO stock_mouvements (produit_id,type,quantite,motif,statut) VALUES (?,?,?,?,?)")->execute([$pid,$type,$qty,$motif,'actif']);
                logAction('Stock ajusté', $prod['nom'].' : '.$type.' de '.$qty.' ('.$motif.')', 'Stock');
                redirect($_SERVER['PHP_SELF'], 'Stock mis à jour : '.$prod['nom'].' → '.$newStock);
            }
        }
        redirect($_SERVER['PHP_SELF'], 'Données invalides.', 'error');
    }
}

$produits = $db->query("SELECT p.*, cp.nom AS categorie_nom FROM produits p LEFT JOIN categories_produits cp ON cp.id=p.categorie_id ORDER BY p.nom")->fetchAll();
foreach ($produits as &$p) { $p['stock'] = getProduitStock($db, $p['id']); }
$alertes = array_filter($produits, fn($p)=>$p['stock'] <= 0);
$top8    = array_slice($produits, 0, 8);

$pageTitle = '<i class="fa-solid fa-warehouse" style="color:var(--gold)"></i> Stock & Inventaire';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-warehouse text-gold"></i> Stock &amp; Inventaire</h1><p>Gestion des niveaux de stock</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAjustStock')"><i class="fa-solid fa-plus"></i> Entrée stock</button>
</div>

<div class="g2 mb16">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fa-solid fa-triangle-exclamation"></i> Produits critiques</div></div>
    <?php if($alertes): foreach($alertes as $p): ?>
      <div class="alert alert-<?= $p['stock']==0?'danger':'warning' ?>">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><strong><?= e($p['nom']) ?></strong> — <?= $p['stock']==0?'RUPTURE STOCK':'Stock faible: '.$p['stock'] ?></div>
      </div>
    <?php endforeach; else: ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> Tous les stocks sont OK</div>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fa-solid fa-chart-bar"></i> Niveaux de stock</div></div>
    <?php foreach($top8 as $p):
      $pct = $p['stock'] > 0 ? min(100,round($p['stock']/10*100)) : 0;
      $cls = $p['stock']<=0 ? 'red' : '';
    ?>
    <div class="progress-wrap">
      <div class="progress-label"><span><?= e($p['nom']) ?></span><span><?= $p['stock'] ?></span></div>
      <div class="progress-track"><div class="progress-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-table-list"></i> Inventaire complet</div></div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Produit</th><th>Catégorie</th><th>Stock actuel</th><th>Stock min.</th><th>Niveau</th><th>Valeur stock</th></tr></thead>
      <tbody>
        <?php foreach($produits as $p):
          $val = $p['stock']*$p['prix_achat'];
          $pct = $p['stock'] > 0 ? min(100,round($p['stock']/10*100)) : 0;
          $color = $p['stock']==0?'var(--red)':($p['stock']<=0?'var(--orange)':'var(--green)');
        ?>
        <tr>
          <td><strong><?= e($p['nom']) ?></strong></td>
          <td><?= badge($p['categorie_nom'] ?? '—','blue') ?></td>
          <td style="color:<?= $color ?>;font-weight:600"><?= $p['stock'] ?></td>
          <td class="td-muted">—</td>
          <td><div style="width:80px;height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden;display:inline-block">
            <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>"></div></div></td>
          <td class="td-gold"><?= fmt($val) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAjustStock">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3><i class="fa-solid fa-plus-minus text-gold"></i> Ajustement de stock</h3><button class="modal-close" onclick="closeModal('modalAjustStock')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajuster">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Produit</label>
          <select class="form-control" name="produit_id" required>
            <?php foreach($produits as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nom']) ?> (stock: <?= $p['stock'] ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select class="form-control" name="type">
              <option value="entrée">Entrée</option>
              <option value="sortie">Sortie</option>
              <option value="correction">Correction</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Quantité</label><input class="form-control" type="number" name="quantite" min="1" value="1" required></div>
        </div>
        <div class="form-group"><label class="form-label">Motif</label><input class="form-control" name="motif" placeholder="Livraison / Perte / Inventaire..."></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAjustStock')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Valider</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
