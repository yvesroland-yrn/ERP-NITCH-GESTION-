<?php
// ============================================================
//  NightERP Pro — Dépenses
//  Fichier : modules/depenses/depenses.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('finances');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'ajouter') {
        $mt = floatval($_POST['montant'] ?? 0);
        if ($mt <= 0) redirect($_SERVER['PHP_SELF'], 'Montant invalide.', 'error');
        $db->prepare("INSERT INTO depenses (libelle,description,montant,statut) VALUES (?,?,?,?)")
           ->execute([$_POST['libelle']??'Autre', trim($_POST['description']??''), $mt, 'actif']);
        logAction('Dépense enregistrée', ($_POST['libelle']??'Autre').' — '.fmt($mt), 'Dépenses');
        redirect($_SERVER['PHP_SELF'], 'Dépense enregistrée.');
    }
    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM depenses WHERE id=?")->execute([$id]);
        logAction('Dépense supprimée', '#'.$id, 'Dépenses');
        redirect($_SERVER['PHP_SELF'], 'Dépense supprimée.', 'info');
    }
}

$deps = $db->query("SELECT * FROM depenses ORDER BY id DESC")->fetchAll();
$todayDeps = array_filter($deps, fn($d)=>str_starts_with($d['created_at'], date('Y-m-d')));
$depTot = array_sum(array_column($deps,'montant'));
$caTot  = $db->query("SELECT COALESCE(SUM(total),0) as t FROM commandes WHERE statut='Payée'")->fetch()['t'];

$catIcons = ['Carburant'=>'fa-gas-pump','Électricité'=>'fa-bolt','Salaires'=>'fa-users','Eau'=>'fa-droplet','Internet'=>'fa-wifi','Publicité'=>'fa-bullhorn','Loyer'=>'fa-building','Entretien'=>'fa-wrench','Autre'=>'fa-file'];

$pageTitle = '<i class="fa-solid fa-file-invoice-dollar" style="color:var(--gold)"></i> Dépenses';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-file-invoice-dollar text-gold"></i> Dépenses</h1><p>Charges &amp; frais de l'établissement</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddDepense')"><i class="fa-solid fa-plus"></i> Nouvelle dépense</button>
</div>
<div class="g3 mb16">
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-calendar-day"></i> Ce jour</div><div class="stat-value text-danger"><?= fmt(array_sum(array_column($todayDeps,'montant'))) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-calendar-week"></i> Total dépenses</div><div class="stat-value"><?= fmt($depTot) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-chart-pie"></i> Bénéfice net</div><div class="stat-value text-success"><?= fmt($caTot-$depTot) ?></div></div>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Date</th><th>Libellé</th><th>Description</th><th>Montant</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if($deps): foreach($deps as $d): ?>
        <tr>
          <td class="td-muted"><?= dateStr($d['created_at']) ?></td>
          <td><span class="pill pill-orange" style="font-size:10px"><i class="fa-solid <?= $catIcons[$d['libelle']] ?? 'fa-file' ?>"></i> <?= e($d['libelle']) ?></span></td>
          <td><?= e($d['description'] ?: '—') ?></td>
          <td class="text-danger fw700"><?= fmt($d['montant']) ?></td>
          <td><form method="POST" onsubmit="return confirm('Supprimer ?')"><?= csrfField() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $d['id'] ?>">
            <button class="btn btn-icon"><i class="fa-solid fa-trash" style="font-size:11px"></i></button></form></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--txt2)">Aucune dépense</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAddDepense">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><h3><i class="fa-solid fa-file-invoice-dollar text-gold"></i> Nouvelle dépense</h3><button class="modal-close" onclick="closeModal('modalAddDepense')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Libellé</label><select class="form-control" name="libelle">
          <?php foreach(array_keys($catIcons) as $c): ?><option><?= $c ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label class="form-label">Description</label><input class="form-control" name="description"></div>
        <div class="form-group"><label class="form-label">Montant (F)</label><input class="form-control" type="number" name="montant" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddDepense')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
