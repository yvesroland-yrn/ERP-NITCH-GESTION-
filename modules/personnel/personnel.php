<?php
// ============================================================
//  NightERP Pro — Personnel
//  Fichier : modules/personnel/personnel.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireLogin();
if (!isAdmin()) redirect('../../modules/dashboard.php', 'Accès refusé.', 'error');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'ajouter') {
        $nom = trim($_POST['nom'] ?? '');
        if (!$nom) redirect($_SERVER['PHP_SELF'], 'Le nom est requis.', 'error');
        $db->prepare("INSERT INTO personnel (nom,role,telephone,salaire) VALUES (?,?,?,?)")
           ->execute([$nom, $_POST['role']??'Serveur', trim($_POST['telephone']??''), floatval($_POST['salaire']??0)]);
        logAction('Employé ajouté', $nom, 'Personnel');
        redirect($_SERVER['PHP_SELF'], "Employé « $nom » ajouté.");
    }
    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM personnel WHERE id=?")->execute([$id]);
        logAction('Employé supprimé', '#'.$id, 'Personnel');
        redirect($_SERVER['PHP_SELF'], 'Employé supprimé.', 'info');
    }
    if ($action === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $st = $_POST['statut'] === 'Actif' ? 'Inactif' : 'Actif';
        $db->prepare("UPDATE personnel SET statut=? WHERE id=?")->execute([$st,$id]);
        redirect($_SERVER['PHP_SELF'], 'Statut mis à jour.', 'info');
    }
}

$staff = $db->query("SELECT * FROM personnel ORDER BY role,nom")->fetchAll();
$pageTitle = '<i class="fa-solid fa-id-badge" style="color:var(--gold)"></i> Personnel';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-id-badge text-gold"></i> Personnel</h1><p>Gestion de l'équipe &amp; performances</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddStaff')"><i class="fa-solid fa-plus"></i> Nouvel employé</button>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Employé</th><th>Rôle</th><th>Téléphone</th><th>Ventes</th><th>CA généré</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if($staff): foreach($staff as $s): ?>
        <tr>
          <td><div class="flex items-center" style="gap:8px">
            <div class="user-av" style="width:30px;height:30px;font-size:11px"><?= strtoupper(substr($s['nom'],0,2)) ?></div>
            <strong><?= e($s['nom']) ?></strong>
          </div></td>
          <td><?= badge($s['role'],'blue') ?></td>
          <td class="td-muted"><?= e($s['telephone'] ?: '—') ?></td>
          <td class="td-muted"><?= $s['nb_ventes'] ?></td>
          <td class="td-gold"><?= fmt($s['ca_genere']) ?></td>
          <td>
            <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="statut" value="<?= $s['statut'] ?>">
              <button class="btn" style="border:none;padding:0;background:none;cursor:pointer">
                <?= badge($s['statut'], $s['statut']==='Actif'?'green':'red') ?>
              </button>
            </form>
          </td>
          <td>
            <form method="POST" onsubmit="return confirm('Supprimer cet employé ?')"><?= csrfField() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $s['id'] ?>">
              <button class="btn btn-icon"><i class="fa-solid fa-trash" style="font-size:11px"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--txt2)">Aucun employé</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAddStaff">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3><i class="fa-solid fa-user-plus text-gold"></i> Nouvel employé</h3><button class="modal-close" onclick="closeModal('modalAddStaff')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Nom complet *</label><input class="form-control" name="nom" required></div>
          <div class="form-group"><label class="form-label">Téléphone</label><input class="form-control" name="telephone"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Rôle</label><select class="form-control" name="role">
            <option>Serveur</option><option>Caissier</option><option>Barman</option><option>Cuisinière</option><option>Sécurité</option><option>Manager</option>
          </select></div>
          <div class="form-group"><label class="form-label">Salaire mensuel (F)</label><input class="form-control" type="number" name="salaire" placeholder="0"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddStaff')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
