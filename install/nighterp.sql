-- ============================================================
--  NightERP Pro — Script SQL d'installation
--  Base de données : nighterp
-- ============================================================

CREATE DATABASE IF NOT EXISTS nighterp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nighterp;

-- -----------------------------------------------------------
-- TABLE : utilisateurs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(120) NOT NULL,
    login       VARCHAR(60)  NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role        ENUM('administrateur','gerant','caissier','serveur','magasinier','comptable') NOT NULL DEFAULT 'serveur',
    perm_principal  TINYINT(1) DEFAULT 0 COMMENT 'Tableau de bord, POS, Tables, Commandes',
    perm_stock      TINYINT(1) DEFAULT 0 COMMENT 'Produits, Stock, Achats, Fournisseurs',
    perm_personnes  TINYINT(1) DEFAULT 0 COMMENT 'Personnel, Réservations',
    perm_finances   TINYINT(1) DEFAULT 0 COMMENT 'Caisse, Dépenses, Comptabilité, Rapports',
    actif       TINYINT(1)  DEFAULT 1,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME    DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : role
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(80) NOT NULL,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME    DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : categories_produits
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories_produits (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME    DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : clients
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS clients (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pseudo      VARCHAR(100) NOT NULL,
    telephone   VARCHAR(30) DEFAULT NULL,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME    DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : fournisseurs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS fournisseurs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(150) NOT NULL,
    contact     VARCHAR(100) DEFAULT NULL,
    telephone   VARCHAR(30) DEFAULT NULL,
    solde       DECIMAL(15,2) DEFAULT 0.00,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME    DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME    DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : produits
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS produits (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(150) NOT NULL,
    image           VARCHAR(255) DEFAULT NULL COMMENT 'Nom du fichier uploadé dans assets/uploads/produits/',
    categorie_id    INT NOT NULL,
    prix_achat      DECIMAL(15,2) DEFAULT 0.00,
    prix_vente      DECIMAL(15,2) DEFAULT 0.00,
    code_barre      VARCHAR(60) DEFAULT NULL,
    statut          VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,
    FOREIGN KEY (categorie_id) REFERENCES categories_produits(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : stock
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    stock_actuel  INT DEFAULT 0,
    seuil_alert   INT DEFAULT 0,
    produit_id    INT NOT NULL UNIQUE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : tables_club
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS tables_club (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(60) NOT NULL,
    zone        ENUM('VIP','Terrasse','Salle principale','Lounge','Bar') NOT NULL DEFAULT 'Salle principale',
    capacite    INT DEFAULT 4,
    statut      VARCHAR(50) DEFAULT 'Libre',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : personnel
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS personnel (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(120) NOT NULL,
    role        VARCHAR(80) DEFAULT 'Serveur',
    telephone   VARCHAR(30) DEFAULT NULL,
    salaire     DECIMAL(15,2) DEFAULT 0.00,
    nb_ventes   INT DEFAULT 0,
    ca_genere   DECIMAL(15,2) DEFAULT 0.00,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : commandes
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS commandes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    table_id        INT DEFAULT NULL,
    client_id       INT DEFAULT NULL,
    numero          VARCHAR(60) DEFAULT NULL,
    promotion_id    INT DEFAULT NULL,
    sous_total      DECIMAL(15,2) DEFAULT 0.00,
    total           DECIMAL(15,2) DEFAULT 0.00,
    mode_paiement   VARCHAR(50) DEFAULT 'Espèces',
    statut          VARCHAR(50) DEFAULT 'En attente',
    utilisateur_id  INT DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,
    FOREIGN KEY (table_id)       REFERENCES tables_club(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id)      REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : detail_commande
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS detail_commande (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    commande_id  INT NOT NULL,
    produit_id   INT NOT NULL,
    prix         DECIMAL(15,2) DEFAULT 0.00,
    qte          INT DEFAULT 1,
    remise       DECIMAL(15,2) DEFAULT 0.00,
    sous_total   DECIMAL(15,2) DEFAULT 0.00,
    statut       VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   DATETIME DEFAULT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id)  REFERENCES produits(id)   ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : achats
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS achats (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id  INT DEFAULT NULL,
    produit_id      INT DEFAULT NULL,
    quantite        INT DEFAULT 1,
    montant         DECIMAL(15,2) DEFAULT 0.00,
    date_reception  DATE,
    statut          VARCHAR(50) NOT NULL DEFAULT 'reçu',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL,
    FOREIGN KEY (produit_id)     REFERENCES produits(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : depenses
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS depenses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(150) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    montant     DECIMAL(15,2) DEFAULT 0.00,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : stock_mouvements
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS stock_mouvements (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    produit_id  INT NOT NULL,
    type        ENUM('Entrée','Sortie','Correction') DEFAULT 'Entrée',
    quantite    INT DEFAULT 0,
    motif       VARCHAR(200) DEFAULT NULL,
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME DEFAULT NULL,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : promotions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS promotions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(120) NOT NULL,
    type_valeur ENUM('pourcentage','montant') NOT NULL DEFAULT 'pourcentage',
    type_promo  ENUM('commande','produit') NOT NULL DEFAULT 'commande',
    valeur      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    produit_id  INT DEFAULT NULL,
    date_debut  DATE DEFAULT NULL,
    date_fin    DATE DEFAULT NULL,
    actif       TINYINT(1) DEFAULT 1,
    user_id     INT DEFAULT NULL COMMENT 'Utilisateur qui a créé la promo',
    statut      VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME DEFAULT NULL,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id)    REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE commandes
    ADD CONSTRAINT fk_commandes_promotion FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL;

-- -----------------------------------------------------------
-- TABLE : reservations
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT DEFAULT NULL,
    user_id         INT DEFAULT NULL COMMENT 'Utilisateur qui a créé la réservation',
    table_id        INT DEFAULT NULL,
    date_resa       DATE DEFAULT NULL,
    heure           TIME DEFAULT NULL,
    nb_personnes    INT DEFAULT 2,
    acompte         DECIMAL(15,2) DEFAULT 0.00,
    statut          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id)   REFERENCES utilisateurs(id) ON DELETE SET NULL,
    FOREIGN KEY (table_id)  REFERENCES tables_club(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : reservation
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservation_table (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    table_id        INT NOT NULL,
    reservation_id  INT NOT NULL,
    nbre_personne   INT DEFAULT 1,
    statut          VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,
    FOREIGN KEY (table_id)       REFERENCES tables_club(id) ON DELETE CASCADE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : fournisseur_produit
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS fournisseur_produit (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    produit_id     INT NOT NULL,
    montant        DECIMAL(15,2) DEFAULT 0.00,
    statut         VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME DEFAULT NULL,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id)     REFERENCES produits(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TABLE : historique
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS historique (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT DEFAULT NULL,
    utilisateur_nom VARCHAR(120) DEFAULT NULL,
    action          VARCHAR(200) DEFAULT NULL,
    detail          TEXT DEFAULT NULL,
    module          VARCHAR(60) DEFAULT NULL,
    statut          VARCHAR(50) NOT NULL DEFAULT 'actif',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- DONNÉES INITIALES
-- -----------------------------------------------------------

INSERT INTO role (nom) VALUES
('Administrateur'),('Gérant'),('Caissier'),('Serveur'),('Magasinier'),('Comptable');

INSERT INTO categories_produits (nom) VALUES
('Whisky'),('Champagne'),('Bière'),('Vin'),('Cocktail'),('Chicha'),('Jus'),('Eau'),('Snack'),('Autre');

INSERT INTO personnel (nom, role, telephone, salaire) VALUES
('Awa Sow', 'Serveur', '+225 07 11 22 33', 180000),
('Kofi Diallo', 'Barman', '+225 05 22 33 44', 200000),
('Mariam Yao', 'Caissier', '+225 01 44 55 66', 160000);

INSERT INTO clients (pseudo, telephone) VALUES
('Cyrille', '+225 07 77 88 99'),
('Sita', '+225 05 44 11 22'),
('Mamadou', '+225 01 22 33 44');

INSERT INTO utilisateurs (nom, login, mot_de_passe, role, perm_principal, perm_stock, perm_personnes, perm_finances, actif)
VALUES ('Admin Principal', 'admin', '$2y$12$qGXZ0hNkqJf/SXYD./KD1e4q0e9XzOqm3x8LwZvs7yoUZkC5vq2Ei', 'administrateur', 1, 1, 1, 1, 1);

INSERT INTO fournisseurs (nom, contact, telephone, solde) VALUES
('Société DIAGEO CI',   'Kouadio Marc',  '+225 07 11 22 33', 0.00),
('SOBRACI',             'Amara Traoré',  '+225 05 44 55 66', 0.00),
('SOLIBRA Distribution','Fatou Diallo',  '+225 01 77 88 99', 0.00);

INSERT INTO produits (nom, categorie_id, prix_achat, prix_vente, code_barre) VALUES
("Jack Daniel's Black", 1, 22000, 45000, '1234567890123'),
('Hennessy VS',           1, 35000, 70000, '1234567890124'),
('Johnnie Walker Black',  1, 28000, 55000, '1234567890125'),
('Moët & Chandon Brut',   2, 55000,110000, '1234567890126'),
('Cristal Rosé',          2,120000,200000, '1234567890127'),
('Heineken (btl)',        3, 1000,  3000, '1234567890128'),
('Flag Export',           3,  800,  2500, '1234567890129'),
('Cocktail Maison',       5, 2500,  5000, '1234567890130'),
('Gin Tonic',             5, 2000,  4500, '1234567890131'),
('Jus d\'orange frais',  7,  500,  2000, '1234567890132'),
('Eau minérale',          8,  300,  1000, '1234567890133'),
('Brochettes poulet',     9, 2000,  5000, '1234567890134'),
('Chicha Pomme',          6, 3000,  8000, '1234567890135');

INSERT INTO tables_club (nom, zone, capacite) VALUES
('VIP-01','VIP',8), ('VIP-02','VIP',8), ('VIP-03','VIP',6),
('T1','Terrasse',4), ('T2','Terrasse',4), ('T3','Terrasse',6),
('01','Salle principale',4), ('02','Salle principale',4),
('03','Salle principale',4), ('04','Salle principale',6),
('Bar-01','Bar',2), ('Lounge-01','Lounge',8);

INSERT INTO promotions (libelle, type_valeur, type_promo, valeur, produit_id, date_debut, date_fin, actif, user_id) VALUES
('Happy Hour', 'pourcentage', 'produit', 20.00, 8, '2026-01-01','2026-12-31',1,1),
('Promo Bières', 'pourcentage', 'produit', 15.00, 6, '2026-01-01','2026-12-31',1,1),
('Remise Commande', 'montant', 'commande', 500.00, NULL, '2026-01-01','2026-12-31',1,1);

INSERT INTO fournisseur_produit (fournisseur_id, produit_id, montant) VALUES
(1,1,42000),(1,2,68000),(2,4,105000),(3,6,2800),(3,7,2200);

INSERT INTO stock (libelle, stock_actuel, seuil_alert, produit_id) VALUES
("Jack Daniel's Black",20,5,1),('Hennessy VS',15,5,2),('Johnnie Walker Black',18,5,3),('Moët & Chandon Brut',8,3,4),('Cristal Rosé',4,3,5),('Heineken (btl)',200,30,6),('Flag Export',150,30,7),('Cocktail Maison',999,100,8),('Gin Tonic',999,100,9),('Jus d''orange frais',50,20,10),('Eau minérale',100,20,11),('Brochettes poulet',30,10,12),('Chicha Pomme',20,10,13);

INSERT INTO stock_mouvements (produit_id, type, quantite, motif, statut) VALUES
(1,'Entrée',20,'Stock initial','actif'),(2,'Entrée',15,'Stock initial','actif'),(3,'Entrée',18,'Stock initial','actif'),
(4,'Entrée',8,'Stock initial','actif'),(5,'Entrée',4,'Stock initial','actif'),(6,'Entrée',200,'Stock initial','actif'),
(7,'Entrée',150,'Stock initial','actif'),(8,'Entrée',999,'Stock initial','actif'),(9,'Entrée',999,'Stock initial','actif'),
(10,'Entrée',50,'Stock initial','actif'),(11,'Entrée',100,'Stock initial','actif'),(12,'Entrée',30,'Stock initial','actif'),(13,'Entrée',20,'Stock initial','actif');

INSERT INTO achats (fournisseur_id,produit_id,quantite,montant,date_reception,statut) VALUES
(1,1,10,420000,'2026-06-01','Reçu'),
(3,6,60,168000,'2026-06-05','Reçu');

INSERT INTO depenses (libelle, description, montant) VALUES
('Électricité', 'Facture EDF', 150000),
('Eau', 'Eau de la salle', 50000),
('Salaires', 'Paiement du personnel', 600000);

INSERT INTO reservations (client_id,user_id,table_id,date_resa,heure,nb_personnes,acompte,statut) VALUES
(1,1,1,'2026-07-15','20:00:00',4,10000,1),
(2,1,7,'2026-07-16','22:00:00',2,5000,1);

INSERT INTO reservation (table_id,reservation_id,nbre_personne) VALUES
(1,1,4),(7,2,2);

INSERT INTO commandes (table_id,client_id,numero,promotion_id,sous_total,total,mode_paiement,statut,utilisateur_id) VALUES
(1,1,'CMD-0001',1,45000,36000,'Carte bancaire','Payée',1),
(2,2,'CMD-0002',NULL,15000,15000,'Espèces','Payée',1);

INSERT INTO detail_commande (commande_id,produit_id,prix,qte,remise,sous_total) VALUES
(1,8,5000,2,0,10000),(1,9,4500,4,0,18000),(1,6,3000,6,0,18000),(2,6,3000,2,0,6000),(2,7,2500,2,0,5000);
