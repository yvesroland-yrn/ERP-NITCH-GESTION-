<?php
// ============================================================
//  NightERP Pro — Tables & Zones
//  Fichier : modules/tables/tables.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('principal');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'ajouter') {
        $nom      = trim($_POST['nom'] ?? '');
        $zone     = $_POST['zone'] ?? 'Salle principale';
        $cap      = intval($_POST['capacite'] ?? 4);
        $statut   = $_POST['statut'] ?? 'Libre';
        if (!$nom) redirect($_SERVER['PHP_SELF'], 'Le nom de la table est requis.', 'error');
        $db->prepare("INSERT INTO tables_club (nom,zone,capacite,statut) VALUES (?,?,?,?)")->execute([$nom,$zone,$cap,$statut]);
        logAction('Table ajoutée', $nom, 'Tables');
        redirect($_SERVER['PHP_SELF'], "Table « $nom » ajoutée.");
    }

    if ($action === 'changer_statut') {
        $id = intval($_POST['id'] ?? 0);
        $t  = $db->prepare("SELECT * FROM tables_club WHERE id=?"); $t->execute([$id]); $row = $t->fetch();
        if ($row) {
            $next = $row['statut'] === 'Libre' ? 'Occupée' : 'Libre';
            $db->prepare("UPDATE tables_club SET statut=? WHERE id=?")->execute([$next,$id]);
            logAction('Statut table modifié', $row['nom'].' → '.$next, 'Tables');
        }
        redirect($_SERVER['PHP_SELF'], 'Statut mis à jour.', 'info');
    }

    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM tables_club WHERE id=?")->execute([$id]);
        logAction('Table supprimée', '#'.$id, 'Tables');
        redirect($_SERVER['PHP_SELF'], 'Table supprimée.', 'info');
    }
}

$tables = $db->query("SELECT * FROM tables_club ORDER BY zone,nom")->fetchAll();
$zones  = array_unique(array_column($tables,'zone'));
$libre  = count(array_filter($tables, fn($t)=>$t['statut']==='Libre'));
$occ    = count(array_filter($tables, fn($t)=>$t['statut']==='Occupée'));
$resa   = count(array_filter($tables, fn($t)=>$t['statut']==='Réservée'));

$pageTitle = '<i class="fa-solid fa-chair" style="color:var(--gold)"></i> Tables & Zones';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-chair text-gold"></i> Tables &amp; Zones</h1><p>Plan de salle interactif</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddTable')"><i class="fa-solid fa-plus"></i> Nouvelle table</button>
</div>

<div class="kpi-row mb20">
  <div class="kpi-chip"><div class="kpi-v" style="color:var(--green)"><?= $libre ?></div><div class="kpi-l">Libres</div></div>
  <div class="kpi-chip"><div class="kpi-v"><?= $occ ?></div><div class="kpi-l">Occupées</div></div>
  <div class="kpi-chip"><div class="kpi-v" style="color:var(--blue)"><?= $resa ?></div><div class="kpi-l">Réservées</div></div>
  <div class="kpi-chip"><div class="kpi-v"><?= count($tables) ?></div><div class="kpi-l">Total</div></div>
</div>

<?php foreach($zones as $zone): ?>
<div class="card mb16">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-layer-group text-gold"></i> Zone <?= e($zone) ?></div></div>
  <div class="zones-grid">
    <?php foreach(array_filter($tables, fn($t)=>$t['zone']===$zone) as $t):
      $cls = $t['statut']==='Libre'?'zone-free':($t['statut']==='Réservée'?'zone-reserved':'zone-occupied');
    ?>
    <div class="zone-card <?= $cls ?>">
      <div class="zone-num"><?= e($t['nom']) ?></div>
      <div class="zone-status"><?= e($t['statut']) ?></div>
      <div class="zone-detail"><?= $t['capacite'] ?> places</div>
      <div style="margin-top:6px;display:flex;gap:4px;justify-content:center">
        <form method="POST"><?= csrfField() ?><input type="hidden" name="action" value="changer_statut"><input type="hidden" name="id" value="<?= $t['id'] ?>">
          <button type="submit" class="btn btn-sm" style="font-size:9px;padding:3px 8px;<?= $t['statut']==='Occupée'?'background:var(--red2);color:var(--red);border:1px solid var(--red)':'background:var(--green2);color:var(--green);border:1px solid var(--green)' ?>">
            <?= $t['statut']==='Occupée' ? 'Libérer' : 'Occuper' ?>
          </button>
        </form>
        <form method="POST" onsubmit="return confirm('Supprimer cette table ?')"><?= csrfField() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $t['id'] ?>">
          <button type="submit" class="btn-icon btn" style="width:24px;height:24px"><i class="fa-solid fa-trash" style="font-size:9px"></i></button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>

<div class="modal-overlay hidden" id="modalAddTable">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3><i class="fa-solid fa-chair text-gold"></i> Nouvelle table</h3><button class="modal-close" onclick="closeModal('modalAddTable')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Numéro/Nom *</label><input class="form-control" name="nom" required placeholder="Ex: VIP-01 ou Table 7"></div>
          <div class="form-group"><label class="form-label">Zone</label>
            <select class="form-control" name="zone"><option>VIP</option><option>Terrasse</option><option selected>Salle principale</option><option>Lounge</option><option>Bar</option></select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Capacité (personnes)</label><input class="form-control" type="number" name="capacite" value="4" min="1"></div>
          <div class="form-group"><label class="form-label">Statut initial</label><select class="form-control" name="statut"><option>Libre</option><option>Réservée</option></select></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddTable')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Ajouter</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
