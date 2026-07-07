<?php
// ============================================================
//  NightERP Pro — Produits
//  Fichier : modules/produits/produits.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('stock');

$db   = getDB();
$root = rootPath(__FILE__);

// --- ACTIONS POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $nom        = trim($_POST['nom'] ?? '');
        $categorie  = intval($_POST['categorie_id'] ?? 0);
        $pa         = floatval($_POST['prix_achat']  ?? 0);
        $pv         = floatval($_POST['prix_vente']  ?? 0);
        $barcode    = trim($_POST['code_barre']      ?? '') ?: null;

        if (!$nom || !$categorie) redirect($_SERVER['PHP_SELF'], 'Le nom et la catégorie sont requis.', 'error');

        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImageProduit($_FILES['image']);
            if (!$image) redirect($_SERVER['PHP_SELF'], 'Image invalide (JPEG/PNG/WEBP, max 5 Mo).', 'error');
        }

        $st = $db->prepare("INSERT INTO produits (nom,image,categorie_id,prix_achat,prix_vente,code_barre,statut) VALUES (?,?,?,?,?,?,?)");
        $st->execute([$nom,$image,$categorie,$pa,$pv,$barcode,'actif']);
        $prodId = $db->lastInsertId();
        if (!empty($_POST['fournisseur_id'])) {
            $db->prepare("INSERT INTO fournisseur_produit (fournisseur_id,produit_id,montant) VALUES (?,?,?)")->execute([intval($_POST['fournisseur_id']), $prodId, $pv]);
        }
        logAction('Produit ajouté', $nom, 'Produits');
        redirect($_SERVER['PHP_SELF'], "Produit « $nom » ajouté avec succès.");
    }

    // SUPPRIMER PRODUIT
    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        $p  = $db->prepare("SELECT nom,image FROM produits WHERE id=?"); $p->execute([$id]); $prod = $p->fetch();
        if ($prod) {
            // Supprimer l'image du disque
            if ($prod['image'] && file_exists(UPLOAD_PATH . $prod['image'])) {
                unlink(UPLOAD_PATH . $prod['image']);
            }
            $db->prepare("DELETE FROM produits WHERE id=?")->execute([$id]);
            logAction('Produit supprimé', $prod['nom'], 'Produits');
            redirect($_SERVER['PHP_SELF'], "Produit supprimé.", 'info');
        }
    }
}

// --- Données ---
$search     = trim($_GET['q'] ?? '');
$fournisseurs = $db->query("SELECT id,nom FROM fournisseurs WHERE (deleted_at IS NULL OR CAST(deleted_at AS CHAR) = '' OR CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00') ORDER BY nom")->fetchAll();
$categories = getCategoriesProduits($db);
$sql        = "SELECT p.*, cp.nom AS categorie_nom FROM produits p LEFT JOIN categories_produits cp ON cp.id=p.categorie_id";
if ($search) {
    $st = $db->prepare($sql . " WHERE p.nom LIKE ? ORDER BY cp.nom, p.nom");
    $st->execute(['%'.$search.'%']);
} else {
    $st = $db->query($sql . " ORDER BY cp.nom, p.nom");
}
$produits = $st->fetchAll();
foreach ($produits as &$p) {
    $fournisseursProduit = $db->prepare("SELECT f.nom FROM fournisseur_produit fp LEFT JOIN fournisseurs f ON f.id = fp.fournisseur_id WHERE fp.produit_id=? AND (f.deleted_at IS NULL OR CAST(f.deleted_at AS CHAR) = '' OR CAST(f.deleted_at AS CHAR) = '0000-00-00 00:00:00') ORDER BY f.nom");
    $fournisseursProduit->execute([$p['id']]);
    $p['fournisseurs'] = $fournisseursProduit->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = '<i class="fa-solid fa-box-open" style="color:var(--gold)"></i> Produits';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header flex items-center justify-between">
  <div>
    <h1><i class="fa-solid fa-box-open text-gold"></i> Produits</h1>
    <p>Catalogue boissons, nourritures &amp; services</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modalAddProduit')">
    <i class="fa-solid fa-plus"></i> Nouveau produit
  </button>
</div>

<!-- Recherche -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:16px">
  <div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:var(--r2);padding:8px 12px;flex:1">
    <i class="fa-solid fa-magnifying-glass" style="color:var(--txt3)"></i>
    <input type="text" name="q" placeholder="Rechercher un produit..." value="<?= e($search) ?>"
           style="background:none;border:none;color:var(--txt);font-size:13px;outline:none;width:100%">
  </div>
  <button type="submit" class="btn btn-ghost"><i class="fa-solid fa-search"></i> Chercher</button>
  <?php if ($search): ?><a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost"><i class="fa-solid fa-xmark"></i></a><?php endif; ?>
</form>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>Image</th><th>Nom</th><th>Catégorie</th>
          <th>P. Achat</th><th>P. Vente</th><th>Stock</th>
          <th>Fournisseur</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($produits): ?>
          <?php foreach ($produits as $p): ?>
          <?php
            $stock = getProduitStock($db, $p['id']);
            $pct = 100;
            $color = 'var(--green)';
          ?>
          <tr>
            <td>
              <?php if ($p['image'] && file_exists(UPLOAD_PATH . $p['image'])): ?>
                <img src="<?= $root . UPLOAD_URL . e($p['image']) ?>" class="prod-img" alt="<?= e($p['nom']) ?>">
              <?php else: ?>
                <span style="font-size:28px">📦</span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e($p['nom']) ?></strong>
              <?php if ($p['code_barre']): ?>
                <div class="td-muted"><?= e($p['code_barre']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= badge($p['categorie_nom'] ?? '—', 'blue') ?></td>
            <td class="td-muted"><?= fmt($p['prix_achat']) ?></td>
            <td class="td-gold"><?= fmt($p['prix_vente']) ?></td>
            <td>
              <span style="color:<?= $color ?>;font-weight:600"><?= $stock ?></span>
              <div style="width:60px;height:5px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:6px">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:3px"></div>
              </div>
            </td>
            <td class="td-muted"><?= e(implode(', ', $p['fournisseurs'] ?? [])) ?: '—' ?></td>
            <td>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce produit ?')">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button type="submit" class="btn btn-icon" title="Supprimer">
                  <i class="fa-solid fa-trash" style="font-size:11px"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--txt2)">
            <i class="fa-solid fa-box-open" style="font-size:30px;opacity:.3;display:block;margin-bottom:8px"></i>
            Aucun produit trouvé
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ===== MODAL AJOUT PRODUIT ===== -->
<div class="modal-overlay hidden" id="modalAddProduit">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-box-open text-gold"></i> Nouveau produit</h3>
      <button class="modal-close" onclick="closeModal('modalAddProduit')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="ajouter">
      <div class="modal-body">

        <!-- Upload image -->
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-image"></i> Photo du produit</label>
          <div class="upload-zone" onclick="document.getElementById('img-produit').click()">
            <input type="file" id="img-produit" name="image" accept="image/jpeg,image/png,image/webp"
                   onchange="previewImage(this,'img-preview')">
            <i class="fa-solid fa-cloud-arrow-up" style="font-size:28px;color:var(--gold);opacity:.6"></i>
            <p>Cliquer pour choisir une photo (JPEG / PNG / WEBP, max 5 Mo)</p>
            <img id="img-preview" class="preview-img" style="display:none;margin:10px auto 0" alt="prévisualisation">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nom du produit *</label>
            <input class="form-control" name="nom" placeholder="Ex : Jack Daniel's Black" required>
          </div>
          <div class="form-group">
            <label class="form-label">Catégorie *</label>
            <select class="form-control" name="categorie_id">
              <?php foreach($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= e($cat['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fournisseur</label>
            <select class="form-control" name="fournisseur_id">
              <option value="">Aucun</option>
              <?php foreach($fournisseurs as $f): ?>
              <option value="<?= $f['id'] ?>"><?= e($f['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Code-barres</label>
            <input class="form-control" name="code_barre" placeholder="Optionnel">
          </div>
        </div>

        <div class="form-row2">
          <div class="form-group">
            <label class="form-label">Prix d'achat (F)</label>
            <input class="form-control" type="number" name="prix_achat" placeholder="0" min="0">
          </div>
          <div class="form-group">
            <label class="form-label">Prix de vente (F)</label>
            <input class="form-control" type="number" name="prix_vente" placeholder="0" min="0">
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalAddProduit')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
