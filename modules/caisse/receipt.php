<?php
// ============================================================
//  NightERP Pro — Reçu de vente imprimable
//  Fichier : modules/caisse/receipt.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('principal');
$db = getDB();

$orderId = intval($_GET['order'] ?? 0);
if (!$orderId) {
    die('Reçu invalide.');
}

$st = $db->prepare("SELECT * FROM commandes WHERE id = ?");
$st->execute([$orderId]);
$order = $st->fetch();
if (!$order) {
    die('Commande introuvable.');
}

$items = getCommandeDetails($db, $orderId);

// Informations entreprise (modifiable)
$company = [
    'name' => 'NightERP — Club & Bar',
    'address' => 'Votre adresse ici',
    'phone' => '+000 000 000',
    'tax_id' => 'N/A'
];

$pageTitle = 'Reçu — #' . $orderId;
require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.receipt-wrap { max-width:720px; margin:20px auto; background:var(--card); border:1px solid var(--border); padding:18px; border-radius:8px; }
.receipt-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
.receipt-company { font-weight:700; color:var(--gold); font-size:18px }
.receipt-meta { text-align:right; color:var(--txt2); font-size:13px }
.receipt-items { margin-top:14px; border-top:1px dashed var(--border); padding-top:12px }
.receipt-items table { width:100%; border-collapse:collapse; }
.receipt-items th, .receipt-items td { padding:8px 6px; font-size:14px; border-bottom:1px dashed rgba(255,255,255,.04); }
.receipt-items th { text-align:left; color:var(--txt2); font-size:12px }
.receipt-totals { margin-top:12px; display:flex; justify-content:flex-end; gap:12px; flex-direction:column; max-width:320px; margin-left:auto }
.receipt-totals .row { display:flex; justify-content:space-between; color:var(--txt2); padding:6px 0 }
.receipt-totals .row.total { font-weight:800; color:var(--gold); font-size:18px }
.receipt-footer { margin-top:18px; text-align:center; color:var(--txt3); font-size:13px }
.print-actions { display:flex; gap:8px; margin-bottom:12px }
@media print { .no-print { display:none !important } }
</style>

<div class="page-header">
  <h1><i class="fa-solid fa-receipt text-gold"></i> Reçu de vente</h1>
  <p>Commande #<?= e($orderId) ?> — <?= date('d/m/Y H:i', strtotime($order['created_at'] ?? $order['date'] ?? 'now')) ?></p>
</div>

<div class="receipt-wrap card">
  <div class="print-actions no-print">
    <a class="btn btn-ghost" href="<?= $root ?>modules/caisse/receipt_ticket.php?order=<?= $orderId ?>" target="_blank"><i class="fa-solid fa-ticket"></i> Imprimer (Ticket)</a>
    <a class="btn" href="<?= $root ?>modules/caisse/pos.php">Retour POS</a>
  </div>

  <div class="receipt-head">
    <div>
      <div class="receipt-company"><?= e($company['name']) ?></div>
      <div style="color:var(--txt2);margin-top:6px"><?= e($company['address']) ?> • <?= e($company['phone']) ?></div>
    </div>
    <div class="receipt-meta">
      <div>Reçu: <strong>#<?= e($orderId) ?></strong></div>
      <div>Caissier: <strong><?= e($_SESSION['user']['nom'] ?? '—') ?></strong></div>
      <div>Mode: <strong><?= e($order['mode_paiement']) ?></strong></div>
      <div>Table: <strong><?= $order['table_id'] ? e($order['table_id']) : '—' ?></strong></div>
    </div>
  </div>

  <div class="receipt-items">
    <table>
      <thead>
        <tr><th>Article</th><th style="width:70px;text-align:center">Qté</th><th style="width:110px;text-align:right">Prix</th></tr>
      </thead>
      <tbody>
        <?php foreach($items as $it): ?>
        <tr>
          <td style="vertical-align:middle">
            <div style="font-weight:700;color:var(--txt)"><?= e($it['produit_nom'] ?? 'Produit') ?></div>
          </td>
          <td style="text-align:center"><?= e($it['qte']) ?></td>
          <td style="text-align:right"><?= e(number_format((int)$it['prix'],0,',',' ')) ?> F</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="receipt-totals">
    <div class="row"><span>Sous-total</span><span><?= fmt($order['sous_total']) ?></span></div>
    <div class="row"><span>Remise</span><span>0 %</span></div>
    <div class="row total"><span>Total</span><span><?= fmt($order['total']) ?></span></div>
  </div>

  <div class="receipt-footer">
    <div>Merci pour votre achat !</div>
    <div style="margin-top:6px;color:var(--txt3);font-size:12px">Ce reçu fait foi de la transaction.</div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php';
