<?php
// ============================================================
//  NightERP Pro — Promotions
//  Fichier : modules/promotions/promotions.php
//  Accès   : Administrateur uniquement
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireLogin();
if (!isAdmin()) redirect('../dashboard.php', 'Accès refusé.', 'error');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'ajouter') {
        $nom = trim($_POST['libelle'] ?? '');
        if (!$nom) redirect($_SERVER['PHP_SELF'], 'Le libellé est requis.', 'error');
        $db->prepare("INSERT INTO promotions (libelle,type_valeur,type_promo,valeur,produit_id,date_debut,date_fin,actif,user_id,statut) VALUES (?,?,?,?,?,?,?,?,?,?)")
           ->execute([$nom, $_POST['type_valeur']??'pourcentage', $_POST['type_promo']??'commande', floatval($_POST['valeur']??0), !empty($_POST['produit_id']) ? intval($_POST['produit_id']) : null, $_POST['date_debut']??null, $_POST['date_fin']??null, intval($_POST['actif'] ?? 1), $_SESSION['user_id'], 'actif']);
        logAction('Promotion créée', $nom, 'Promotions');
        redirect($_SERVER['PHP_SELF'], "Promotion « $nom » créée.");
    }
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $actif = intval($_POST['actif'] ?? 0);
        $db->prepare("UPDATE promotions SET actif=? WHERE id=?")->execute([$actif?0:1,$id]);
        redirect($_SERVER['PHP_SELF'], 'Promotion mise à jour.', 'info');
    }
}

$promos = $db->query("SELECT p.*, pr.nom AS produit_nom FROM promotions p LEFT JOIN produits pr ON pr.id=p.produit_id ORDER BY p.id")->fetchAll();
$produits = $db->query("SELECT id,nom FROM produits ORDER BY nom")->fetchAll();
$pageTitle = '<i class="fa-solid fa-percent" style="color:var(--gold)"></i> Promotions';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-percent text-gold"></i> Promotions</h1><p>Happy hours &amp; offres spéciales</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddPromo')"><i class="fa-solid fa-plus"></i> Nouvelle promo</button>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Libellé</th><th>Type</th><th>Valeur</th><th>Produit</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if($promos): foreach($promos as $p): ?>
        <tr>
          <td><strong><?= e($p['libelle']) ?></strong></td>
          <td><?= e($p['type_promo']) ?> / <?= e($p['type_valeur']) ?></td>
          <td class="td-gold fw700"><?= e($p['valeur']) ?><?= $p['type_valeur']==='pourcentage' ? '%' : ' F' ?></td>
          <td class="td-muted"><?= e($p['produit_nom'] ?? '—') ?></td>
          <td><?= badge($p['actif']?'Active':'Inactive', $p['actif']?'green':'red') ?></td>
          <td>
            <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="actif" value="<?= $p['actif'] ?>">
              <button class="btn-icon btn"><i class="fa-solid fa-power-off"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--txt2)">Aucune promotion</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAddPromo">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3><i class="fa-solid fa-percent text-gold"></i> Nouvelle promotion</h3><button class="modal-close" onclick="closeModal('modalAddPromo')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Libellé</label><input class="form-control" name="libelle" placeholder="Ex: Happy Hour" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Type de valeur</label><select class="form-control" name="type_valeur"><option value="pourcentage">Pourcentage</option><option value="montant">Montant</option></select></div>
          <div class="form-group"><label class="form-label">Type de promo</label><select class="form-control" name="type_promo"><option value="commande">Commande</option><option value="produit">Produit</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Valeur</label><input class="form-control" type="number" name="valeur" min="0" value="20"></div>
          <div class="form-group"><label class="form-label">Produit (optionnel)</label><select class="form-control" name="produit_id"><option value="">—</option><?php foreach($produits as $prod): ?><option value="<?= $prod['id'] ?>"><?= e($prod['nom']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Date début</label><input class="form-control" type="date" name="date_debut" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label class="form-label">Date fin</label><input class="form-control" type="date" name="date_fin" value="<?= date('Y-m-d', strtotime('+30 days')) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Actif</label><select class="form-control" name="actif"><option value="1">Oui</option><option value="0">Non</option></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddPromo')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Créer</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
