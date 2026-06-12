# Suivi du Projet ULKY — Claude & Agents

Source de vérité pour tout agent reprenant le développement d'ULKY. Architecture, état réel, prochaines étapes.

---

## ⚠️ ALERTE SÉCURITÉ — ACTION HUMAINE REQUISE (2026-06-12)

Avant toute reprise de développement, l'humain doit traiter trois fuites de secrets identifiées dans l'historique git (commits antérieurs à ce document) :

1. **SingPay Wallet ID** (présent en clair dans une version précédente de ce fichier et dans `agent.md` / `claude.md`) — à considérer comme compromis.
   → **Faire tourner le wallet dans le dashboard SingPay**, puis mettre la nouvelle valeur dans `backend/.env` (jamais en clair dans un MD).

2. **Clé SSH privée de déploiement** (`VM_SSH_PRIVATE_KEY`) — était collée intégralement dans ce document. Permet l'accès SSH à la VM de prod.
   → **Régénérer une paire Ed25519**, retirer l'ancienne `authorized_keys` de la VM, ajouter la nouvelle clé publique, mettre la nouvelle clé privée dans le secret GitHub `VM_SSH_PRIVATE_KEY`.

3. **Historique Git** — les deux secrets sont dans l'historique du dépôt `kurt08ulysse/ulky`. La rotation ci-dessus suffit ; la réécriture d'historique (`git filter-repo`) est optionnelle (et destructive) puisque les nouveaux secrets seront différents.

Tant que ces trois actions ne sont pas faites, considérer le canal de paiement et l'accès VM comme exposés.

---

## 📌 Architecture & Stack

- **Monorepo** racine `APP/`
  - **Backend** : `backend/` — Laravel 12 · PHP 8.5 · PostgreSQL Neon · Clerk (JWT natif via JWKS)
  - **Frontend** : `ulky_new/` — Expo SDK 56 · React Native 0.85 · React 19.2 · NativeWind v4 · Clerk Expo · TypeScript strict
- **BDD** : PostgreSQL sur Neon (Pooler pour l'app, Direct pour les migrations)
- **Auth** : Clerk exclusivement (pas de mot de passe local ; webhooks Svix synchronisent le miroir users)
- **VM staging** : Ubuntu 26.04 LTS · Tailscale `100.68.232.112` · Nginx + PHP-FPM 8.5 · Cloudflare Tunnel (Quick — URL non stable entre reboots)

Les règles dures (interdictions, conventions, frontière des états, contrat API) vivent dans `ulky_new/AGENTS.md`. Ce fichier-ci ne tient que le journal des phases.

---

## ✅ Phase 0 — Socle (livrée 2026-06-12)

Monorepo, `.gitignore` / `.gitattributes`, GitHub Actions (`ci.yml`, `deploy.yml`), VM Ubuntu, Tailscale, stack Nginx + PHP 8.5 + Composer, Cloudflare Quick Tunnel opérationnel.

## ✅ Phase 1 — Identité, Rôles, Webhooks Clerk (livrée 2026-06-12)

- `spatie/laravel-permission ^6.25`, `svix/svix ^1.95`, `firebase/php-jwt` installés ; Sanctum et Socialite supprimés.
- Schéma `users` refondu : `clerk_id` unique, `phone`, `taxpayer_type`, `commune_id`. Sans `password` ni `remember_token`.
- Rôles : `citizen`, `municipal_agent`, `cashier`, `commune_admin`, `super_admin` (seeder idempotent).
- Middleware `ClerkAuthenticate` (alias `clerk.auth`) — vérification JWT via JWKS, cache 5 min.
- `ClerkWebhookController` (signature Svix, handlers idempotents `user.created/updated/deleted`).
- Tests Feature : `MeTest` + `ClerkWebhookTest` — passants (Pint + PHPUnit OK).

## ✅ Phase 2 — Référentiel taxes & contribuables (livrée 2026-06-12)

- Modèles `Tax` (`base_amount` / `stamp_amount` en centimes, `periodicity`) et `TaxNotice` (montants gelés à l'émission, statuts `pending/paid/cancelled`).
- `TaxesTableSeeder` : 39 actes administratifs réels.
- Policies `TaxPolicy` et `TaxNoticePolicy`. Citoyens voient leurs propres avis uniquement ; municipal_agent / admin gèrent le référentiel.
- API : `GET|POST|PUT|DELETE /api/v1/taxes`, `GET|POST /api/v1/tax-notices`, `PUT /api/v1/tax-notices/{id}/cancel`.
- Frontend : écran `TaxesScreen`, filtres de statut, calcul du total dû, gestion des rôles pour l'annulation.
- Tests Feature : `TaxTest` + `TaxNoticeTest` (19 passants, 54 assertions).

---

## 🟡 Phase 3 — Paiement (SingPay) & Quittance — CORRIGÉE LE 2026-06-12

État livré initialement (sans validation sécurité) :
- Tables `payments`, `receipts`, `receipt_counters`.
- Numérotation séquentielle sans trou via `lockForUpdate()` dans une transaction (`ReceiptGeneratorService`).
- `SingPayService` (`initiatePayment` Airtel `74` / Moov `62`, `checkTransactionStatus` exposé).
- Quittance PDF (`barryvdh/laravel-dompdf`), routes publiques `/verify/receipt/{token}` et `/verify/receipt/{token}/pdf` (token de 240 bits via `Str::random(40)`).
- Frontend : `taxService.ts`, hook `usePayTaxNotice`, écran `TaxesScreen` avec choix opérateur, attente Push USSD, bouton « Voir & Télécharger le reçu ».

### Corrections apportées le 2026-06-12

| # | Trou identifié à l'audit | Fix appliqué | Statut |
|---|---|---|---|
| 1 | Webhook SingPay sans vérification de signature | Middleware `singpay.signed` (HMAC-SHA256, header `X-SingPay-Signature`, `hash_equals`) sur la route `webhooks/singpay` | ✅ |
| 2 | Pas d'appel serveur→serveur à SingPay avant `paid` | `SingPayWebhookController` appelle `SingPayService::checkTransactionStatus()` ; statut divergent → 409 + audit `webhook.status_mismatch` | ✅ |
| 3 | QR code généré via `api.qrserver.com` | `bacon/bacon-qr-code ^3.1` (backend GD) → data URI PNG inline. Aucun appel HTTP sortant. | ✅ |
| 4 | Journal d'audit append-only absent | Table `audit_logs` (sans `updated_at`/`deleted_at`), modèle `AuditLog`, `AuditService::record()`. Wires : `webhook.received`, `signature_failed`, `status_mismatch`, `payment.confirmed`, `payment.failed`, `receipt.generated`, `unknown_payment` | ✅ |
| 5 | Tokens Clerk persistés en `localStorage` sur web | Suppression de `tokenStorage.ts`, `store/auth.ts`, `loginWithClerkToken` ; ClerkProvider utilise `tokenCache` officiel de `@clerk/expo/token-cache` (undefined sur web, SecureStore sur natif) ; `TokenBridge` injecte `getToken()` dans l'intercepteur Axios | ✅ |
| 6 | Tests « 100% passants » mais ne couvrent ni signature, ni rejeu, ni divergence S2S | Nouveau `SingPayWebhookSecurityTest` (6 cas : non signé, mal signé, S2S divergent, signé OK avec audit, rejeu idempotent, référence inconnue). Total backend : **32 tests, 100 assertions** (Pint + PHPUnit OK). | ✅ |
| 7 | Écran blanc sur l'export web déployé | Cause racine : `tokenCache` custom passé à ClerkProvider + double système d'auth (Clerk + Zustand). Fix : retour au `tokenCache` officiel + Zustand auth supprimé. `npx tsc --noEmit` OK, nouveau bundle `entry-c769b9ccb2c6aa63bbc034875056d43b.js` généré dans `ulky_new/dist/`. | ✅ (à redéployer) |

### Reste à faire côté humain pour clore réellement la phase

1. **Rotation des secrets fuités** (alerte en haut du fichier) — bloquant.
2. **Configurer `SINGPAY_WEBHOOK_SECRET`** dans `backend/.env` de la VM **et** dans le dashboard SingPay (côté SingPay : enregistrer le même secret HMAC pour signer les callbacks).
3. **Migrer la base** : `php artisan migrate` sur staging (ajoute `audit_logs`).
4. **Redéployer `ulky_new/dist/`** sur la VM (le bundle local est prêt).
5. **Migrer le tunnel Cloudflare** vers un tunnel nommé / vrai sous-domaine — un reboot VM avec Quick Tunnel coupe silencieusement les paiements (SingPay détient l'URL).
6. **Test bout-en-bout** : un paiement Moov réel en sandbox SingPay → webhook signé reçu → quittance PDF générée → scan QR → page publique de vérification OK → entrées d'audit log cohérentes.

Tant que les points 1-2-3-4 ne sont pas faits, le code corrigé n'est pas en service.

---

## 🟡 Phase 4 — Tableau de bord Régisseur (en cours — 2026-06-12)

### Backend livré

| Fichier | Description |
|---|---|
| `app/Http/Middleware/RequireAdminRole.php` | Guard rôle admin — 403 pour les citoyens |
| `app/Http/Controllers/Api/AdminController.php` | Dashboard KPIs, liste avis tous citoyens, création avis, recherche citoyen, audit logs, export CSV |
| `app/Http/Resources/AdminTaxNoticeResource.php` | Resource enrichie avec données citoyen + paiement + quittance |
| `routes/api.php` | Groupe `/api/v1/admin/*` protégé par `clerk.auth` + `admin.role` + `throttle:120,1` |
| `bootstrap/app.php` | Alias middleware `admin.role` enregistré |

Endpoints admin : `GET /dashboard`, `GET|POST /tax-notices`, `GET /tax-notices/{id}`, `GET /citizens/search`, `GET /audit-logs`, `GET /export/csv`

### Frontend livré

| Fichier | Description |
|---|---|
| `services/adminService.ts` | Toutes les fonctions API admin + export CSV (Share Sheet natif / download web) |
| `hooks/useAdmin.ts` | Hooks React Query : dashboard, avis, création, recherche citoyen, audit, export |
| `features/admin/AdminDashboardScreen.tsx` | KPI cards (4), graphique 7 jours, top 5 taxes, bouton export |
| `features/admin/AdminNoticesScreen.tsx` | Liste paginée tous avis, recherche debounce, modal création avec lookup citoyen |
| `features/admin/AdminAuditScreen.tsx` | Journal d'audit filtrable, paginé |
| `features/admin/components/KpiCard.tsx` | Composant carte KPI réutilisable |
| `features/admin/components/MiniBarChart.tsx` | Graphique barres 100% natif |
| `app/(app)/admin.tsx` | Écran container avec onglets internes Dashboard/Avis/Audit |
| `app/(app)/_layout.tsx` | Onglet ⚙️ Admin visible seulement pour les rôles admin (`href: null` pour les citoyens) |

### Correctifs livrés en même temps

- **Bouton déconnexion** : `queryClient.clear()` avant `signOut()`, gestion d'erreur avec `Alert`, spinner `loading` sur le bouton pour éviter les doubles clics.
- **Sécurité SQL** : confirmé que tous les filtres passent par le Query Builder PDO de Laravel — pas d'injection possible.

### Reste à faire pour clore la Phase 4

1. Écrire les tests Feature `AdminDashboardTest`, `AdminTaxNoticeTest`, `AdminExportTest`.
2. Tester l'onglet Admin sur l'émulateur avec un compte `municipal_agent`.
3. Faire tourner `php artisan migrate` sur la VM (+ `db:seed --class=RolesSeeder` pour le rôle `merchant`).
4. Déployer le bundle frontend.

---

## 🔜 Phase 5 — Gestion des marchés municipaux (loyers des commerçants)

### Socle de données livré (migrations + modèles)

| Fichier | Description |
|---|---|
| `migrations/2026_06_12_200000_create_markets_tables.php` | Tables `markets`, `market_stalls`, `stall_rents` |
| `app/Models/Market.php` | Marché municipal |
| `app/Models/MarketStall.php` | Emplacement (stalle) avec loyer mensuel |
| `app/Models/StallRent.php` | Avis de loyer mensuel — équivalent TaxNotice pour les marchés |
| `database/seeders/RolesSeeder.php` | Rôle `merchant` ajouté (idempotent) |

### Périmètre prévu (à démarrer)

**Pour les commerçants (rôle `merchant`)** :
- Vue de leurs emplacements et loyers mensuels en attente
- Paiement Mobile Money via SingPay (même flux que les taxes)
- Téléchargement de la quittance de loyer

**Pour les régisseurs (admin)** :
- Gestion des marchés et emplacements (CRUD)
- Attribution d'un emplacement à un commerçant
- Génération automatique des avis de loyer (commande artisan mensuelle)
- Vue de réconciliation : loyers attendus vs loyers perçus

---

## 🔧 Bouts d'infrastructure utiles

- **Tailscale VM** : `100.68.232.112` (port SSH local : `2222`)
- **Cloudflare Quick Tunnel backend** : URL régénérée à chaque `cloudflared` (instable — à remplacer par un tunnel nommé)
- **Dépôt** : `github.com/kurt08ulysse/ulky.git` (branche `main`)
- **Clé SSH déploiement** : **À RÉGÉNÉRER** (cf. alerte sécurité en haut du fichier). Procédure : `ssh-keygen -t ed25519 -f vm_deploy_new -N ""` ; `cat vm_deploy_new.pub | ssh bradley@100.68.232.112 'cat >> ~/.ssh/authorized_keys'` ; retirer l'ancienne entrée ; copier `vm_deploy_new` (privée) dans le secret GitHub `VM_SSH_PRIVATE_KEY` ; supprimer le fichier local après.
