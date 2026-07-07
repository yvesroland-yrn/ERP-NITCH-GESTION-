<?php
// ============================================================
//  NightERP Pro — Reçu ticket thermique (standalone, sans JS)
//  Fichier : modules/caisse/receipt.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireLogin();

$db = getDB();

$orderId = intval($_GET['order'] ?? 0);
if (!$orderId) die('Reçu invalide.');

$st = $db->prepare("
    SELECT c.*,
           tc.nom   AS table_nom,
           tc.zone  AS table_zone,
           u.nom    AS caissier_nom
    FROM   commandes c
    LEFT   JOIN tables_club  tc ON c.table_id       = tc.id
    LEFT   JOIN utilisateurs u  ON c.utilisateur_id = u.id
    WHERE  c.id = ?
");
$st->execute([$orderId]);
$order = $st->fetch();
if (!$order) die('Commande introuvable.');

$items     = getCommandeDetails($db, $orderId);
$sousTotal = floatval($order['sous_total'] ?? 0);
$remise    = 0.0;
$total     = floatval($order['total']      ?? 0);
$remiseMt  = $sousTotal - $total;

$nbArticles = count($items);
$nbUnites   = array_sum(array_column($items, 'qte'));

$dateH = $order['created_at']
       ? date('d/m/Y', strtotime($order['created_at']))
       : date('d/m/Y');
$heureH = $order['created_at']
        ? date('H:i:s', strtotime($order['created_at']))
        : date('H:i:s');

// ── Personnaliser ici ──────────────────────────────────────
$company = [
    'name'    => 'NightERP Pro',
    'sub'     => 'CLUB & BAR',
    'address' => 'Abidjan, Cocody — Côte d\'Ivoire',
    'phone'   => '+225 07 XX XX XX XX',
    'merci'   => 'Merci pour votre visite. À bientôt !',
];

// Chemin retour POS
$root = rootUrl();

// Icônes texte selon mode de paiement
$payLabel = [
    'Espèces'        => '[CASH]',
    'Mobile Money'   => '[MOBILE]',
    'Carte bancaire' => '[CARTE]',
    'Mixte'          => '[MIXTE]',
];
$payIcon = $payLabel[$order['mode_paiement']] ?? '[—]';

// Formatage montant
function money(float $n): string {
    return number_format((int)round($n), 0, ',', ' ') . ' FCFA';
}

// Ligne de séparation (longueur fixe 32 chars)
function sep(string $c = '-'): string { return str_repeat($c, 32); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reçu #<?= str_pad($orderId,6,'0',STR_PAD_LEFT) ?></title>
<style>
/* ── Reset ───────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Écran : fond sombre, ticket centré ──────── */
html { background: #1a1a2e; min-height: 100vh; }
body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 13px;
    color: #111;
    background: transparent;
    padding: 30px 12px 80px;
}

/* ── Barre boutons (masquée à l'impression) ──── */
.btn-bar {
    max-width: 320px;
    margin: 0 auto 24px;
    display: flex;
    gap: 8px;
}
.btn-bar a,
.btn-bar button {
    flex: 1;
    padding: 10px 0;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    border: none;
    text-decoration: none;
    display: inline-block;
}
.btn-print {
    background: #C9A84C;
    color: #000;
}
.btn-back {
    background: #2a2a3e;
    color: #C9A84C;
    border: 1px solid #C9A84C !important;
}

/* ── Ticket ──────────────────────────────────── */
.ticket {
    width: 320px;
    margin: 0 auto;
    background: #ffffff;
    color: #111;

    /* Bords dentelés haut et bas (zigzag CSS) */
    padding: 0 0 20px;

    /* Ombre portée sur fond sombre */
    box-shadow: 0 12px 50px rgba(0,0,0,.7);

    /* Découpe en dents de scie haut */
    position: relative;
}

/* Dents de scie en haut du ticket */
.ticket::before {
    content: '';
    display: block;
    height: 14px;
    background:
        radial-gradient(circle at 7px -1px, #1a1a2e 6px, #fff 7px) top left / 14px 14px repeat-x;
    margin-bottom: 0;
}
/* Dents de scie en bas du ticket */
.ticket::after {
    content: '';
    display: block;
    height: 14px;
    background:
        radial-gradient(circle at 7px 15px, #1a1a2e 6px, #fff 7px) bottom left / 14px 14px repeat-x;
    margin-top: 20px;
}

/* ── Zone en-tête ─────────────────────────────── */
.t-head {
    padding: 20px 18px 14px;
    text-align: center;
}
.t-name {
    font-size: 18px;
    font-weight: 900;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #000;
    line-height: 1.2;
}
.t-sub {
    font-size: 10px;
    letter-spacing: 4px;
    color: #666;
    margin-top: 2px;
    text-transform: uppercase;
}
.t-addr {
    font-size: 11px;
    color: #555;
    margin-top: 8px;
    line-height: 1.7;
}

/* ── Séparateurs ─────────────────────────────── */
.sep { margin: 0 18px; border: none; border-top: 1px dashed #bbb; }
.sep-solid { margin: 0 18px; border: none; border-top: 2px solid #111; }

/* ── Infos transaction ───────────────────────── */
.t-meta {
    padding: 10px 18px;
    font-size: 12px;
    line-height: 2;
    color: #333;
}
.t-meta .row {
    display: flex;
    justify-content: space-between;
}
.t-meta .row span:first-child { color: #777; }
.t-meta .row strong { color: #000; font-weight: 700; }

/* ── Titre section ───────────────────────────── */
.t-section {
    padding: 6px 18px;
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-align: center;
    color: #888;
    background: #f8f8f8;
}

/* ── En-tête colonnes ────────────────────────── */
.t-col-head {
    display: grid;
    grid-template-columns: 1fr 36px 90px;
    padding: 6px 18px;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #999;
    background: #f2f2f2;
}
.t-col-head span:nth-child(2) { text-align: center; }
.t-col-head span:nth-child(3) { text-align: right; }

/* ── Articles ────────────────────────────────── */
.t-items { padding: 4px 0; }
.t-item  { padding: 8px 18px; border-bottom: 1px dotted #e0e0e0; }
.t-item-row {
    display: grid;
    grid-template-columns: 1fr 36px 90px;
    align-items: start;
    gap: 2px;
}
.t-item-name {
    font-size: 12.5px;
    font-weight: 700;
    color: #000;
    line-height: 1.35;
}
.t-item-qty {
    font-size: 12px;
    text-align: center;
    color: #333;
}
.t-item-total {
    font-size: 12.5px;
    text-align: right;
    font-weight: 700;
    color: #000;
}
.t-item-pu {
    font-size: 10.5px;
    color: #888;
    margin-top: 2px;
    grid-column: 1 / -1;
}

/* ── Totaux ──────────────────────────────────── */
.t-totals { padding: 10px 18px; background: #fafafa; }
.t-total-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    padding: 4px 0;
    font-size: 12.5px;
    color: #555;
    border-bottom: 1px dotted #e8e8e8;
}
.t-total-row:last-child { border-bottom: none; }
.t-total-row.grand {
    border-top: 2px solid #000;
    border-bottom: none;
    padding-top: 8px;
    margin-top: 4px;
    font-size: 17px;
    font-weight: 900;
    color: #000;
}
.t-total-row.grand span:last-child { font-size: 19px; }
.t-total-row.remise span:last-child { color: #C0392B; }

/* ── Paiement ────────────────────────────────── */
.t-pay {
    margin: 0 18px;
    padding: 10px 12px;
    background: #111;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    border-radius: 0;
}
.t-pay .mode-label { color: #aaa; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; }
.t-pay .mode-val   { font-weight: 900; font-size: 14px; letter-spacing: 1px; margin-top: 2px; }
.t-pay .mode-icon  { font-size: 11px; color: #C9A84C; letter-spacing: 1px; }

/* ── Comptage articles ───────────────────────── */
.t-count {
    text-align: center;
    font-size: 11px;
    color: #888;
    padding: 8px 18px;
    letter-spacing: 1px;
}

/* ── Code-barres CSS pur ─────────────────────── */
.barcode-wrap { padding: 10px 18px 4px; text-align: center; }
.barcode {
    display: inline-flex;
    align-items: flex-end;
    gap: 1.5px;
    height: 52px;
}
.b { display: inline-block; background: #000; border-radius: 0; }
.barcode-num {
    font-family: 'Courier New', monospace;
    font-size: 11px;
    letter-spacing: 4px;
    color: #555;
    text-align: center;
    padding: 3px 18px 0;
}

/* ── Pied du ticket ──────────────────────────── */
.t-footer {
    padding: 12px 18px 0;
    text-align: center;
}
.t-footer .merci {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #111;
}
.t-footer .legal {
    font-size: 10.5px;
    color: #999;
    margin-top: 5px;
    line-height: 1.6;
}

/* ── Impression ──────────────────────────────── */
@page { size: 80mm auto; margin: 0; }
@media print {
    html { background: #fff !important; }
    body { padding: 0 !important; }
    .btn-bar { display: none !important; }
    .ticket {
        box-shadow: none !important;
        width: 100% !important;
    }
    .ticket::before,
    .ticket::after { display: none !important; }
}
</style>
</head>
<body>

<!-- Boutons actions (cachés à l'impression) -->
<div class="btn-bar">
    <button class="btn-print" onclick="window.print()">
        &#128438; Imprimer / PDF
    </button>
    <a class="btn-back" href="<?= $root ?>modules/caisse/pos.php">
        &#8592; Retour POS
    </a>
</div>

<!-- ══ TICKET ══════════════════════════════════════════════ -->
<div class="ticket">

    <!-- En-tête établissement -->
    <div class="t-head">
        <div class="t-name"><?= e($company['name']) ?></div>
        <div class="t-sub"><?= e($company['sub']) ?></div>
        <div class="t-addr">
            <?= e($company['address']) ?><br>
            Tél : <?= e($company['phone']) ?>
        </div>
    </div>

    <hr class="sep">

    <!-- Méta-données transaction -->
    <div class="t-meta">
        <div class="row">
            <span>Date</span>
            <strong><?= $dateH ?></strong>
        </div>
        <div class="row">
            <span>Heure</span>
            <strong><?= $heureH ?></strong>
        </div>
        <div class="row">
            <span>Reçu N°</span>
            <strong>#<?= str_pad($orderId,6,'0',STR_PAD_LEFT) ?></strong>
        </div>
        <div class="row">
            <span>Caissier</span>
            <strong><?= e($order['caissier_nom'] ?? $_SESSION['user']['nom'] ?? '—') ?></strong>
        </div>
        <?php if (!empty($order['table_nom'])): ?>
        <div class="row">
            <span>Table</span>
            <strong><?= e($order['table_zone'] . ' — ' . $order['table_nom']) ?></strong>
        </div>
        <?php endif; ?>
    </div>

    <hr class="sep-solid">

    <!-- En-têtes colonnes -->
    <div class="t-section">Détail commande</div>
    <div class="t-col-head">
        <span>Article</span>
        <span>Qté</span>
        <span>Montant</span>
    </div>
    <hr class="sep">

    <!-- Articles -->
    <div class="t-items">
        <?php foreach ($items as $it):
            $prix_u = floatval($it['prix']     ?? 0);
            $qty    = intval($it['quantite']   ?? 1);
            $mt_it  = $prix_u * $qty;
        ?>
        <div class="t-item">
            <div class="t-item-row">
                <div class="t-item-name"><?= e($it['nom'] ?? '?') ?></div>
                <div class="t-item-qty">x<?= $qty ?></div>
                <div class="t-item-total"><?= money($mt_it) ?></div>
            </div>
            <div class="t-item-pu">
                P.U : <?= money($prix_u) ?>/unité
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <hr class="sep">

    <!-- Totaux -->
    <div class="t-totals">
        <div class="t-total-row">
            <span>Sous-total</span>
            <span><?= money($sousTotal) ?></span>
        </div>
        <?php if ($remise > 0): ?>
        <div class="t-total-row remise">
            <span>Remise (<?= $remise ?>%)</span>
            <span>- <?= money($remiseMt) ?></span>
        </div>
        <?php endif; ?>
        <div class="t-total-row grand">
            <span>TOTAL</span>
            <span><?= money($total) ?></span>
        </div>
    </div>

    <!-- Mode de paiement -->
    <div class="t-pay">
        <div>
            <div class="mode-label">Payé par</div>
            <div class="mode-val"><?= e($order['mode_paiement'] ?? '—') ?></div>
        </div>
        <div class="mode-icon"><?= $payIcon ?></div>
    </div>

    <!-- Comptage -->
    <div class="t-count">
        (<?= $nbArticles ?> article<?= $nbArticles > 1 ? 's' : '' ?>
        &nbsp;·&nbsp;
        <?= $nbUnites ?> unité<?= $nbUnites > 1 ? 's' : '' ?>)
    </div>

    <!-- Code-barres CSS pur — aucun JS, aucune image -->
    <div class="barcode-wrap">
        <div class="barcode">
            <?php
            // Barres déterministes basées sur l'ID de commande
            srand($orderId * 7919);
            for ($i = 0; $i < 55; $i++) {
                $w = rand(1, 3);
                $h = rand(28, 50);
                echo '<span class="b" style="width:' . $w . 'px;height:' . $h . 'px"></span>';
            }
            srand();
            ?>
        </div>
    </div>
    <div class="barcode-num">
        <?= str_pad($orderId, 12, '0', STR_PAD_LEFT) ?>
    </div>

    <!-- Pied -->
    <div class="t-footer">
        <div class="merci">&#10022; <?= e($company['merci']) ?> &#10022;</div>
        <div class="legal">
            Ce reçu fait foi de la transaction.<br>
            Conservez-le pour tout litige ou retour.
        </div>
    </div>

</div><!-- /ticket -->

</body>
</html>
