# Suivi du Projet ULKY — Claude & Agents

Ce fichier sert de source de vérité pour tout agent (Claude Code, Antigravity, etc.) reprenant le développement de l'application **ULKY**. Il détaille l'architecture, l'historique des modifications apportées et les étapes suivantes.

---

## 📌 Architecture & Stack Technique

- **Monorepo** : La racine du projet Git est `APP/`
  - **Backend** : `/backend` (Laravel 12 · PHP 8.5 · PostgreSQL Neon · Clerk SDK)
  - **Frontend** : `/ulky_new` (Expo SDK · React Native · NativeWind · Clerk Expo)
- **Base de Données** : PostgreSQL hébergé sur **Neon** (avec séparation Pooler pour l'application et Direct pour les migrations)
- **Authentification** : Gérée exclusivement par **Clerk** (pas de mot de passe en local, synchronisation par webhooks Svix)
- **Infrastructure VM** : VM Ubuntu 26.04 LTS (accessible via Tailscale IP `100.68.232.112` ou port local `2222`)

---

## ✅ Étape 1 : Identité, Rôles et Webhooks (Terminé)

Toutes les tâches de la Phase 1 ont été implémentées, testées et validées en local :
- [x] **Dépendances Backend** : Installation de `spatie/laravel-permission` (v6.25) et `svix/svix` (v1.95).
- [x] **Schéma Utilisateur** : Migration `users` mise à jour (retrait des champs `password` et `remember_token`, ajout de `clerk_id` unique, `phone`, `taxpayer_type` enum, et `commune_id`).
- [x] **Rôles ULKY** : Création de `RolesSeeder` pour initialiser les 5 rôles (`citizen`, `municipal_agent`, `cashier`, `commune_admin`, `super_admin`).
- [x] **Authentification Clerk** : Implémentation du middleware `ClerkAuthenticate` et de l'alias `clerk.auth` dans `bootstrap/app.php`.
- [x] **Webhooks Clerk** : Création du `ClerkWebhookController` gérant les événements `user.created`, `user.updated` et `user.deleted` de manière idempotente avec vérification de signature Svix.
- [x] **Nettoyage Auth** : Suppression de Sanctum, Laravel Socialite et des routes d'authentification obsolètes (seul `AuthController@me` et `logout` restent).
- [x] **Tests & Qualité** :
  - `MeTest` (vérification du token JWT Clerk simulé) et `ClerkWebhookTest` (5 tests d'intégration webhooks) créés et validés.
  - Exécution de Laravel Pint (`vendor/bin/pint`) et des tests unitaires (`php artisan test`) : **100% OK**.

---

## ✅ Étape 2 : Configuration Monorepo & Déploiement CI/CD (Terminé)

La transition vers le monorepo et la configuration de la VM de staging sont prêtes :
- [x] **Restructuration Git** : Le dossier `.git` a été déplacé à la racine `APP/`.
- [x] **Fichiers de Configuration Git** :
  - Création de `.gitignore` racine configuré pour exclure les `.env` et dépendances.
  - Création de `.gitattributes` forçant le format de fin de ligne `LF` pour la compatibilité Linux/CI.
- [x] **GitHub Workflows** :
  - `ci.yml` : Lance automatiquement les tests automatisés (Backend PHPUnit/Pint via SQLite en mémoire, Frontend linter/compiler) à chaque push/PR.
  - `deploy.yml` : Workflow de déploiement automatique sur la VM en rejoignant le réseau privé Tailscale.
- [x] **Environnement CI** : Création de `backend/.env.ci` utilisant SQLite en mémoire pour exécuter les tests dans GitHub Actions sans dépendre de Neon.
- [x] **Push initial** : Code poussé avec succès sur le dépôt privé `github.com/kurt08ulysse/ulky.git` (branche `main`).

---

## ✅ Étape 3 : Configuration de la VM de Staging (Terminé)

La VM Ubuntu 26.04 a été configurée avec succès pour faire tourner le backend :
- [x] **Réseau Privé Tailscale** :
  - Installation de Tailscale sur la VM et connexion au réseau.
  - IP Tailscale de la VM : **`100.68.232.112`**
- [x] **Clé SSH Dédiée pour GitHub Actions** :
  - Génération d'une clé SSH Ed25519 dédiée au déploiement automatique sur la VM.
  - Clé publique ajoutée dans `/home/bradley/.ssh/authorized_keys`.
- [x] **Stack Web sur la VM** :
  - Installation de **PHP 8.5** (version par défaut d'Ubuntu 26.04) et de ses extensions requises (`pgsql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`, `redis`, `sqlite3`).
  - Installation de **Composer** et de **Nginx**.
- [x] **Préparation du Projet** :
  - Création du dossier `/var/www/ulky/` avec permissions accordées à `bradley:www-data`.
  - Clone initial du repo via SSH GitHub.
  - Copie du fichier `.env` configuré pour PostgreSQL Neon.
  - Création des dossiers de cache/stockage Laravel et application des droits d'écriture.
- [x] **Installation & Base de Données** :
  - Exécution de `composer install --no-dev` réussie.
  - Exécution des migrations de base de données (`php artisan migrate`) sur la base Neon.
  - Exécution du seeder de rôles (`php artisan db:seed`) sur la base Neon.
- [x] **Serveur Web Nginx** :
  - Configuration du virtual host dans `/etc/nginx/sites-available/ulky` pointant vers `/var/www/ulky/backend/public` et utilisant le socket PHP 8.5-FPM.
  - Activation du site et désactivation du site par défaut (`default`).
- [x] **Exposition Publique (Cloudflare Tunnel)** :
  - Installation de `cloudflared` sur la VM.
  - Lancement d'un Tunnel Quick Cloudflare temporaire redirigeant vers l'application locale.
  - URL publique active : **[https://stocks-picking-easter-band.trycloudflare.com](https://stocks-picking-easter-band.trycloudflare.com)**

---

## 🔑 Clé SSH Privée pour GitHub Actions (Déploiement)

Pour que le workflow `deploy.yml` fonctionne, tu dois ajouter cette clé privée dans les secrets de ton dépôt GitHub (sous le nom `VM_SSH_PRIVATE_KEY`) :

```text
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACDgvxXWwJ0n6wjH8VNNtYxC4J+oQe10pFHOhp/9XoDl9QAAAJhD3FmsQ9xZ
rAAAAAtzc2gtZWQyNTUxOQAAACDgvxXWwJ0n6wjH8VNNtYxC4J+oQe10pFHOhp/9XoDl9Q
AAAEAupUBqMP8RL95glFZuSVTo6T22R0y8lkZI4S/PEQvZbuC/FdbAnSfrCMfxU021jELg
n6hB7XSkUc6Gn/1egOX1AAAAEWJyYWRsZXlAdmVsbm94ZXJwAQIDBA==
-----END OPENSSH PRIVATE KEY-----
```

---

## 🚀 Prochaines Étapes (À faire)

- [ ] **Secrets GitHub** : Ajouter les variables suivantes dans les secrets du repository GitHub (`Settings -> Secrets and variables -> Actions`) :
  - `VM_SSH_PRIVATE_KEY` : *(Coller le bloc de clé privée OpenSSH ci-dessus)*
  - `VM_TAILSCALE_IP` : `100.68.232.112`
  - `VM_SSH_USER` : `bradley`
  - `TAILSCALE_OAUTH_CLIENT_ID` : *(Depuis la console Tailscale, générer des credentials OAuth avec tag `tag:ci`)*
  - `TAILSCALE_OAUTH_CLIENT_SECRET` : *(Secret généré par Tailscale)*
- [ ] **Tunnel permanent** : Configurer un Cloudflare Tunnel permanent si tu as un nom de domaine (pour remplacer l'URL temporaire `*.trycloudflare.com`).
- [ ] **Déploiement Frontend** : Configurer et déployer l'application mobile Expo (/ulky_new).
- [ ] **Clerk Webhooks en Prod** : Configurer le endpoint de webhook de production (`/api/webhooks/clerk`) sur le dashboard de Clerk.
