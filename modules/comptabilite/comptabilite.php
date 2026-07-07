<?php
// ============================================================
//  NightERP Pro — Comptabilité
//  Fichier : modules/comptabilite/comptabilite.php
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireModule('finances');
$db = getDB();

$caTot  = $db->query("SELECT COALESCE(SUM(total),0) as t FROM commandes WHERE statut='Payée'")->fetch()['t'];
$depTot = $db->query("SELECT COALESCE(SUM(montant),0) as t FROM depenses")->fetch()['t'];
$achTot = $db->query("SELECT COALESCE(SUM(montant),0) as t FROM achats")->fetch()['t'];
$sorties = $depTot + $achTot;

$ventes = $db->query("SELECT * FROM commandes WHERE statut='Payée' ORDER BY id DESC LIMIT 20")->fetchAll();
$deps   = $db->query("SELECT * FROM depenses ORDER BY id DESC LIMIT 10")->fetchAll();

$journal = [];
foreach ($ventes as $v) $journal[] = ['type'=>'in', 'label'=>'Vente #'.str_pad($v['id'],4,'0',STR_PAD_LEFT), 'mt'=>$v['total'], 'date'=>$v['created_at']];
foreach ($deps as $d)   $journal[] = ['type'=>'out','label'=>$d['libelle'].' : '.$d['description'], 'mt'=>$d['montant'], 'date'=>$d['created_at']];
usort($journal, fn($a,$b)=>strtotime($b['date'])-strtotime($a['date']));

$pageTitle = '<i class="fa-solid fa-book" style="color:var(--gold)"></i> Comptabilité';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><i class="fa-solid fa-book text-gold"></i> Comptabilité</h1><p>Journal de caisse &amp; bilan financier</p></div>
<div class="g3 mb16">
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-arrow-up"></i> Total Entrées</div><div class="stat-value text-success"><?= fmt($caTot) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-arrow-down"></i> Total Sorties</div><div class="stat-value text-danger"><?= fmt($sorties) ?></div></div>
  <div class="stat-card"><div class="stat-label"><i class="fa-solid fa-scale-balanced"></i> Bénéfice</div><div class="stat-value gold"><?= fmt($caTot - $sorties) ?></div></div>
</div>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-book-open"></i> Journal de caisse</div></div>
  <?php if($journal): foreach($journal as $r): ?>
  <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)">
    <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;background:<?= $r['type']==='in'?'var(--green2)':'var(--red2)' ?>;color:<?= $r['type']==='in'?'var(--green)':'var(--red)' ?>">
      <i class="fa-solid fa-arrow-<?= $r['type']==='in'?'up':'down' ?>"></i>
    </div>
    <div style="flex:1"><div style="font-size:13px;color:var(--txt)"><?= e($r['label']) ?></div><div class="td-muted"><?= dateStr($r['date']) ?></div></div>
    <div style="font-size:14px;font-weight:700;color:<?= $r['type']==='in'?'var(--green)':'var(--red)' ?>"><?= $r['type']==='in'?'+':'−' ?><?= fmt($r['mt']) ?></div>
  </div>
  <?php endforeach; else: ?>
  <div style="text-align:center;padding:30px;color:var(--txt2)">Aucune opération</div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
