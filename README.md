# NightERP Pro — Gestion Club & Bar
### Projet PHP pur + MySQL

## 📁 Structure du projet

```
ERP_GESTION_DE_CAVE/
├── index.php                      → Point d'entrée (redirige login/dashboard)
├── .htaccess                      → Protection racine
│
├── config/
│   ├── config.php                 → Configuration générale + modules + session
│   └── database.php                → Connexion PDO MySQL
│
├── auth/
│   ├── login.php                  → Page de connexion
│   └── logout.php                 → Déconnexion
│
├── includes/
│   ├── header.php                  → Sidebar + Topbar (commun à toutes les pages)
│   ├── footer.php                  → Fermeture HTML + scripts
│   ├── fonctions.php               → Fonctions utilitaires (fmt, e, upload image, permissions...)
│   └── historique.php              → Enregistrement des logs d'actions
│
├── modules/
│   ├── dashboard.php               → Tableau de bord (MODULE: principal)
│   │
│   ├── caisse/
│   │   ├── pos.php                 → Point de Vente (MODULE: principal)
│   │   └── caisse.php              → Journal des ventes (MODULE: finances)
│   │
│   ├── tables/
│   │   └── tables.php              → Tables & Zones (MODULE: principal)
│   │
│   ├── commandes/
│   │   └── commandes.php           → Suivi des commandes (MODULE: principal)
│   │
│   ├── produits/
│   │   └── produits.php            → Catalogue produits + upload image (MODULE: stock)
│   │
│   ├── stock/
│   │   └── stock.php               → Inventaire & alertes (MODULE: stock)
│   │
│   ├── achats/
│   │   └── achats.php              → Achats fournisseurs (MODULE: stock)
│   │
│   ├── fournisseurs/
│   │   └── fournisseurs.php        → Gestion fournisseurs (MODULE: stock)
│   │
│   ├── personnel/
│   │   └── personnel.php           → Gestion du personnel (MODULE: personnes)
│   │
│   ├── reservations/
│   │   └── reservations.php        → Réservations tables (MODULE: personnes)
│   │
│   ├── depenses/
│   │   └── depenses.php            → Dépenses (MODULE: finances)
│   │
│   ├── comptabilite/
│   │   └── comptabilite.php        → Journal comptable (MODULE: finances)
│   │
│   ├── rapports/
│   │   └── rapports.php            → Statistiques & performance (MODULE: finances)
│   │
│   ├── promotions/
│   │   └── promotions.php          → Happy hours (ADMIN uniquement)
│   │
│   └── admin/
│       ├── utilisateurs.php        → Gestion utilisateurs + PERMISSIONS (ADMIN uniquement)
│       └── historique.php          → Logs système (ADMIN uniquement)
│
├── assets/
│   ├── css/app.css                 → Feuille de style unique du projet
│   ├── js/app.js                   → JS global (panier POS, modals, toasts)
│   └── uploads/produits/           → Images des produits uploadées par la caissière/admin
│
└── install/
    └── nighterp.sql                → Script SQL complet (tables + données de démo)
```

## 🗂️ Les 4 modules (système de permissions)

| Module      | Pages incluses                                              |
|-------------|---------------------------------------------------------------|
| **principal**  | Tableau de bord, Point de Vente, Tables & Zones, Commandes |
| **stock**      | Produits, Stock, Achats, Fournisseurs                      |
| **personnes**  | Personnel, Réservations                                    |
| **finances**   | Caisse, Dépenses, Comptabilité, Rapports                   |

➡️ L'**administrateur** voit toujours tout (Principal, Stock & Achats, Personnes, Finances + Promotions, Utilisateurs, Historique).
➡️ Les autres comptes (gérant, caissier, serveur, magasinier, comptable) n'ont accès **qu'aux modules cochés** par l'admin dans **Utilisateurs & Rôles → Modifier permissions**.

## ⚙️ Installation

### 1. Prérequis
- PHP 8.0 ou supérieur (extension PDO MySQL activée)
- MySQL / MariaDB
- Serveur Apache (ou Nginx) avec mod_rewrite (optionnel)

### 2. Base de données
Importer le fichier SQL :
```bash
mysql -u root -p < install/nighterp.sql
```
Cela crée la base `nighterp`, toutes les tables, et insère :
- Un compte administrateur : **login: `admin`** / **mot de passe: `Admin@1234`**
- Des fournisseurs, produits, tables, personnel et promotions de démonstration.

### 3. Configuration
Modifier `config/database.php` avec vos identifiants MySQL :
```php
define('DB_HOST',     'localhost');
define('DB_NAME',     'nighterp');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
```

### 4. Droits du dossier uploads
S'assurer que le serveur web peut écrire dans :
```
assets/uploads/produits/
```
```bash
chmod -R 755 assets/uploads/produits/
```

### 5. Lancer le projet
Pointer votre serveur (Apache/Nginx) vers la racine `ERP_GESTION_DE_CAVE/`, puis ouvrir :
```
http://localhost/ERP_GESTION_DE_CAVE/
```

## 🔐 Sécurité incluse
- Mots de passe hashés (bcrypt)
- Protection CSRF sur tous les formulaires
- Requêtes préparées PDO (anti injection SQL)
- `.htaccess` bloquant l'exécution PHP dans `assets/uploads/`
- Permissions par module vérifiées côté serveur (`requireModule()`), pas seulement côté affichage

## 📸 Upload d'images produits
Dans **Produits → Nouveau produit**, la caissière/l'admin peut uploader une photo (JPEG/PNG/WEBP, max 5 Mo) à la place des anciennes icônes emoji. L'image s'affiche automatiquement dans le catalogue et au Point de Vente pour faciliter la reconnaissance rapide des boissons.

## 👥 Gestion des rôles & permissions
Dans **Utilisateurs & Rôles** (visible seulement par l'administrateur) :
1. Cliquer sur **Nouvel utilisateur**
2. Renseigner nom, login, mot de passe, rôle
3. Cocher les modules auxquels ce compte aura accès (Principal / Stock & Achats / Personnes / Finances)
4. Pour modifier plus tard : bouton crayon ✏️ sur la ligne de l'utilisateur
