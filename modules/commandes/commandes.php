<?php
// ============================================================
//  NightERP Pro — Commandes
//  Fichier : modules/commandes/commandes.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('principal');
$db   = getDB();
$root = rootPath(__FILE__);

// Annuler / Supprimer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id     = intval($_POST['id'] ?? 0);
    if ($action === 'supprimer') {
        $db->prepare("DELETE FROM commandes WHERE id=?")->execute([$id]);
        logAction('Commande supprimée','#'.$id,'Commandes');
        redirect($_SERVER['PHP_SELF'],'Commande supprimée.','info');
    }
    if ($action === 'annuler') {
        $db->prepare("UPDATE commandes SET statut='Annulée' WHERE id=?")->execute([$id]);
        logAction('Commande annulée','#'.$id,'Commandes');
        redirect($_SERVER['PHP_SELF'],'Commande annulée.','info');
    }
}

$cmds = $db->query("SELECT c.*,tc.nom as t_nom,tc.zone,u.nom as u_nom FROM commandes c LEFT JOIN tables_club tc ON c.table_id=tc.id LEFT JOIN utilisateurs u ON c.utilisateur_id=u.id ORDER BY c.id DESC")->fetchAll();
// echo '<pre>';
// var_dump($cmds);
// echo '</pre>';

foreach ($cmds as &$c) {
  $c['details'] = getCommandeDetails($db, $c['id']);
}
$pageTitle = '<i class="fa-solid fa-receipt" style="color:var(--gold)"></i> Commandes';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header flex items-center justify-between">
  <div><h1><i class="fa-solid fa-receipt text-gold"></i> Commandes</h1><p>Suivi de toutes les commandes</p></div>
</div>
<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Date</th>
          <th>Table</th>
          <th>Articles</th>
          <th>Total</th>
          <th>Paiement</th>
          <th>Statut</th>
          <th>Caissier</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($cmds): foreach($cmds as $c):
          $resume = implode(', ', array_map(fn($i)=>($i['produit_nom'] ?? 'Produit').' ×'.$i['qte'], $c['details']));
          $sc = $c['statut']==='Payée'?'pill-green':($c['statut']==='Annulée'?'pill-red':'pill-blue');
        ?>
        <tr>
          <td class="td-muted">#<?= str_pad($c['id'],4,'0',STR_PAD_LEFT) ?></td>
          <td class="td-muted"><?= dateStr($c['created_at']) ?></td>
          <td><?= $c['t_nom'] ? e($c['zone']).' — '.e($c['t_nom']) : '—' ?></td>
          <td class="td-muted" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($resume) ?></td>
          <td class="td-gold"><?= fmt($c['total'] ?? 0) ?></td>
          <td><?= badge($c['mode_paiement']??'—','blue') ?></td>
          <td><?= badge($c['statut'],$sc==='pill-green'?'green':($sc==='pill-red'?'red':'blue')) ?></td>
          <td class="td-muted"><?= e($c['u_nom']??'—') ?></td>
          <td style="white-space:nowrap">
            <?php if($c['statut']==='En attente'||$c['statut']==='En cours'): ?>
            <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="annuler"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="btn btn-icon btn-sm" title="Annuler"><i class="fa-solid fa-ban" style="font-size:11px"></i></button></form>
            <?php endif; ?>
            <a class="btn btn-ghost btn-sm" href="<?= $root ?>modules/caisse/receipt.php?order=<?= $c['id'] ?>" target="_blank" title="Imprimer"><i class="fa-solid fa-print" style="font-size:11px"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?= csrfField() ?><input type="hidden" name="action" value="supprimer"><input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button class="btn btn-icon btn-sm"><i class="fa-solid fa-trash" style="font-size:11px"></i></button></form>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="9" style="text-align:center;padding:30px;color:var(--txt2)">Aucune commande</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
