# Audit de sécurité & fiabilité — ULKY

Date : 2026-06-13
Périmètre : backend Laravel (`backend/`), frontend Expo/React Native (`ulky_new/`),
workflows CI/CD (`.github/`).
Méthode : revue manuelle du code (auth, autorisations, webhooks, paiements,
quittances, export), exécution de la suite de tests et de la chaîne CI en local.

Légende : ✅ corrigé dans cette branche · ⚠️ résiduel / recommandation.

---

## 1. Vulnérabilités corrigées

### ✅ V1 — Injection de formules CSV (CSV/Formula Injection) — *Élevé*
`AdminController::exportCsv` écrivait des champs contrôlés par le contribuable
(nom issu de Clerk, téléphone, etc.) directement dans le CSV. Un nom tel que
`=cmd|'/c calc'!A1` est interprété comme une **formule** à l'ouverture du fichier
dans Excel/LibreOffice par un agent municipal → exécution de code/exfiltration.
**Correctif** : neutralisation (`neutralizeCsvInjection`) qui préfixe d'une
apostrophe toute valeur commençant par `= + - @ \t \r`. Test de non-régression
ajouté (`AdminExportCsvTest`).

### ✅ V2 — Script d'infrastructure laissé dans le dépôt — *Moyen*
`.tmp_check_data.py` (versionné) divulguait des détails d'infrastructure : hôte/port
SSH (`127.0.0.1:2222`), nom d'utilisateur (`bradley`), chemin de déploiement
(`/var/www/ulky/backend`) et usage de `php artisan tinker` pour dumper les données
utilisateurs. **Correctif** : fichier supprimé.

### ✅ V3 — Absence de limitation de débit sur l'API authentifiée — *Moyen*
Le groupe `v1` (dont `POST /tax-notices/{id}/pay`) n'avait **aucun** `throttle`.
`pay` accepte un numéro de téléphone arbitraire et déclenche un push Mobile Money
SingPay → risque de harcèlement (push USSD en boucle) et d'abus de coûts.
**Correctif** : ajout de `throttle:120,1` sur le groupe `v1`.

### ✅ V4 — Autorisation de téléchargement de quittance non « deny-by-default » — *Faible/Moyen*
`ReceiptController::download` n'interdisait l'accès qu'au cas explicite
`hasRole('citizen') && propriétaire différent`. Un utilisateur sans rôle aurait pu
télécharger n'importe quelle quittance. **Correctif** : logique inversée en
deny-by-default (autorisé seulement si propriétaire **ou** agent/admin).

### ✅ V5 — Clés de test JWT honorées en toute circonstance — *Faible (défense en profondeur)*
`ClerkAuthenticate::resolveKeys` acceptait `CLERK_TESTING_SECRET` (bypass HS256) /
`CLERK_TESTING_JWKS` quel que soit l'environnement. Une mauvaise configuration en
prod aurait transformé un secret symétrique en bypass de signature.
**Correctif** : ces modes ne sont plus honorés si `app()->environment('production')`.

### ✅ V6 — `SingPayService` provoque un 500 si `SINGPAY_WALLET_ID` absent — *Fiabilité/DoS léger*
La propriété typée `string $walletId` recevait `null` (config non définie) →
`TypeError` → 500 sur `/pay`. **Correctif** : coalescence `(string) … ?? ''`.

---

## 2. Bugs fonctionnels corrigés (application & CI cassées)

### ✅ F1 — Volet admin des avis entièrement cassé (500) — relation manquante
`AdminController` (liste, détail, export) et `AdminTaxNoticeResource` utilisaient
`->with(['payment','payment.receipt'])` et `whenLoaded('payment')`, mais le modèle
`TaxNotice` **ne définissait pas** la relation `payment` → `Call to undefined
relationship [payment]` (500) sur toutes les vues admin de suivi des avis.
**Correctif** : ajout de `TaxNotice::payment()` (`hasOne(Payment)->latestOfMany()`).
Tests ajoutés.

### ✅ F2 — `AdminTaxNoticeResource` : bloc « receipt » jamais renseigné
Le bloc lisait `whenLoaded('receipt')` (relation inexistante sur `TaxNotice`) et le
champ `number` (le modèle expose `receipt_number`). L'admin ne voyait jamais le
numéro de quittance ni l'URL de vérification. **Correctif** : lecture via
`payment.receipt` et champ `receipt_number`.

### ✅ F3 — CI cassée : `.env.ci` référencé mais absent
`.github/workflows/ci.yml` exécute `cp .env.ci .env`, or le fichier n'a jamais été
versionné (ignoré par `.env.*`). La CI échouait dès cette étape.
**Correctif** : ajout de `backend/.env.ci` (valeurs mockées, aucun secret réel) et
exception `!backend/.env.ci` dans `.gitignore`.

### ✅ F4 — CI cassée : violations de style Pint
`Market.php` (import inutilisé), `AdminController.php`, `AdminTaxNoticeResource.php`
échouaient à `pint --test`. **Correctif** : `pint` appliqué.

Chaîne CI rejouée en local de bout en bout (pint → migrate → seed → tests) :
**34 tests verts (106 assertions)**.

---

## 3. Points résiduels / recommandations (non bloquants)

- ⚠️ **R1 — `pay` accepte un téléphone arbitraire.** Le push Mobile Money peut viser
  n'importe quel numéro. Le rate-limit (V3) atténue l'abus ; envisager une
  validation de format (`/^0?[0-9]{8,12}$/`) et/ou un rapprochement avec le profil.
- ⚠️ **R2 — Page publique de vérification de quittance.** `/verify/receipt/{token}`
  expose le nom du contribuable, le montant et l'ID de transaction à quiconque
  détient le jeton. Acceptable (jeton aléatoire 40 caractères, entropie forte),
  mais à garder à l'esprit côté RGPD/CNPDCP — limiter aux données strictement
  nécessaires à la preuve d'authenticité.
- ⚠️ **R3 — `Carbon::parse()` sur des filtres de date non validés** (`taxNotices`,
  `auditLogs`, `exportCsv`) peut lever une exception → 500. Ajouter une règle
  `date` ou un `try/catch`.
- ⚠️ **R4 — Configuration Clerk en production.** Définir `CLERK_AUTHORIZED_PARTY`
  pour activer la vérification `azp`, et garantir `APP_DEBUG=false`
  (le défaut de `config/app.php` est `false`, mais `.env.example` met `true`).
- ⚠️ **R5 — Accès admin et séparation des tâches.** Le rôle `cashier` accède au
  dashboard, à la recherche citoyen et à l'export (PII). Conforme au modèle de
  séparation des tâches documenté, mais à confirmer fonctionnellement.

---

## 4. Éléments vérifiés et jugés sains

- **Injection SQL** : recherches admin (`ilike "%…%"`) et filtres passent par le
  query builder Eloquent (paramètres liés) — pas de concaténation.
- **Webhooks** : Clerk (signature Svix) et SingPay (HMAC-SHA256 timing-safe via
  `hash_equals`) vérifiés ; SingPay ajoute une confirmation serveur→serveur avant
  tout changement d'état ; idempotence et journal d'audit append-only en place.
- **Falsification de montant** : le paiement utilise `total_amount` figé en base,
  pas une valeur fournie par le client.
- **Création d'avis / de taxes** : réservée aux rôles agents via Policies.
- **Secrets** : aucun secret réel versionné ; tout provient de variables
  d'environnement.

---

## 5. Isolation par utilisateur, multi-tenant & montée en charge (revue 2)

### ✅ V7 — Fuite d'isolation sur `GET /tax-notices` — *Élevé*
La liste restreignait uniquement le rôle `citizen` à ses propres avis ; **tout
autre compte non-admin** (commerçant `merchant`, ou utilisateur sans rôle) tombait
dans la branche « agent » et pouvait **énumérer les avis de TOUS les
contribuables**. Contraire au principe « chacun ne voit que ses données ».
**Correctif** : logique inversée en deny-by-default — seuls
`municipal_agent | cashier | commune_admin | super_admin` voient les avis des
autres ; tout le reste est filtré sur `user_id = soi`. Tests ajoutés
(`TaxNoticeIsolationTest` : commerçant, compte sans rôle, agent).

### ✅ V8 — Course au provisioning multi-appareils — *Fiabilité à l'échelle*
`ClerkAuthenticate::provisionUser` faisait `User::create()` : deux connexions
simultanées d'un même nouvel utilisateur (plusieurs appareils, ou webhook en
parallèle) provoquaient une violation d'unicité `clerk_id` → 500.
**Correctif** : `firstOrCreate()` idempotent + assignation de rôle seulement à la
création réelle.

### Isolation — état après correctifs (vérifié)
| Endpoint | Citoyen / Commerçant | Agent / Admin |
|---|---|---|
| `GET /tax-notices` | uniquement les siens ✅ | tous (filtre `user_id` optionnel) |
| `GET /tax-notices/{id}` | seulement si propriétaire (Policy `view`) ✅ | tous |
| `POST /tax-notices/{id}/pay` | seulement les siens (Policy `view`) ✅ | — |
| `PUT …/cancel`, `POST /tax-notices` | 403 ✅ | autorisé (Policy) |
| `GET /taxes` (référentiel) | 403 (Policy `viewAny`) ✅ | autorisé |
| `GET /receipts/{id}` | propriétaire seulement ✅ | agents |
| `/api/v1/admin/*` | 403 via `admin.role` ✅ | autorisé |
| `GET /auth/me` | son propre profil ✅ | son profil |

→ Le modèle « chacun n'accède qu'à sa page » (type WhatsApp) est désormais
respecté pour les citoyens **et** les commerçants.

### Montée en charge (500 / 1 000 / 2 000 appareils) — analyse
- **Auth stateless** : JWT Clerk vérifié à chaque requête via JWKS mis en cache
  5 min ; aucune session serveur → scalabilité horizontale, pas d'état partagé.
- **Base de données** : PostgreSQL Neon avec *pooler* de connexions (cf.
  `.env.example`) → supporte un grand nombre de clients concurrents.
- **Recherche utilisateur** : `clerk_id` est unique et indexé (lookup O(log n)).
- **Rate-limit** : `throttle:120,1` par utilisateur (et `120,1` admin) → 2 000
  appareils = 2 000 compteurs indépendants, pas de blocage mutuel.
- **Quittances** : numérotation séquentielle protégée par `lockForUpdate()` →
  pas de doublon sous concurrence.
- **Provisioning** : désormais idempotent (V8).

→ Aucun goulot d'étranglement structurel : l'architecture supporte des milliers
d'appareils. Recommandations d'exploitation : activer un cache partagé
(Redis : déjà prévu dans la config) pour le rate-limit en multi-instances, et
surveiller la taille du pool Neon.

### ⚠️ R6 — Cloisonnement multi-commune (multi-tenant) non appliqué
Les routes admin (`AdminController`) renvoient les avis/recettes de **toutes les
communes**, sans filtrer par `commune_id`. Un `commune_admin` de la commune A voit
les données de la commune B. À implémenter lors de la phase 2 multi-tenant :
scoper les requêtes admin sur `commune_id` du `commune_admin` (le `super_admin`
restant global). Non corrigé ici (décision d'architecture / hors périmètre actuel).

---

## 6. Sécurité du frontend (Expo / React Native)

- ✅ **Clés** : seule la clé Clerk **publishable** (`pk_test_…`) est présente
  côté client — c'est public par conception. Aucune `CLERK_SECRET_KEY` ni secret
  SingPay côté frontend.
- ✅ **Stockage du token** : `tokenCache` de `@clerk/expo` (Expo SecureStore /
  Keychain sur mobile). Le bearer est injecté à la volée via un intercepteur Axios.
- ✅ **Garde d'accès** : `(app)/_layout` redirige les non-connectés ; l'onglet
  Admin est masqué pour les non-agents — défense en profondeur, l'autorité reste
  le backend (`admin.role`).
- ⚠️ **R7 — Logs console verbeux** (`login.tsx`) : `console.log`/`console.error`
  tracent l'e-mail et les statuts d'auth. À retirer (ou conditionner à `__DEV__`)
  pour éviter la fuite de PII dans les logs de prod.
- ⚠️ **R8 — Export CSV web sans en-tête d'auth** : `adminService.exportCsv` ouvre
  l'URL via `window.open` (web), qui ne peut pas porter le header `Authorization`
  → l'endpoint protégé renverra 401 sur web. Bug fonctionnel (pas une faille) :
  prévoir un téléchargement authentifié (fetch + blob) ou un lien signé temporaire.
- ⚠️ **R9 — Token web en stockage non-httpOnly** : sur web, le fallback de cache
  utilise `localStorage` (accessible au JS) — exposition en cas de XSS. Inhérent
  au modèle SPA + Clerk ; à compenser par une CSP stricte côté hébergement web.

---

## 7. Revue 3 — traitement des recommandations

### ✅ R3 — Robustesse des filtres de date (AdminController)
`taxNotices`, `auditLogs`, `exportCsv` parsaient les dates utilisateur via
`Carbon::parse()` (exception → 500 sur entrée invalide). Ajout d'un helper
`parseDate()` tolérant (retourne null si vide/invalide).

### ✅ R6 — Cloisonnement multi-commune (multi-tenant)
Les routes admin sont désormais scopées par `commune_id` :
- `super_admin` : accès global ; tout autre rôle admin : sa commune uniquement.
- Appliqué à `dashboard` (KPIs/paiements via la relation `taxNotice`),
  `taxNotices`, `showTaxNotice` (404 hors commune), `searchCitizen`,
  `createTaxNotice` (citoyen résolu dans la commune), `exportCsv`.
- Helper `scopeToCommune()` + `hasGlobalScope()`. Tests : `AdminCommuneScopeTest`.
- Limite connue : `audit_logs` n'a pas de colonne `commune_id` → reste global
  (à traiter par une migration si un cloisonnement strict de l'audit est requis).

### ✅ R7 — Logs console (PII) côté frontend
Tous les `console.*` de `login.tsx` et `profile.tsx` (dont des logs d'e-mail)
sont gatés derrière `__DEV__` → aucun log en build de production.

### ✅ R8 — Export CSV web sans authentification
`adminService.exportCsv` n'utilisait plus `window.open` (qui ne peut pas porter
l'en-tête `Authorization` → 401 sur web). Remplacé par un `fetch` authentifié
(token Clerk via le nouveau `getAuthToken()` d'`api.ts`) → blob → téléchargement
via `<a download>`. Le chemin mobile partage désormais le même en-tête propre.

### ✅ Réparation des pipelines CI (revue 4)
La première exécution CI de la PR a révélé deux blocages d'infrastructure
**pré-existants** (sans lien avec le code de sécurité), désormais corrigés :

- **Backend** : `composer install` échouait (`bootstrap/cache directory must be
  present and writable`). Les dossiers runtime Laravel non versionnés
  (`bootstrap/cache`, `storage/framework/{cache,sessions,views}`, `storage/logs`)
  sont désormais suivis via des `.gitignore` standards.
- **Frontend** : `npm ci` échouait (`package-lock.json` désynchronisé →
  `Missing: utf-8-validate`). Lockfile régénéré. L'install incomplète privait
  aussi Expo de `tsconfig.base.json` (cassant `tsc`) ; résolu par l'install
  saine. Ajout de la config ESLint officielle Expo (absente du repo) et d'une
  déclaration `*.css` (NativeWind, TS2882).

Séquences CI rejouées localement de bout en bout : **frontend** `npm ci → tsc →
eslint --max-warnings=0` verts ; **backend** 39 tests + Pint verts.
