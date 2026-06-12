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

## ✅ Étape 4 : Référentiel des taxes et des contribuables — Backend (Terminé)

L'implémentation de la Phase 2 côté backend est achevée, validée par des tests d'intégration complets :
- [x] **Modélisation de la Taxe (`Tax`)** :
  - Migration et modèle `Tax` gérant `base_amount` et `stamp_amount` en centimes (entiers pour éviter les flottants).
  - Périodicité (`periodicity`) paramétrée en `one_time` (ponctuelle) par défaut pour les actes à la demande.
- [x] **Modélisation des Avis de Taxes (`TaxNotice`)** :
  - Migration et modèle `TaxNotice` liant un citoyen (`user_id`) à une taxe (`tax_id`) avec stockage des montants réels facturés (base et timbre) au moment de l'émission.
  - Gestion des statuts (`pending`, `paid`, `cancelled`) avec scopes Eloquent.
- [x] **Peuplement du Référentiel Réel (`TaxesTableSeeder`)** :
  - Intégration des **39 actes administratifs et tarifs réels** fournis par le client (de l'Attestation de cession au Certificat de célibat) avec leurs coûts de base et de timbres exacts.
- [x] **Sécurisation (Policies & Auth)** :
  - Enregistrement des policies `TaxPolicy` et `TaxNoticePolicy`.
  - Permissions restrictives : seuls les agents municipaux ou administrateurs peuvent créer/modifier les taxes et avis de taxes. Les citoyens ne peuvent voir **que leurs propres avis**.
- [x] **Contrôleurs et API Resources** :
  - Endpoints `/api/v1/taxes` et `/api/v1/tax-notices` enregistrés.
  - Implémentation de `TaxResource` et `TaxNoticeResource` pour formater les montants en centimes et retour de texte.
  - Route d'annulation dédiée : `PUT /api/v1/tax-notices/{taxNotice}/cancel`.
- [x] **Tests Feature Automatisés** :
  - `TaxTest` et `TaxNoticeTest` couvrant l'ensemble des scénarios de permissions (municipal agent vs citoyen), d'annulation, de validation de données et de calculs financiers.
  - Exécution réussie de Pint et PHPUnit : **19 tests réussis, 54 assertions**.

---

## ✅ Étape 5 : Paiement Mobile Money (SingPay) et Quittance (Terminé)

L'implémentation de la Phase 3 est terminée, tant pour la partie backend que frontend :
- [x] **Schéma de Données** :
  - Création de la table `payments` pour tracer chaque tentative (opérateur, téléphone, montant, référence, statut).
  - Création de la table `receipts` stockant le numéro séquentiel unique, le chemin du PDF et le jeton de sécurité du QR code.
  - Création de la table `receipt_counters` gérant les compteurs annuels par commune de manière atomique.
- [x] **Génération Séquentielle Sans Trou** :
  - Utilisation de `lockForUpdate()` dans une transaction SQL pour garantir une numérotation continue (format `Q-COMMUNE-ANNEE-NUMERO`) même lors de requêtes concurrentes.
- [x] **Intégration de l'API SingPay** :
  - Service `SingPayService` configuré avec le Wallet ID réel `6a21624a7ed7ed4575e73edd`.
  - Intégration des endpoints opérateurs Airtel Money (`74`) et Moov Money (`62`).
- [x] **Vérification Publique & QR Code** :
  - Intégration d'un QR code sur la quittance PDF pointant vers une route publique `/verify/receipt/{token}`.
  - Développement de la page web publique d'authenticité et d'un bouton de téléchargement direct du PDF quittance (contournant les contraintes JWT Clerk sur mobile).
- [x] **Webhook et Réconciliation** :
  - Endpoint `POST /api/webhooks/singpay` géré par `SingPayWebhookController` pour passer automatiquement les avis de taxes en `paid` dès réception du callback de SingPay.
- [x] **Intégration Mobile (Expo)** :
  - Service `taxService.ts` et hook `usePayTaxNotice` ajoutés.
  - Écran `TaxesScreen.tsx` mis à jour pour choisir l'opérateur (Airtel/Moov), saisir le numéro de téléphone, afficher l'attente du code secret (Push USSD), et présenter le bouton "Voir & Télécharger le reçu".
- [x] **Qualité et Déploiement** :
  - Tous les 26 tests backend (78 assertions) passent avec succès.
  - Le frontend compile sans aucune erreur TypeScript (`npx tsc --noEmit` OK).
  - Code poussé sur GitHub et synchronisé sur la VM de staging. Configuration du `.env` de production avec le SingPay Wallet ID complétée.

---

## 🚀 Prochaines Étapes (À faire)

- [ ] **Tests manuels bout-en-bout (Staging)** :
  - Tester les paiements fictifs via l'URL publique `https://stocks-picking-easter-band.trycloudflare.com` avec SingPay en mode sandbox/réel.
  - Valider la réception du webhook et la génération de la quittance PDF dans le dossier `storage/app/public/receipts/`.
- [ ] **Phase 4 : Tableau de bord régisseur** :
  - Concevoir l'interface d'administration pour les agents de mairie / régisseurs (visualisation des recettes, recherche de quittances, états financiers).

