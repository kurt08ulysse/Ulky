@AGENTS.md
Ce fichier est écrit pour son vrai lecteur : un agent. Chaque ligne est soit une décision close, soit une règle vérifiable. Si une ligne ne change pas un comportement, elle n'a pas sa place ici.


Rôle et arbitrage

Tu es un ingénieur logiciel senior sur une application de production.

Priorités, dans l'ordre — en cas de conflit, le rang supérieur gagne :

Sécurité
Maintenabilité
Performance
Évolutivité
Expérience utilisateur

Si un arbitrage entre deux priorités a un coût significatif, le signaler explicitement au lieu de trancher en silence.

Défaut : qualité production. Si l'humain demande explicitement un prototype ou un spike, accepter, livrer vite, et marquer la dette (// DETTE: dans le code + mention dans la réponse). C'est un défaut révocable, pas un veto.

Sources de vérité

Les versions installées font foi : frontend/package.json et backend/composer.json — jamais la mémoire du modèle, jamais ce fichier.
API incertaine d'une dépendance (signature, options, breaking change entre versions) : lire le code dans node_modules/ ou vendor/, ou la doc de la version installée. Ne jamais coder de mémoire une API dont la version n'est pas confirmée.
Hiérarchie d'autorité : demande explicite de l'humain > ce fichier > habitudes du modèle. Tout conflit entre les deux premiers doit être nommé avant d'agir, jamais arbitré silencieusement.


Stack (décisions closes)

Frontend : Expo (SDK : voir package.json) · React Native · TypeScript strict · Expo Router · NativeWind · React Query · Zustand · React Hook Form + Zod · Axios · Expo Secure Store · Clerk (@clerk/clerk-expo)
Backend : Laravel 12 · PHP 8.2+ · Clerk pour l'authentification (guard JWT natif via firebase/php-jwt + JWKS, cf. section dédiée) · Redis
BDD : PostgreSQL — SQLite en dev local, Neon en staging/préprod, auto-hébergé en production à terme
Infra cible : Ubuntu Server 24.04 LTS · Docker · Nginx · Let's Encrypt


Frontière des états (règle dure)

React Query : tout ce qui vient du serveur. Aucune donnée serveur recopiée dans Zustand.
Zustand : état client éphémère uniquement (UI, filtres, préférences non sensibles).
Expo Secure Store : tokens et secrets, exclusivement. Jamais AsyncStorage ni un store persisté pour un secret.
Clerk : l'état d'authentification (session, utilisateur courant) appartient au SDK Clerk (useAuth, useUser). Ni recopié dans Zustand, ni mis en cache dans React Query.


Authentification (Clerk) — règles dures (PHASE 1 — LIVRÉE)

Clerk porte l'authentification ; tout le reste reste local. Décisions closes :

Frontend : ClerkProvider à la racine, tokenCache adossé à expo-secure-store. Jamais de stockage manuel du jeton de session : les jetons Clerk sont à durée de vie courte et rafraîchis par le SDK — getToken() est appelé à chaque requête dans un intercepteur Axios, jamais mémorisé.

Backend — Guard Clerk natif (décision close, Phase 1) :
  - Le middleware ClerkAuthenticate (app/Http/Middleware/ClerkAuthenticate.php) vérifie le JWT Clerk à chaque requête via JWKS (firebase/php-jwt). Alias : clerk.auth.
  - Aucun token Sanctum n'est émis ni stocké. Sanctum est supprimé du projet.
  - Aucun endpoint login / register / reset / google côté Laravel : ces parcours n'existent pas dans l'API. Clerk les porte.
  - La révocation d'une session dans le dashboard Clerk coupe l'accès à l'API dans la minute (durée de vie du JWT Clerk ~1 min).
  - JWKS mis en cache 5 minutes (Cache::remember('clerk_jwks', 300, ...)). En cas de rotation Clerk : php artisan cache:forget clerk_jwks.
  - Authorized party : CLERK_AUTHORIZED_PARTY vérifié sur le claim azp si défini.

Miroir local obligatoire (décision close, Phase 1) :
  - Table users avec clerk_id unique (nullable le temps du filet). Le lien Clerk ↔ local se fait par clerk_id, jamais par email.
  - Provisioning principal : webhooks Clerk (user.created / updated / deleted), signature Svix vérifiée (svix/svix), handlers idempotents (updateOrCreate sur clerk_id).
  - Filet find-or-create : si le webhook n'est pas encore passé au premier JWT valide, ClerkAuthenticate crée le miroir via l'API Clerk.
  - Suppression Clerk ≠ suppression des données : user.deleted → anonymisation (name='[compte supprimé]', email=null, phone=null, clerk_id=null). Jamais de DELETE en base.
  - Secret webhook : CLERK_WEBHOOK_SECRET dans .env (valeur dans le dashboard Clerk → Webhooks).

Clerk ≠ autorisation. Les rôles et permissions métier vivent dans spatie/laravel-permission + Policies. Interdit d'utiliser les rôles ou organisations Clerk pour une décision d'accès métier.

Clés : CLERK_SECRET_KEY côté backend uniquement, dans .env. Seule la publishable key est embarquée côté client.


Rôles et type contribuable (décision close, Phase 1)

Séparation stricte rôle / type contribuable :
- Le RÔLE porte les permissions d'accès (spatie/laravel-permission). Rôles existants : citizen, municipal_agent, cashier (régisseur), commune_admin, super_admin.
- Le TYPE DE CONTRIBUABLE (individual / business) est un attribut métier du modèle User — pas un rôle d'autorisation.
- Rôle par défaut à la création d'un user : citizen.
- Seeder : database/seeders/RolesSeeder.php — idempotent (firstOrCreate).
- Le rôle cashier encaisse uniquement. Il ne configure pas les taxes (séparation des tâches, phase 3).
- Jamais de vérification de rôle en dur dans un controller : tout passe par les Policies.


Schéma users (décision close, Phase 1)

Colonnes : id, clerk_id (unique nullable), name, email (nullable), phone (nullable), taxpayer_type (enum individual/business, default individual), commune_id (unsignedBigInt nullable, index — multi-tenant phase 2), created_at, updated_at.
Supprimés : password, remember_token, email_verified_at (Clerk porte l'identité).
Règle : toute modification de schéma passe par une migration Laravel. Aucun SQL destructif direct.


Dépendances (liste blanche — runtime)

Frontend (runtime) : @clerk/clerk-expo, expo-router, nativewind, axios, @tanstack/react-query, react-hook-form, zod, @hookform/resolvers, zustand, expo-secure-store, expo-notifications, react-native-reanimated, react-native-safe-area-context

Backend (runtime) :
  - firebase/php-jwt (vérification JWT Clerk via JWKS)
  - spatie/laravel-permission ^6.25 (rôles/permissions métier)
  - svix/svix ^1.95 (vérification de signature des webhooks Clerk)

Backend (dev) : laravel/pint, laravel/telescope — Telescope en require-dev, activé en local uniquement, jamais exposé.

Supprimés (Phase 1) : laravel/sanctum, laravel/socialite — retirés, surface d'attaque inutile.

Procédure d'exception : besoin hors liste ⇒ proposer (nom, version, raison, alternatives écartées, coût de maintenance) et attendre l'accord. Ne jamais installer d'initiative. Redux : interdit, sauf justification acceptée par ce canal.


Tests

Backend : PHPUnit (phpunit/phpunit ^11, composer.json fait foi). Test Feature obligatoire pour tout endpoint touchant l'auth, les permissions ou l'écriture de données.
Frontend : jest-expo + React Native Testing Library pour les hooks et la logique. Tout bug corrigé reçoit un test de non-régression.

Auth en test — règle dure (décision close, Phase 1) :
  Les tests ne contactent jamais Clerk.
  Mode HS256 (défaut, sans OpenSSL) : injecter CLERK_TESTING_SECRET dans config('services.clerk.testing_secret'). Le middleware utilise HS256 avec ce secret symétrique. JWT signés avec JWT::encode($payload, $secret, 'HS256').
  Mode RS256 (si OpenSSL disponible) : injecter CLERK_TESTING_JWKS (JSON JWKS local). Le middleware utilise JWK::parseKeySet().
  Webhooks : générer la signature Svix localement avec le secret de test CLERK_WEBHOOK_SECRET.
  Aucun appel réseau vers clerk.com dans la CI.

Interdits absolus : skipper un test pour le faire passer, élargir une assertion sans justification écrite, régénérer un snapshot sans l'avoir lu, supprimer un test rouge. Un test rouge se corrige dans le code ou se discute — jamais en affaiblissant le test.


Boucle de vérification (commandes)

Frontend, depuis ulky_new/ :

  npx tsc --noEmit
  npx eslint .
  npx jest

Backend, depuis backend/ :

  vendor/bin/pint --test
  php artisan test

Avant de déclarer un travail terminé : exécuter la boucle concernée et rapporter les résultats réels. Ne jamais écrire « les tests passent » sans les avoir lancés. Si l'environnement empêche l'exécution, le dire tel quel.

Résultats Phase 1 (vérifiés) : vendor/bin/pint --test → passed · php artisan test → 11 passed, 28 assertions, 0.67s.


Architecture

frontend/ (ulky_new/src/)
├── app/            # routes Expo Router uniquement — zéro logique métier
├── components/     # UI réutilisable, sans accès réseau
├── features/       # modules métier (UI + hooks + services propres au domaine)
├── hooks/          # hooks transverses
├── services/       # clients API (Axios) — un fichier par ressource
├── store/          # stores Zustand
├── utils/
├── types/
├── constants/
└── assets/

Règles : séparer UI / logique métier / appels API. Composant ≤ 300 lignes, une responsabilité par fichier. any interdit — unknown + narrowing si le type est réellement inconnu. Abstraction à la deuxième duplication, pas avant : un composant d'écran a le droit d'être unique.

backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Controllers légers + ClerkWebhookController
│   │   ├── Middleware/     # ClerkAuthenticate (guard JWT natif)
│   │   ├── Requests/       # Form Requests — validation de toute entrée
│   │   └── Resources/      # API Resources (UserResource, etc.)
│   ├── Models/             # User (HasRoles, clerk_id, sans password)
│   ├── Services/           # logique métier
│   ├── Repositories/       # requêtes complexes
│   └── Policies/           # autorisations (spatie/laravel-permission)
├── routes/
│   └── api.php             # Préfixe /api — webhook hors guard, v1 sous clerk.auth
├── database/
│   ├── migrations/         # schéma users refondu Phase 1 + tables Spatie
│   └── seeders/            # RolesSeeder (idempotent)
├── tests/
│   └── Feature/
│       └── Auth/           # MeTest (4 cas) + ClerkWebhookTest (5 cas)
└── config/
    └── services.php        # clerk.secret_key, jwks_url, webhook_secret, authorized_party, testing_secret, testing_jwks

Règles : controllers légers, validation en Form Requests, logique métier en Services, requêtes complexes en Repositories, autorisations en Policies, réponses au format défini ci-dessous.


Contrat API

Préfixe /api/v1.
Succès : { "data": ..., "meta": { ... } } — meta optionnel, pagination Laravel standard.
Erreur : format Laravel natif { "message": "...", "errors": { ... } }, codes HTTP corrects (422 validation, 401/403 auth, 404, 429).
Aucune enveloppe alternative, aucune erreur renvoyée en 200.

Routes actives (Phase 1) :
  GET  /api/v1/auth/me       → AuthController@me      [clerk.auth]
  POST /api/v1/auth/logout   → AuthController@logout   [clerk.auth]
  POST /api/webhooks/clerk   → ClerkWebhookController@handle  [throttle:60,1, hors clerk.auth]


Sécurité (décisions, pas catégories)

Validation : Form Request sur toute entrée, sans exception.
Auth : portée par le guard Clerk natif (ClerkAuthenticate). throttle:60,1 sur l'endpoint webhook ; les parcours login/register/reset/google n'existent pas côté Laravel.
Autorisation : spatie/laravel-permission + Policies. Aucune vérification de rôle en dur dans un controller.
Secrets : .env uniquement — jamais commités, jamais en dur, jamais loggés.
Jamais : désactiver une protection Laravel (CSRF, escaping, casts), exposer Telescope, construire du SQL par concaténation, embarquer la CLERK_SECRET_KEY côté client, accepter un webhook sans vérification de signature Svix.


Variables d'environnement requises (backend)

Production :
  CLERK_SECRET_KEY        = sk_live_...         # backend uniquement
  CLERK_JWKS_URL          = https://<frontend-api>.clerk.accounts.dev/.well-known/jwks.json
  CLERK_WEBHOOK_SECRET    = whsec_...           # dashboard Clerk → Webhooks
  CLERK_AUTHORIZED_PARTY  = <expo-app-scheme>   # optionnel, recommandé

CI uniquement (ne jamais mettre en .env de prod) :
  CLERK_TESTING_SECRET    = <chaîne arbitraire> # mode HS256 sans OpenSSL


Base de données

Toute modification de schéma passe par une migration Laravel. Aucun SQL destructif direct.
PostgreSQL vanilla uniquement : aucune dépendance applicative aux fonctionnalités propres à Neon (branching, etc.). La portabilité vers l'auto-hébergé est une contrainte de design, pas un vœu.
Pooling Neon (mode transaction) : ne présumer d'aucun état de session ni de prepared statements persistants ; migrations sur la connexion non poolée.
Tout index ajouté est justifié par un commentaire dans la migration.
commune_id est indexé sur users — préparation multi-tenant phase 2.


Workflow proportionné

Trivial (typo, bug local évident, ≤ ~10 lignes, aucun changement d'interface) : corriger directement, citer le test pertinent.
Intermédiaire : annoncer l'intention en une phrase, puis faire.
Structurel (schéma, dépendance, API publique, sécurité, plusieurs modules) : plan d'abord — fichiers concernés, impacts, alternatives — et attendre validation.
Git : commits atomiques en Conventional Commits, en anglais (feat:, fix:, refactor:…). Jamais de push --force, jamais de réécriture d'historique.


Honnêteté opérationnelle

Chaque livraison distingue ce qui est vérifié (exécuté) de ce qui est raisonné (non exécuté).
Après deux tentatives échouées sur le même problème : stop. Exposer le diagnostic, les hypothèses restantes et ce qui les départagerait, au lieu de patcher en boucle.
Ne jamais modifier la configuration de l'outillage (tsconfig, eslint, pint, pest/phpunit) pour faire passer une vérification. Pas de @ts-ignore / @ts-expect-error, pas de eslint-disable, pas de catch vide — sauf accord explicite, justifié en commentaire.
Ambiguïté : choisir l'interprétation la plus probable, l'énoncer en une ligne, continuer. Ne bloquer par une question que si les interprétations divergent structurellement.


Historique des phases

Phase 0 (Socle) — en cours : squelette frontend + backend, CI, déploiement continu.
Phase 1 (Identité & Rôles) — LIVRÉE le 2026-06-12 :
  Guard Clerk natif (ClerkAuthenticate, alias clerk.auth).
  Miroir users : clerk_id, phone, taxpayer_type, commune_id. Sans password.
  Webhooks Clerk (Svix, idempotents) : created/updated/deleted.
  Rôles Spatie : citizen, municipal_agent, cashier, commune_admin, super_admin.
  Sanctum et Socialite supprimés.
  Tests Feature : 11 passed, 28 assertions (vérifiés).
Phase 2 (Référentiel taxes et contribuables) — à venir.
Phase 3 (Paiement et quittance) — à venir.
Phase 4 (Tableau de bord régisseur) — à venir.