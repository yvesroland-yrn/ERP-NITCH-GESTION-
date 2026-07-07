<?php
// ============================================================
//  NightERP Pro — Utilisateurs & Permissions
//  Fichier : modules/admin/utilisateurs.php
//  Accès   : Administrateur uniquement
// ============================================================
require_once __DIR__ . '/../../config/config.php';
requireLogin();
if (!isAdmin()) redirect('../../modules/dashboard.php', 'Accès refusé.', 'error');

$db   = getDB();
$root = rootPath(__FILE__);

// --- ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // CRÉER UTILISATEUR
    if ($action === 'creer') {
        $nom   = trim($_POST['nom']   ?? '');
        $login = trim($_POST['login'] ?? '');
        $role  = $_POST['role']       ?? 'serveur';
        $pass  = $_POST['mot_de_passe'] ?? '';

        if (!$nom || !$login || !$pass) redirect($_SERVER['PHP_SELF'], 'Tous les champs sont requis.', 'error');

        // Vérifier login unique
        $check = $db->prepare("SELECT id FROM utilisateurs WHERE login=?");
        $check->execute([$login]);
        if ($check->fetch()) redirect($_SERVER['PHP_SELF'], "L'identifiant « $login » est déjà utilisé.", 'error');

        $hash = password_hash($pass, PASSWORD_BCRYPT);

        // L'admin peut choisir les modules accessibles
        $pPrincipal = isset($_POST['perm_principal']) ? 1 : 0;
        $pStock     = isset($_POST['perm_stock'])     ? 1 : 0;
        $pPersonnes = isset($_POST['perm_personnes']) ? 1 : 0;
        $pFinances  = isset($_POST['perm_finances'])  ? 1 : 0;

        $st = $db->prepare("INSERT INTO utilisateurs (nom,login,mot_de_passe,role,perm_principal,perm_stock,perm_personnes,perm_finances) VALUES (?,?,?,?,?,?,?,?)");
        $st->execute([$nom,$login,$hash,$role,$pPrincipal,$pStock,$pPersonnes,$pFinances]);
        logAction('Utilisateur créé', "$nom ($role)", 'Admin');
        redirect($_SERVER['PHP_SELF'], "Utilisateur « $nom » créé avec succès.");
    }

    // MODIFIER PERMISSIONS
    if ($action === 'permissions') {
        $id = intval($_POST['user_id'] ?? 0);
        // Ne pas modifier l'admin principal
        $target = $db->prepare("SELECT role FROM utilisateurs WHERE id=?"); $target->execute([$id]);
        $t = $target->fetch();
        if ($t && $t['role'] === 'administrateur') redirect($_SERVER['PHP_SELF'], 'Les permissions de l\'administrateur ne peuvent pas être modifiées.', 'error');

        $role       = $_POST['role']             ?? 'serveur';
        $pPrincipal = isset($_POST['perm_principal']) ? 1 : 0;
        $pStock     = isset($_POST['perm_stock'])     ? 1 : 0;
        $pPersonnes = isset($_POST['perm_personnes']) ? 1 : 0;
        $pFinances  = isset($_POST['perm_finances'])  ? 1 : 0;

        $st = $db->prepare("UPDATE utilisateurs SET role=?,perm_principal=?,perm_stock=?,perm_personnes=?,perm_finances=? WHERE id=?");
        $st->execute([$role,$pPrincipal,$pStock,$pPersonnes,$pFinances,$id]);
        logAction('Permissions modifiées', "Utilisateur #$id", 'Admin');
        redirect($_SERVER['PHP_SELF'], 'Permissions mises à jour.');
    }

    // ACTIVER / DÉSACTIVER
    if ($action === 'toggle') {
        $id   = intval($_POST['user_id'] ?? 0);
        $actif = intval($_POST['actif'] ?? 0);
        if ($id != $_SESSION['user_id']) { // ne pas se désactiver soi-même
            $db->prepare("UPDATE utilisateurs SET actif=? WHERE id=?")->execute([$actif ? 0 : 1, $id]);
            logAction('Compte ' . ($actif ? 'désactivé' : 'activé'), "Utilisateur #$id", 'Admin');
            redirect($_SERVER['PHP_SELF'], 'Statut mis à jour.', 'info');
        }
    }

    // SUPPRIMER
    if ($action === 'supprimer') {
        $id = intval($_POST['user_id'] ?? 0);
        if ($id != $_SESSION['user_id']) {
            $u = $db->prepare("SELECT nom FROM utilisateurs WHERE id=?"); $u->execute([$id]); $ur = $u->fetch();
            $db->prepare("DELETE FROM utilisateurs WHERE id=?")->execute([$id]);
            logAction('Utilisateur supprimé', $ur['nom'] ?? '#'.$id, 'Admin');
            redirect($_SERVER['PHP_SELF'], 'Utilisateur supprimé.', 'info');
        }
    }
}

// --- Données ---
$utilisateurs = $db->query("SELECT * FROM utilisateurs ORDER BY role,nom")->fetchAll();

$roles = [
    'administrateur' => ['label'=>'Administrateur', 'pill'=>'pill-red',    'icon'=>'fa-crown'],
    'gerant'         => ['label'=>'Gérant',          'pill'=>'pill-gold',   'icon'=>'fa-user-tie'],
    'caissier'       => ['label'=>'Caissier',        'pill'=>'pill-green',  'icon'=>'fa-cash-register'],
    'serveur'        => ['label'=>'Serveur',         'pill'=>'pill-blue',   'icon'=>'fa-utensils'],
    'magasinier'     => ['label'=>'Magasinier',      'pill'=>'pill-purple', 'icon'=>'fa-warehouse'],
    'comptable'      => ['label'=>'Comptable',       'pill'=>'pill-orange', 'icon'=>'fa-book'],
];

$modules = [
    'perm_principal' => ['label'=>'Principal',     'desc'=>'Tableau de bord, POS, Tables & Zones, Commandes', 'icon'=>'fa-gauge-high'],
    'perm_stock'     => ['label'=>'Stock & Achats','desc'=>'Produits, Stock, Achats, Fournisseurs',           'icon'=>'fa-warehouse'],
    'perm_personnes' => ['label'=>'Personnes',     'desc'=>'Personnel, Réservations',                         'icon'=>'fa-users'],
    'perm_finances'  => ['label'=>'Finances',      'desc'=>'Caisse, Dépenses, Comptabilité, Rapports',        'icon'=>'fa-sack-dollar'],
];

$pageTitle = '<i class="fa-solid fa-users-gear" style="color:var(--gold)"></i> Utilisateurs & Rôles';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header flex items-center justify-between">
  <div>
    <h1><i class="fa-solid fa-users-gear text-gold"></i> Utilisateurs &amp; Rôles</h1>
    <p>Gestion des accès et permissions par module</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modalAddUser')">
    <i class="fa-solid fa-plus"></i> Nouvel utilisateur
  </button>
</div>

<!-- Grille utilisateurs -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-users"></i> Comptes actifs</div>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead>
        <tr>
          <th>Nom</th><th>Login</th><th>Rôle</th>
          <th>Principal</th><th>Stock & Achats</th><th>Personnes</th><th>Finances</th>
          <th>Statut</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($utilisateurs as $u): ?>
        <?php $r = $roles[$u['role']] ?? ['label'=>$u['role'],'pill'=>'pill-blue','icon'=>'fa-user']; ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="user-av" style="width:32px;height:32px;font-size:11px"><?= strtoupper(substr($u['nom'],0,2)) ?></div>
              <strong><?= e($u['nom']) ?></strong>
            </div>
          </td>
          <td class="td-muted"><i class="fa-solid fa-at" style="margin-right:4px"></i><?= e($u['login']) ?></td>
          <td><span class="pill <?= $r['pill'] ?>" style="font-size:10px"><i class="fa-solid <?= $r['icon'] ?>"></i> <?= $r['label'] ?></span></td>

          <?php foreach(['perm_principal','perm_stock','perm_personnes','perm_finances'] as $perm): ?>
          <td style="text-align:center">
            <?php if ($u['role'] === 'administrateur'): ?>
              <i class="fa-solid fa-check" style="color:var(--gold)" title="Accès total admin"></i>
            <?php else: ?>
              <i class="fa-solid <?= $u[$perm] ? 'fa-check-circle' : 'fa-xmark-circle' ?>"
                 style="color:<?= $u[$perm] ? 'var(--green)' : 'var(--txt3)' ?>;font-size:16px"></i>
            <?php endif; ?>
          </td>
          <?php endforeach; ?>

          <td>
            <span class="pill <?= $u['actif'] ? 'pill-green' : 'pill-red' ?>">
              <?= $u['actif'] ? 'Actif' : 'Inactif' ?>
            </span>
          </td>
          <td style="white-space:nowrap">
            <?php if ($u['role'] !== 'administrateur' || $u['id'] != $_SESSION['user_id']): ?>

            <!-- Bouton modifier permissions -->
            <button class="btn btn-icon btn-sm" title="Modifier permissions"
                    onclick="openEditModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
              <i class="fa-solid fa-pen" style="font-size:11px"></i>
            </button>

            <!-- Activer/Désactiver -->
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <form method="POST" style="display:inline">
              <?= csrfField() ?>
              <input type="hidden" name="action"  value="toggle">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <input type="hidden" name="actif"   value="<?= $u['actif'] ?>">
              <button type="submit" class="btn btn-icon btn-sm" title="<?= $u['actif'] ? 'Désactiver' : 'Activer' ?>">
                <i class="fa-solid <?= $u['actif'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"
                   style="font-size:14px;color:<?= $u['actif'] ? 'var(--green)' : 'var(--txt3)' ?>"></i>
              </button>
            </form>

            <!-- Supprimer -->
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
              <?= csrfField() ?>
              <input type="hidden" name="action"  value="supprimer">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-icon btn-sm" title="Supprimer">
                <i class="fa-solid fa-trash" style="font-size:11px"></i>
              </button>
            </form>
            <?php endif; ?>

            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Explication des niveaux d'accès -->
<div class="card" style="margin-top:16px">
  <div class="card-title" style="margin-bottom:16px"><i class="fa-solid fa-shield-halved text-gold"></i> Modules &amp; Accès</div>
  <table class="perm-table">
    <thead><tr><th>Module</th><th>Pages incluses</th></tr></thead>
    <tbody>
      <?php foreach($modules as $key => $m): ?>
      <tr>
        <td><div class="perm-module"><i class="fa-solid <?= $m['icon'] ?> text-gold"></i> <?= $m['label'] ?></div></td>
        <td><div class="perm-desc"><?= $m['desc'] ?></div></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ===== MODAL CRÉER UTILISATEUR ===== -->
<div class="modal-overlay hidden" id="modalAddUser">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-user-plus text-gold"></i> Nouvel utilisateur</h3>
      <button class="modal-close" onclick="closeModal('modalAddUser')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="creer">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nom complet *</label>
            <input class="form-control" name="nom" required placeholder="Ex: Awa Koné">
          </div>
          <div class="form-group">
            <label class="form-label">Identifiant (login) *</label>
            <input class="form-control" name="login" required placeholder="ex: awa.kone">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Rôle</label>
            <select class="form-control" name="role">
              <?php foreach($roles as $key => $r): ?>
              <?php if($key !== 'administrateur'): ?>
              <option value="<?= $key ?>"><?= $r['label'] ?></option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Mot de passe *</label>
            <input type="password" class="form-control" name="mot_de_passe" required minlength="6">
          </div>
        </div>

        <hr class="divider">
        <div class="card-title" style="margin-bottom:12px"><i class="fa-solid fa-shield-halved text-gold"></i> Modules accessibles</div>

        <?php foreach($modules as $key => $m): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--txt)">
              <i class="fa-solid <?= $m['icon'] ?>" style="color:var(--gold);margin-right:8px"></i><?= $m['label'] ?>
            </div>
            <div class="perm-desc"><?= $m['desc'] ?></div>
          </div>
          <label class="switch">
            <input type="checkbox" name="<?= $key ?>" value="1">
            <span class="slider"></span>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalAddUser')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Créer</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== MODAL MODIFIER PERMISSIONS ===== -->
<div class="modal-overlay hidden" id="modalEditUser">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fa-solid fa-pen text-gold"></i> Modifier les permissions — <span id="edit-nom"></span></h3>
      <button class="modal-close" onclick="closeModal('modalEditUser')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <input type="hidden" name="action"  value="permissions">
      <input type="hidden" name="user_id" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Rôle</label>
          <select class="form-control" name="role" id="edit-role">
            <?php foreach($roles as $key => $r): ?>
            <?php if($key !== 'administrateur'): ?>
            <option value="<?= $key ?>"><?= $r['label'] ?></option>
            <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <hr class="divider">
        <div class="card-title" style="margin-bottom:12px"><i class="fa-solid fa-shield-halved text-gold"></i> Modules accessibles</div>
        <?php foreach($modules as $key => $m): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04)">
          <div>
            <div style="font-size:13px;font-weight:600;color:var(--txt)">
              <i class="fa-solid <?= $m['icon'] ?>" style="color:var(--gold);margin-right:8px"></i><?= $m['label'] ?>
            </div>
            <div class="perm-desc"><?= $m['desc'] ?></div>
          </div>
          <label class="switch">
            <input type="checkbox" name="<?= $key ?>" value="1" id="edit-<?= $key ?>">
            <span class="slider"></span>
          </label>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditUser')">Annuler</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(u) {
    document.getElementById('edit-id').value  = u.id;
    document.getElementById('edit-nom').textContent = u.nom;
    document.getElementById('edit-role').value = u.role;
    ['perm_principal','perm_stock','perm_personnes','perm_finances'].forEach(p => {
        const el = document.getElementById('edit-' + p);
        if (el) el.checked = u[p] == 1;
    });
    openModal('modalEditUser');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
