# Vite & Gourmand — Guide de déploiement local

Application web de commande en ligne pour un traiteur bordelais, développée dans le cadre de l'ECF de la formation Développeur Web Full Stack.

**Stack technique :** PHP 8.4 · Symfony 8.1 · MySQL 9 · MongoDB 7 · Bootstrap 5 · Mailtrap

---

## Prérequis

Avant de commencer, assurez-vous d'avoir installé les outils suivants :

| Outil | Version minimale | Vérification |
|-------|-----------------|--------------|
| [WAMP] (Windows) | 3.x | PHP, Apache et MySQL intégrés |
| PHP | 8.4 | `php -v` |
| Composer | 2.x | `composer -V` |
| MongoDB Community | 7.x | `mongod --version` |
| Git | 2.x | `git --version` |

> **Extension PHP requise :** l'extension `mongodb` doit être activée dans `php.ini`.  
> Dans WAMP : clic gauche sur l'icône → PHP → Extensions PHP → cocher `php_mongodb`.

---

## 1. Récupérer le projet

```bash
git clone https://github.com/biofa3/ecf-vg.git
cd ecf-vg
```


---

## 2. Installer les dépendances PHP

```bash
composer install
```

---

## 3. Configurer les variables d'environnement

Copier le fichier `.env` en `.env.local` et renseigner vos valeurs :

```bash
cp .env .env.local
```

Ouvrir `.env.local` et modifier les lignes suivantes :

```dotenv
# Clé secrète de l'application (générer avec : php bin/console secret:generate)
APP_SECRET=votre_cle_secrete_ici

# Base de données MySQL
DATABASE_URL="mysql://root:@127.0.0.1:3306/ecf_vg?serverVersion=8.0&charset=utf8mb4"

# Base de données MongoDB
MONGODB_URI=mongodb://localhost:27017
MONGODB_DB=ecf_vg_stats

# Envoi d'emails (sandbox Mailtrap pour les tests)
MAILER_DSN="smtp://votre_user_mailtrap:votre_mdp_mailtrap@sandbox.smtp.mailtrap.io:2525"
```

> **Mailtrap (mailtrap.io) :** créer un compte gratuit sur [mailtrap.io](https://mailtrap.io), puis copier les identifiants SMTP de l'inbox sandbox.

---

## 4. Créer et importer la base de données MySQL

### Créer la base

Dans phpMyAdmin (accessible via WAMP) ou en ligne de commande :

```sql
CREATE DATABASE ecf_vg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Importer le fichier SQL

```bash
mysql -u root ecf_vg < ecf_vg.sql
```

Ou via phpMyAdmin : sélectionner la base `ecf_vg` → onglet **Importer** → choisir `ecf_vg.sql`.

> Le fichier `ecf_vg.sql` est fourni à la racine du projet. Il contient la structure complète des tables et les données initiales (menus, plats, compte administrateur José).

---

## 5. Démarrer MongoDB

Ouvrir un terminal et lancer le serveur MongoDB :

```bash
mongod --dbpath "C:\data\db"
```

> Créer le dossier `C:\data\db` s'il n'existe pas.  
> MongoDB n'a pas besoin d'import manuel : les collections sont créées automatiquement lors de la première consultation des statistiques dans l'espace admin.

---

## 6. Lancer le serveur Symfony

```bash
# Option 1 : serveur de développement intégré Symfony (recommandé)
symfony server:start

# Option 2 : via WAMP (Apache)
# L'application est accessible directement via http://localhost/ecf-vg/public/
```

---

## 7. Vider le cache

```bash
php bin/console cache:clear
```

---

## 8. Accéder à l'application

| URL | Description |
|-----|-------------|
| `http://localhost:8000` | Page d'accueil |
| `http://localhost:8000/admin` | Espace administrateur |
| `http://localhost:8000/employe` | Espace employé |
| `http://localhost:8000/utilisateur` | Espace client |

---

## Comptes disponibles

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | `jose@vite-gourmand.fr` | *(voir José Dupont dans la BDD)* |

> Les mots de passe sont hachés en base (bcrypt/argon2). Pour réinitialiser celui de José en développement :
> ```bash
> php bin/console security:hash-password
> ```

---

## Commandes utiles

```bash
# Envoyer les relances retour matériel (commandes en attente depuis +10 jours)
php bin/console app:relance-retour-materiel

# Changer le délai de relance
php bin/console app:relance-retour-materiel --jours=7

# Lister toutes les commandes disponibles
php bin/console list app
```

---

## Structure du projet

```
ecf-vg/
├── config/               Configuration Symfony (security, doctrine, mailer…)
├── public/               Point d'entrée web (index.php, assets CSS/JS)
├── src/
│   ├── Command/          Commandes console (relance email matériel)
│   ├── Controller/       Contrôleurs (Admin, Employé, Utilisateur, Commande…)
│   ├── Document/         Documents MongoDB (statistiques)
│   ├── Entity/           Entités Doctrine MySQL (Commande, Menu, Utilisateur…)
│   ├── Form/             Formulaires Symfony
│   ├── Repository/       Requêtes base de données
│   └── Security/         UserChecker (blocage comptes désactivés)
├── templates/            Vues Twig (pages + emails)
├── ecf_vg.sql            Export de la base de données MySQL
├── .env                  Variables d'environnement (modèle)
└── composer.json         Dépendances PHP
```
