<?php
// ============================================================
//  NightERP Pro — Page de connexion
//  Fichier : auth/login.php
// ============================================================
require_once __DIR__ . '/../config/config.php';

// Déjà connecté ?
if (!empty($_SESSION['user_id'])) {
    header('Location: ../modules/dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['mot_de_passe'] ?? '';

    if ($login && $pass) {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM utilisateurs WHERE login = ? AND actif = 1");
        $st->execute([$login]);
        $user = $st->fetch();

        if ($user && password_verify($pass, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            unset($user['mot_de_passe']);
            $_SESSION['user'] = $user;
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            logAction('Connexion', 'Connexion réussie', 'Auth');
            header('Location: ../modules/dashboard.php');
            exit;
        }
    }
    $erreur = 'Identifiant ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — NightERP Pro</title>
<link rel="stylesheet" href="../assets/css/app.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <div class="logo-icon" style="margin:0 auto 12px;width:60px;height:60px;font-size:26px">
        <i class="fa-solid fa-crown"></i>
      </div>
      <h1>NightERP Pro</h1>
      <p>Gestion Club &amp; Bar</p>
    </div>

    <?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> <?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-user"></i> Identifiant</label>
        <input type="text" name="login" class="form-control" placeholder="admin" required
               value="<?= e($_POST['login'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-lock"></i> Mot de passe</label>
        <input type="password" name="mot_de_passe" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
        <i class="fa-solid fa-right-to-bracket"></i> Se connecter
      </button>
    </form>

    <p style="font-size:11px;color:var(--txt3);text-align:center;margin-top:20px">
      Compte par défaut : <strong>admin</strong> / <strong>Admin@1234</strong>
    </p>
  </div>
</div>
</body>
</html>
