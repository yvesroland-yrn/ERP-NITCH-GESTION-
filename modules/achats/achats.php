<?php
// ============================================================
//  NightERP Pro — Achats Fournisseurs
//  Fichier : modules/achats/achats.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('stock');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'ajouter') {
        $fid = $_POST['fournisseur_id'] ?: null;
        $pid = intval($_POST['produit_id'] ?? 0);
        $qty = intval($_POST['quantite'] ?? 1);
        $mt  = floatval($_POST['montant'] ?? 0);
        $dt  = $_POST['date_reception'] ?? date('Y-m-d');
        if ($pid && $qty > 0) {
            $db->prepare("INSERT INTO achats (fournisseur_id,produit_id,quantite,montant,date_reception) VALUES (?,?,?,?,?)")->execute([$fid,$pid,$qty,$mt,$dt]);
            // $db->prepare("UPDATE produits SET stock=stock+? WHERE id=?")->execute([$qty,$pid]);
            $p = $db->prepare("SELECT nom FROM produits WHERE id=?"); $p->execute([$pid]); $nom = $p->fetchColumn();
            logAction('Achat enregistré', "$nom +$qty unités — ".fmt($mt), 'Achats');
            redirect($_SERVER['PHP_SELF'], "Stock mis à jour : +$qty unités");
        }
        redirect($_SERVER['PHP_SELF'], 'Données invalides.', 'error');
    }
}

$achats = $db->query("SELECT a.*,f.nom as f_nom,p.nom as p_nom FROM achats a LEFT JOIN fournisseurs f ON a.fournisseur_id=f.id LEFT JOIN produits p ON a.produit_id=p.id ORDER BY a.id DESC")->fetchAll();
$fournisseurs = $db->query("SELECT id,nom FROM fournisseurs ORDER BY nom")->fetchAll();
$produits     = $db->query("SELECT id,nom FROM produits ORDER BY nom")->fetchAll();

$pageTitle = '<i class="fa-solid fa-truck" style="color:var(--gold)"></i> Achats Fournisseurs';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-truck text-gold"></i> Achats Fournisseurs</h1><p>Bons de réception et entrées de marchandises</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddAchat')"><i class="fa-solid fa-plus"></i> Nouvel achat</button>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Date</th><th>Fournisseur</th><th>Produit</th><th>Qté</th><th>Montant</th><th>Statut</th></tr></thead>
      <tbody>
        <?php if($achats): foreach($achats as $a): ?>
        <tr>
          <td class="td-muted">#<?= str_pad($a['id'],4,'0',STR_PAD_LEFT) ?></td>
          <td class="td-muted"><?= $a['date_reception'] ? date('d/m/Y',strtotime($a['date_reception'])) : dateStr($a['created_at']) ?></td>
          <td><?= e($a['f_nom'] ?? '—') ?></td>
          <td><?= e($a['p_nom'] ?? '—') ?></td>
          <td class="td-muted">×<?= $a['quantite'] ?></td>
          <td class="td-gold"><?= fmt($a['montant']) ?></td>
          <td><?= badge($a['statut'],'green') ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--txt2)">Aucun achat enregistré</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAddAchat">
  <div class="modal">
    <div class="modal-header"><h3><i class="fa-solid fa-truck text-gold"></i> Enregistrer un achat</h3><button class="modal-close" onclick="closeModal('modalAddAchat')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Fournisseur</label><select class="form-control" name="fournisseur_id">
            <option value="">Aucun</option><?php foreach($fournisseurs as $f): ?><option value="<?= $f['id'] ?>"><?= e($f['nom']) ?></option><?php endforeach; ?>
          </select></div>
          <div class="form-group"><label class="form-label">Montant total (F)</label><input class="form-control" type="number" name="montant" required></div>
        </div>
        <div class="form-group"><label class="form-label">Produit</label><select class="form-control" name="produit_id" required>
          <?php foreach($produits as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nom']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Quantité reçue</label><input class="form-control" type="number" name="quantite" min="1" value="1" required></div>
          <div class="form-group"><label class="form-label">Date réception</label><input class="form-control" type="date" name="date_reception" value="<?= date('Y-m-d') ?>"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddAchat')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
