<?php
// ============================================================
//  NightERP Pro — Réservations
//  Fichier : modules/reservations/reservations.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('principal');
$db   = getDB();
$root = rootPath(__FILE__);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'ajouter') {
        $cid = intval($_POST['client_id'] ?? 0) ?: null;
        $tid = intval($_POST['table_id'] ?? 0) ?: null;
        if (!$cid && empty($_POST['client_nom'])) redirect($_SERVER['PHP_SELF'], 'Le client est requis.', 'error');
        if ($cid === null && !empty($_POST['client_nom'])) {
            $st = $db->prepare("INSERT INTO clients (pseudo,telephone,statut) VALUES (?,?,?)");
            $st->execute([trim($_POST['client_nom']), trim($_POST['telephone'] ?? ''), 'actif']);
            $cid = $db->lastInsertId();
        }
        $db->prepare("INSERT INTO reservations (client_id,user_id,table_id,date_resa,heure,nb_personnes,acompte,statut) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$cid, $_SESSION['user_id'], $tid, $_POST['date_resa']??null, $_POST['heure']??null, intval($_POST['nb_personnes']??2), floatval($_POST['acompte']??0), 1]);
        if ($tid) $db->prepare("UPDATE tables_club SET statut='Réservée' WHERE id=?")->execute([$tid]);
        logAction('Réservation créée', "client #$cid — ".($_POST['date_resa']??''), 'Réservations');
        redirect($_SERVER['PHP_SELF'], 'Réservation confirmée.');
    }
    if ($action === 'supprimer') {
        $id = intval($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
        logAction('Réservation supprimée', '#'.$id, 'Réservations');
        redirect($_SERVER['PHP_SELF'], 'Réservation supprimée.', 'info');
    }
}

$resa   = $db->query("SELECT r.*,tc.nom as t_nom,tc.zone,c.pseudo,c.telephone FROM reservations r LEFT JOIN tables_club tc ON r.table_id=tc.id LEFT JOIN clients c ON c.id=r.client_id ORDER BY r.date_resa DESC,r.heure DESC")->fetchAll();
$tables = $db->query("SELECT id,nom,zone FROM tables_club ORDER BY zone,nom")->fetchAll();
$clients = getClients($db);

$pageTitle = '<i class="fa-solid fa-calendar-check" style="color:var(--gold)"></i> Réservations';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-calendar-check text-gold"></i> Réservations</h1><p>Gestion des tables réservées</p></div>
  <button class="btn btn-primary" onclick="openModal('modalAddReservation')"><i class="fa-solid fa-plus"></i> Nouvelle réservation</button>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>#</th><th>Client</th><th>Table</th><th>Date</th><th>Heure</th><th>Personnes</th><th>Acompte</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if($resa): foreach($resa as $r): ?>
        <tr>
          <td class="td-muted">#<?= $r['id'] ?></td>
          <td><strong><?= e($r['pseudo'] ?? '—') ?></strong><?php if($r['telephone']): ?><div class="td-muted"><?= e($r['telephone']) ?></div><?php endif; ?></td>
          <td><?= $r['t_nom'] ? e($r['zone']).' — '.e($r['t_nom']) : '—' ?></td>
          <td class="td-muted"><?= $r['date_resa'] ? date('d/m/Y',strtotime($r['date_resa'])) : '—' ?></td>
          <td class="td-muted"><?= $r['heure'] ? substr($r['heure'],0,5) : '—' ?></td>
          <td class="td-muted"><?= $r['nb_personnes'] ?> pers.</td>
          <td class="td-gold"><?= fmt($r['acompte']) ?></td>
          <td><?= badge($r['statut'] ? 'Confirmée' : 'Annulée', $r['statut'] ? 'green' : 'red') ?></td>
          <td><form method="POST" onsubmit="return confirm('Supprimer ?')"><?= csrfField() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn btn-icon"><i class="fa-solid fa-trash" style="font-size:11px"></i></button></form></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--txt2)">Aucune réservation</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay hidden" id="modalAddReservation">
  <div class="modal">
    <div class="modal-header"><h3><i class="fa-solid fa-calendar-plus text-gold"></i> Nouvelle réservation</h3><button class="modal-close" onclick="closeModal('modalAddReservation')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <?= csrfField() ?><input type="hidden" name="action" value="ajouter">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Client</label><select class="form-control" name="client_id"><option value="">Nouveau client</option><?php foreach($clients as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['pseudo']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Nom du client</label><input class="form-control" name="client_nom" placeholder="Si nouveau"></div>
        </div>
        <div class="form-group"><label class="form-label">Téléphone</label><input class="form-control" name="telephone"></div>
        <div class="form-row3">
          <div class="form-group"><label class="form-label">Table</label><select class="form-control" name="table_id">
            <option value="">Aucune</option><?php foreach($tables as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['zone']) ?> — <?= e($t['nom']) ?></option><?php endforeach; ?>
          </select></div>
          <div class="form-group"><label class="form-label">Date</label><input class="form-control" type="date" name="date_resa" value="<?= date('Y-m-d') ?>"></div>
          <div class="form-group"><label class="form-label">Heure</label><input class="form-control" type="time" name="heure"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Nb personnes</label><input class="form-control" type="number" name="nb_personnes" min="1" value="2"></div>
          <div class="form-group"><label class="form-label">Acompte (F)</label><input class="form-control" type="number" name="acompte" value="0"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('modalAddReservation')">Annuler</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Réserver</button></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
