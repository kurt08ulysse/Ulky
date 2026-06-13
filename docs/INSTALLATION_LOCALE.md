# Installer et lancer ULKY en local

Guide pas-à-pas pour faire tourner **le backend (Laravel)** et **le frontend (Expo/React Native)** sur ta machine, puis (optionnel) servir le backend derrière **NGINX**.

> Le dépôt est un *monorepo* :
> - `backend/` → l'API Laravel + le back-office d'administration (`/admin`).
> - `ulky_new/` → l'application mobile/web Expo (React Native).

---

## 1. Outils à installer (prérequis)

| Outil | Version | Pourquoi |
|------|---------|----------|
| **Git** | récent | récupérer le code |
| **PHP** | **8.2+** | exécuter Laravel (la prod est en 8.5) |
| **Extensions PHP** | voir ci-dessous | obligatoires |
| **Composer** | 2.x | dépendances PHP |
| **Node.js** | **20+** (LTS) | exécuter Expo |
| **npm** | fourni avec Node | dépendances JS |
| **PostgreSQL** | 16 (recommandé) | base de données (ou **SQLite** pour démarrer vite) |
| **Expo Go** (téléphone) ou un **émulateur** Android/iOS | — | tester l'app mobile |

**Extensions PHP requises** : `pdo`, `pdo_pgsql` (et/ou `pdo_sqlite`), `mbstring`, `xml`, `ctype`, `json`, `bcmath`, `gd` (génération des QR codes), `fileinfo`, `openssl`, `curl`, `tokenizer`.

### Installation des outils (Ubuntu / Debian)

```bash
sudo apt update
sudo apt install -y git unzip curl \
  php8.2 php8.2-cli php8.2-pgsql php8.2-sqlite3 php8.2-mbstring \
  php8.2-xml php8.2-bcmath php8.2-gd php8.2-curl \
  postgresql postgresql-contrib

# Composer
php -r "copy('https://getcomposer.org/installer','composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Node.js 20 (via nvm, recommandé)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
# rouvre le terminal, puis :
nvm install 20 && nvm use 20
```

### macOS (Homebrew)

```bash
brew install git php@8.2 composer node@20 postgresql@16
brew services start postgresql@16
```

### Windows

Le plus simple : **WSL2 (Ubuntu)** et suivre la procédure Ubuntu ci-dessus. (Sinon Laragon/XAMPP pour PHP, et Node depuis nodejs.org.)

### Vérifier

```bash
php -v        # 8.2+
composer -V   # 2.x
node -v       # v20+
npm -v
php -m | grep -E 'pdo_pgsql|pdo_sqlite|gd|mbstring'   # doivent apparaître
```

---

## 2. Récupérer le code

```bash
git clone https://github.com/kurt08ulysse/ulky.git
cd ulky
```

---

## 3. Backend — démarrage RAPIDE (SQLite, sans installer PostgreSQL)

Idéal pour tester l'API et le back-office immédiatement.

```bash
cd backend

# 1. Dépendances PHP
composer install

# 2. Fichier d'environnement
cp .env.example .env

# 3. Choisir SQLite : édite .env et mets ces lignes
#    DB_CONNECTION=sqlite
#    DB_DATABASE=/chemin/absolu/vers/ulky/backend/database/database.sqlite
#    (et commente/supprime DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD)
touch database/database.sqlite

# 4. Clé d'application
php artisan key:generate

# 5. Compte du back-office (admin) — à mettre dans .env :
#    BACKOFFICE_ADMIN_EMAIL=admin@ulky.local
#    BACKOFFICE_ADMIN_PASSWORD=ChangeMoi2026

# 6. Créer les tables + données de base (rôles, 39 taxes, compte admin)
php artisan migrate --force
php artisan db:seed --force

# 7. Lien de stockage (photos des élus, quittances PDF)
php artisan storage:link

# 8. Démarrer le serveur de dev
php artisan serve
```

➡️ API : **http://localhost:8000/api/v1**
➡️ Back-office : **http://localhost:8000/admin** (connexion avec `BACKOFFICE_ADMIN_EMAIL` / `BACKOFFICE_ADMIN_PASSWORD`)
➡️ Page d'accueil Laravel : **http://localhost:8000**

> Pour tester l'API depuis un **téléphone physique**, lance plutôt :
> `php artisan serve --host=0.0.0.0 --port=8000` et utilise l'**IP locale** de ta machine (ex. `http://192.168.1.20:8000`).

---

## 4. Backend — avec PostgreSQL (proche de la prod / Neon)

```bash
# Créer la base et l'utilisateur
sudo -u postgres psql -c "CREATE DATABASE ulky;"
sudo -u postgres psql -c "CREATE USER ulky WITH PASSWORD 'ulky';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ulky TO ulky;"
```

Dans `backend/.env` :

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ulky
DB_USERNAME=ulky
DB_PASSWORD=ulky
DB_SSLMODE=prefer
```

Puis : `php artisan migrate --force && php artisan db:seed --force`.

> Pour pointer vers **Neon** : reprends l'hôte/port/identifiants du dashboard Neon et mets `DB_SSLMODE=require`.

---

## 5. Variables `.env` importantes (auth & paiement)

L'authentification est portée par **Clerk** ; les paiements par **SingPay**.

```env
# ─── Clerk (connexion des utilisateurs) ───
# Le backend vérifie les jetons via les JWKS de TON instance Clerk.
CLERK_JWKS_URL=https://<ton-frontend-api>.clerk.accounts.dev/.well-known/jwks.json
CLERK_SECRET_KEY=sk_test_xxx
CLERK_WEBHOOK_SECRET=whsec_xxx

# ─── SingPay (Mobile Money) ───
SINGPAY_WALLET_ID=
SINGPAY_CLIENT_ID=
SINGPAY_CLIENT_SECRET=
SINGPAY_BASE_URL=https://gateway.singpay.ga/v1
SINGPAY_WEBHOOK_SECRET=
# En local/test, on peut simuler SingPay :
SINGPAY_TESTING_STATUS=successful
```

> **Tester l'API SANS Clerk réel** : les tests automatisés utilisent un secret symétrique (`CLERK_TESTING_SECRET`) — voir section 6. Mais pour **se connecter depuis l'app mobile**, il faut une vraie instance Clerk (clé publishable côté app + `CLERK_JWKS_URL` correspondant côté backend).

---

## 6. Lancer les tests & le style (backend)

```bash
cd backend
vendor/bin/pint --test      # style de code
php artisan test            # suite de tests (auth, paiements, isolation, etc.)
```

---

## 7. Frontend — application Expo

```bash
cd ulky_new

# 1. Dépendances (un script postinstall applique des patches automatiquement)
npm install

# 2. Indiquer l'URL du backend à l'app
#    Ouvre ulky_new/app.json et ajoute (dans "expo") un bloc "extra" :
#
#      "extra": {
#        "BACKEND_API_URL": "http://192.168.1.20:8000/api/v1"
#      }
#
#    - Téléphone réel  : IP locale de ta machine (ex. 192.168.1.20)
#    - Émulateur Android: http://10.0.2.2:8000/api/v1
#    - Web (navigateur) : http://localhost:8000/api/v1

# 3. Démarrer Expo
npx expo start
```

Puis :
- appuie sur **`w`** → ouvre dans le navigateur (web),
- scanne le **QR code** avec l'app **Expo Go** (téléphone),
- ou **`a`** (Android) / **`i`** (iOS) pour un émulateur.

> **Clerk côté app** : la clé *publishable* est dans `ulky_new/src/app/_layout.tsx` (`CLERK_PUBLISHABLE_KEY`). Remplace-la par celle de ton instance Clerk si besoin — c'est une clé publique, sans danger côté client.

### Vérifs frontend

```bash
cd ulky_new
npx tsc --noEmit              # types
npx eslint . --max-warnings=0 # lint
```

---

## 8. (Optionnel) Servir le backend derrière NGINX

`php artisan serve` suffit pour développer. Pour reproduire un environnement type production en local, utilise **NGINX + PHP-FPM**.

```bash
sudo apt install -y nginx php8.2-fpm
sudo systemctl enable --now php8.2-fpm nginx
```

Crée `/etc/nginx/sites-available/ulky` :

```nginx
server {
    listen 80;
    server_name ulky.local;                 # ou localhost
    root /chemin/absolu/vers/ulky/backend/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;               # uploads (photos d'élus, etc.)
}
```

Activer et recharger :

```bash
sudo ln -s /etc/nginx/sites-available/ulky /etc/nginx/sites-enabled/ulky
# Donner à NGINX/PHP-FPM les droits d'écriture sur storage & cache :
sudo chown -R www-data:www-data backend/storage backend/bootstrap/cache
sudo nginx -t && sudo systemctl reload nginx
# (optionnel) faire pointer ulky.local vers 127.0.0.1 :
echo "127.0.0.1 ulky.local" | sudo tee -a /etc/hosts
```

➡️ Backend servi sur **http://ulky.local** (API, `/admin`, vérification de quittance `/verify/...`).

> Le **frontend web** Expo peut être servi par NGINX après build statique :
> `cd ulky_new && npx expo export --platform web` → dossier `dist/` à servir comme `root` d'un autre `server {}`. En développement, garde simplement `npx expo start`.

---

## 9. Récapitulatif des URLs (dev)

| Élément | URL |
|--------|-----|
| API | `http://localhost:8000/api/v1` |
| Back-office admin | `http://localhost:8000/admin` |
| Vérification publique de quittance | `http://localhost:8000/verify/receipt/{token}` |
| App Expo (web) | `http://localhost:8081` (ouvert par `expo start`) |

---

## 10. Dépannage (erreurs fréquentes)

| Erreur | Cause / solution |
|--------|------------------|
| `bootstrap/cache directory must be present and writable` | `mkdir -p backend/bootstrap/cache && chmod -R 775 backend/bootstrap/cache backend/storage` |
| `could not find driver` (PDO) | extension manquante : installer `php8.2-pgsql` ou `php8.2-sqlite3` |
| QR / quittance en erreur | extension `php8.2-gd` manquante |
| Photos d'élus introuvables | exécuter `php artisan storage:link` |
| L'app mobile n'atteint pas l'API | utiliser l'**IP locale** (pas `localhost`) + `php artisan serve --host=0.0.0.0` |
| 401 sur l'API | jeton Clerk invalide : vérifier `CLERK_JWKS_URL` (backend) ⇄ clé publishable (app) de la **même** instance Clerk |
| `npm ci` échoue | utiliser `npm install` (le lock est régénéré au besoin) |

---

*Pour le déploiement (VM Ubuntu, Tailscale, Neon) voir `deploy.yml` et `SECURITY_AUDIT.md`. Ce document couvre uniquement le **développement local**.*
