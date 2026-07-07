<?php
// ============================================================
//  NightERP Pro — Fonctions utilitaires
//  Fichier : includes/fonctions.php
// ============================================================

// Formatage montant en Francs CFA
function fmt(float $n): string {
    return number_format((int)$n, 0, ',', ' ') . ' F';
}

// Sécurisation sortie HTML
function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Date/heure lisible
function dateStr(?string $iso): string {
    if (!$iso) return '—';
    $d = new DateTime($iso);
    return $d->format('d/m/Y H:i');
}

// Vérifier si l'utilisateur est connecté, sinon rediriger
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . rootUrl() . 'auth/login.php');
        exit;
    }
}

// Vérifier si l'utilisateur a accès à un module
// L'administrateur a toujours accès à tout
function requireModule(string $module): void {
    requireLogin();
    $u = $_SESSION['user'];
    if ($u['role'] === 'administrateur') return;
    $col = 'perm_' . $module;
    if (empty($u[$col])) {
        header('Location: ' . rootUrl() . 'modules/dashboard.php?erreur=acces_refuse');
        exit;
    }
}

// URL racine du projet (adapte automatiquement)
function rootUrl(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir    = trim(dirname($script), '/');

    if ($dir === '' || $dir === '.') {
        return '';
    }

    $segments = array_values(array_filter(explode('/', $dir), 'strlen'));
    $project   = trim(basename(ROOT_PATH), '/');

    if ($project !== '' && in_array($project, $segments, true)) {
        $offset = array_search($project, $segments, true);
        $depth  = count($segments) - $offset - 1;
    } else {
        $depth = count($segments);
    }

    return str_repeat('../', max(0, $depth));
}

// Chemin relatif vers la racine depuis n'importe quel fichier
function rootPath(string $from): string {
    $from = str_replace('\\', '/', realpath($from) ?: $from);
    $root = str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH);
    $rel  = preg_replace('#^' . preg_quote(rtrim($root, '/'), '#') . '/#', '', $from);
    $depth = $rel === '' ? 0 : substr_count(trim($rel, '/'), '/');
    return str_repeat('../', $depth);
}

// Recharger les données utilisateur en session depuis la BDD
function refreshSession(): void {
    if (!empty($_SESSION['user_id'])) {
        $db  = getDB();
        $st  = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $st->execute([$_SESSION['user_id']]);
        $u = $st->fetch();
        if ($u) {
            unset($u['mot_de_passe']);
            $_SESSION['user'] = $u;
        }
    }
}

function getProduitStock(PDO $db, int $produitId): int {
    $st = $db->prepare("SELECT stock_actuel FROM stock WHERE produit_id=? LIMIT 1");
    $st->execute([$produitId]);
    $row = $st->fetch();
    if ($row !== false) {
        return (int) $row['stock_actuel'];
    }
    $st = $db->prepare("SELECT COALESCE(SUM(CASE WHEN type='Entrée' THEN quantite WHEN type='Sortie' THEN -quantite WHEN type='Correction' THEN quantite ELSE 0 END),0) AS stock FROM stock_mouvements WHERE produit_id=? AND (deleted_at IS NULL OR CAST(deleted_at AS CHAR) = '' OR CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00')");
    $st->execute([$produitId]);
    return (int) $st->fetchColumn();
}

function getCommandeDetails(PDO $db, int $commandeId): array {
    $st = $db->prepare("SELECT dc.*, p.nom AS produit_nom FROM detail_commande dc LEFT JOIN produits p ON p.id = dc.produit_id WHERE dc.commande_id=? AND (dc.deleted_at IS NULL OR CAST(dc.deleted_at AS CHAR) = '' OR CAST(dc.deleted_at AS CHAR) = '0000-00-00 00:00:00')");
    $st->execute([$commandeId]);
    return $st->fetchAll();
}

function getCategoriesProduits(PDO $db): array {
    $st = $db->query("SELECT id, nom FROM categories_produits WHERE (deleted_at IS NULL OR CAST(deleted_at AS CHAR) = '' OR CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00') ORDER BY nom");
    return $st->fetchAll();
}

function getClients(PDO $db): array {
    $st = $db->query("SELECT id, pseudo, telephone FROM clients WHERE (deleted_at IS NULL OR CAST(deleted_at AS CHAR) = '' OR CAST(deleted_at AS CHAR) = '0000-00-00 00:00:00') ORDER BY pseudo");
    return $st->fetchAll();
}

// Upload image produit — retourne le nom du fichier ou null
function uploadImageProduit(array $file): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_UPLOAD_SIZE)  return null;
    if (!in_array($file['type'], ALLOWED_IMG_TYPES)) return null;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'prod_' . uniqid() . '.' . strtolower($ext);
    $dest     = UPLOAD_PATH . $filename;

    if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }
    return null;
}

// Afficher l'image d'un produit (balise img ou icône par défaut)
function imgProduit(?string $image, string $classe = 'prod-img'): string {
    if ($image && file_exists(UPLOAD_PATH . $image)) {
        $url = UPLOAD_URL . e($image);
        return "<img src=\"{$url}\" class=\"{$classe}\" alt=\"produit\">";
    }
    return '<span class="prod-emoji-default">📦</span>';
}

// Badge de statut
function badge(string $texte, string $couleur = 'blue'): string {
    return '<span class="pill pill-' . $couleur . '">' . e($texte) . '</span>';
}

// Vérifier le droit admin pur
function isAdmin(): bool {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'administrateur';
}

// CSRF token
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}
function verifyCsrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf_token'] ?? '')) {
        die('Requête invalide (CSRF).');
    }
}

// Redirection avec message flash
function redirect(string $url, string $msg = '', string $type = 'success'): void {
    if ($msg) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }
    header('Location: ' . $url);
    exit;
}

// Afficher et vider le message flash
function flash(): string {
    if (empty($_SESSION['flash'])) return '';
    $f   = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $cls = $f['type'] === 'error' ? 'alert-danger' : ($f['type'] === 'warning' ? 'alert-warning' : 'alert-success');
    $ico = $f['type'] === 'error' ? 'fa-circle-xmark' : 'fa-circle-check';
    return '<div class="alert ' . $cls . '"><i class="fa-solid ' . $ico . '"></i> ' . e($f['msg']) . '</div>';
}
