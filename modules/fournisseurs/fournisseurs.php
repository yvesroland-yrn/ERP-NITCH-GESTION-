<?php
// ============================================================
//  NightERP Pro — Fournisseurs
//  Fichier : modules/fournisseurs/fournisseurs.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('stock');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        if (!$nom) redirect($_SERVER['PHP_SELF'], 'Le nom est requis.', 'error');
        $stmt = $db->prepare("INSERT INTO fournisseurs (nom,contact,telephone,solde,statut) VALUES (?,?,?,?,?)");
        $stmt->execute([$nom, trim($_POST['contact']??''), trim($_POST['telephone']??''), 0.00, 1]);
        $fournisseurId = (int) $db->lastInsertId();

        $produits = $_POST['produit_ids'] ?? [];
        if (is_string($produits)) {
            $produits = [$produits];
        }
        foreach ((array) $produits as $produitId) {
            $pid = (int) $produitId;
            if ($pid > 0) {
                $db->prepare("INSERT INTO fournisseur_produit (fournisseur_id, produit_id, montant, statut) VALUES (?,?,?,?)")
                   ->execute([$fournisseurId, $pid, 0.00, 1]);
            }
        }

        logAction('Fournisseur ajouté', $nom, 'Fournisseurs');
        redirect($_SERVER['PHP_SELF'], "Fournisseur « $nom » ajouté.");
    }
    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM fournisseurs WHERE id=?")->execute([$id]);
        logAction('Fournisseur supprimé', '#'.$id, 'Fournisseurs');
        redirect($_SERVER['PHP_SELF'], 'Fournisseur supprimé.', 'info');
    }
}

$fournisseurs = $db->query("SELECT * FROM fournisseurs ORDER BY nom")->fetchAll();
$produits = $db->query("SELECT id, nom FROM produits ORDER BY nom")->fetchAll();
foreach ($fournisseurs as &$f) {
    $produitsFournisseur = $db->prepare("SELECT p.nom FROM fournisseur_produit fp LEFT JOIN produits p ON p.id = fp.produit_id WHERE fp.fournisseur_id=? AND (p.deleted_at IS NULL OR CAST(p.deleted_at AS CHAR) = '' OR CAST(p.deleted_at AS CHAR) = '0000-00-00 00:00:00') ORDER BY p.nom");
    $produitsFournisseur->execute([$f['id']]);
    $f['produits'] = $produitsFournisseur->fetchAll(PDO::FETCH_COLUMN);
}
$pageTitle = '<i class="fa-solid fa-handshake" style="color:var(--gold)"></i> Fournisseurs';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-handshake text-gold"></i> Fournisseurs</h1><p>Gestion des contacts &amp; historique</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddFournisseur')"><i class="fa-solid fa-plus"></i> Nouveau fournisseur</button>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Nom</th><th>Contact</th><th>Téléphone</th><th>Produits</th><th>Solde</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if($fournisseurs): foreach($fournisseurs as $f): ?>
        <tr>
          <td><strong><?= e($f['nom']) ?></strong></td>
          <td><?= e($f['contact'] ?: '—') ?></td>
          <td class="td-muted"><?= e($f['telephone'] ?: '—') ?></td>
          <td class="td-muted"><?= e(implode(', ', $f['produits'] ?? [])) ?: '—' ?></td>
          <td class="td-gold"><?= fmt($f['solde']) ?></td>
          <td>
            <form method="POST" onsubmit="return confirm('Supprimer ce fournisseur ?')"><?= csrfField() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $f['id'] ?>">
              <button class="btn btn-icon"><i class="fa-solid fa-trash" style="font-size:11px"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:var(--txt2)">Aucun fournisseur</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAddFournisseur">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3><i class="fa-solid fa-handshake text-gold"></i> Nouveau fournisseur</h3><button class="modal-close" onclick="closeModal('modalAddFournisseur')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Raison sociale *</label><input class="form-control" name="nom" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Contact</label><input class="form-control" name="contact"></div>
          <div class="form-group"><label class="form-label">Téléphone</label><input class="form-control" name="telephone"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Produits associés</label>
          <div style="max-height:220px;overflow:auto;border:1px solid var(--border);border-radius:var(--r2);padding:8px;background:rgba(255,255,255,.03);">
            <?php if ($produits): foreach($produits as $p): ?>
              <label style="display:flex;align-items:center;gap:8px;padding:6px 0;cursor:pointer;">
                <input type="checkbox" name="produit_ids[]" value="<?= $p['id'] ?>">
                <span><?= e($p['nom']) ?></span>
              </label>
            <?php endforeach; else: ?>
              <div class="td-muted">Aucun produit disponible</div>
            <?php endif; ?>
          </div>
          <div class="td-muted" style="font-size:11px;margin-top:6px">Cochez les produits à associer au fournisseur.</div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddFournisseur')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
