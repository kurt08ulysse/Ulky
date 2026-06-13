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
