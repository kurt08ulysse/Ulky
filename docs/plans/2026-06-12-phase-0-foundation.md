# Phase 0 — Foundation and Continuous Deployment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stand up a production-grade skeleton with correct Clerk JWT auth (no Sanctum exchange), PostgreSQL, Docker environments (dev/staging/prod), blocking GitHub Actions CI, and one live screen fetching data from the API in staging — with a restored database backup as the final proof.

**Architecture:** The frontend sends raw Clerk JWTs in every `Authorization: Bearer` header via an Axios interceptor that calls `getToken()` fresh per request. The backend verifies each JWT per-request using `clerk/clerk-sdk-php` in a custom Laravel guard — no `/auth/login`, no Sanctum tokens, no session. Docker Compose manages services locally and in staging/prod; CI enforces the AGENTS.md verification loop on every PR.

**Tech Stack:** Expo SDK 56 · React Native 0.85.3 · TypeScript strict · NativeWind v4 · @clerk/clerk-expo v3 · React Query v5 · Zustand v5 · Laravel 12 · PHP 8.4 · clerk/clerk-sdk-php · PostgreSQL 16 · Redis 7 · Docker · GitHub Actions

---

## Findings: What Is Already In Place

**Frontend (`ulky_new/`) — substantially built but wrong auth pattern:**
- Expo SDK 56, React Native 0.85.3, TypeScript strict with `@/*` path aliases pointing to `src/`
- `expo-router` configured with `src/app/` as the route root
- NativeWind v4 fully configured — do not touch
- `ClerkProvider` at root with `expo-secure-store` tokenCache — structurally correct
- `QueryClientProvider` wrapping the tree — correct
- **VIOLATION:** `src/services/api.ts` reads a Sanctum token from `tokenStorage`, not `getToken()` from Clerk
- **VIOLATION:** `src/services/auth.ts` calls `POST /auth/clerk/token` to exchange Clerk JWT for Sanctum token — this endpoint must not exist per AGENTS.md
- **VIOLATION:** Clerk publishable key is hardcoded in `_layout.tsx`
- Missing: `src/components/`, `src/features/`, `src/hooks/`, `src/utils/`, `src/types/`, `src/constants/` directories
- Missing: jest, ESLint, testing library

**Backend (`backend/`) — wrong auth approach:**
- Laravel 12, PHP ^8.2 (AGENTS.md requires 8.4+), Sanctum installed
- `firebase/php-jwt` used for Clerk JWT verification — `clerk/clerk-sdk-php` NOT installed
- PHPUnit in use (acceptable per AGENTS.md — "Pest or PHPUnit if already in place")
- `users` table has no `clerk_id` column
- No `spatie/laravel-permission`, no webhook endpoint
- No `Services/`, `Repositories/`, `Policies/` directories
- `DB_CONNECTION=sqlite` everywhere — no PostgreSQL configured
- Two conflicting AuthController files

**Infrastructure:** No Docker, no CI, no `docs/` directory.

---

## File Map

```
APP/
├── .github/
│   └── workflows/
│       └── ci.yml                          [CREATE]
├── docker/
│   ├── nginx/default.conf                  [CREATE]
│   ├── php/Dockerfile                      [CREATE]
│   └── postgres/backup.sh                  [CREATE]
├── docker-compose.yml                      [CREATE]
├── docker-compose.staging.yml              [CREATE]
├── docker-compose.prod.yml                 [CREATE]
├── docs/plans/
│   └── 2026-06-12-phase-0-foundation.md   [THIS FILE]
│
├── ulky_new/  (frontend)
│   ├── app.config.ts                       [CREATE] replaces app.json
│   ├── .eslintrc.js                        [CREATE]
│   ├── jest.config.js                      [CREATE]
│   ├── package.json                        [MODIFY] add jest-expo, testing-library, eslint deps
│   ├── src/
│   │   ├── app/_layout.tsx                 [MODIFY] remove hardcoded key, fix auth state
│   │   ├── app/(app)/_layout.tsx           [MODIFY] useAuth() instead of useAuthStore
│   │   ├── app/(auth)/login.tsx            [MODIFY] remove loginWithClerkToken()
│   │   ├── app/(auth)/sso-callback.tsx     [MODIFY] remove loginWithClerkToken()
│   │   ├── app/(app)/index.tsx             [MODIFY] remove tokenStorage; add StatusScreen
│   │   ├── components/.gitkeep            [CREATE]
│   │   ├── features/status/
│   │   │   ├── StatusScreen.tsx            [CREATE]
│   │   │   └── useStatus.ts               [CREATE]
│   │   ├── hooks/.gitkeep                  [CREATE]
│   │   ├── services/
│   │   │   ├── api.ts                      [MODIFY] getToken() interceptor
│   │   │   └── status.ts                  [CREATE]
│   │   ├── store/auth.ts                   [MODIFY] remove token logic
│   │   ├── types/api.ts                    [CREATE]
│   │   └── constants/config.ts            [CREATE]
│   └── __tests__/services/api.test.ts     [CREATE]
│
└── backend/
    ├── composer.json                       [MODIFY] add clerk/clerk-sdk-php; remove sanctum, socialite
    ├── .env.example                        [MODIFY] add CLERK_*, pgsql, redis
    ├── config/auth.php                     [MODIFY] register 'clerk' guard
    ├── config/services.php                 [MODIFY] add authorized_parties
    ├── app/
    │   ├── Auth/ClerkGuard.php            [CREATE]
    │   ├── Http/Controllers/Api/
    │   │   ├── StatusController.php       [CREATE]
    │   │   └── MeController.php           [CREATE]
    │   ├── Models/User.php                [MODIFY] remove HasApiTokens; add clerk_id
    │   ├── Providers/AppServiceProvider.php [MODIFY] register ClerkGuard
    │   └── Services/.gitkeep             [CREATE]
    ├── database/migrations/
    │   └── 2026_06_12_000001_add_clerk_id_to_users_table.php [CREATE]
    ├── routes/api.php                      [MODIFY] clean up; add /status, /me
    └── tests/Feature/
        ├── StatusTest.php                  [CREATE]
        └── ClerkGuardTest.php             [CREATE]
```

---

## BLOCK A — Repository Scaffold

### Task A1: Create docs directory and .env.example

- [ ] **Step 1: Create root `.env.example`**

File: `APP/.env.example`
```
# CLERK
EXPO_PUBLIC_CLERK_PUBLISHABLE_KEY=pk_test_...
CLERK_SECRET_KEY=sk_test_...
CLERK_JWKS_URL=https://<your-instance>.clerk.accounts.dev/.well-known/jwks.json
CLERK_AUTHORIZED_PARTIES=com.ulky.app

# DATABASE (backend)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ulky
DB_USERNAME=ulky
DB_PASSWORD=change_me

# CACHE / QUEUE
REDIS_HOST=redis
REDIS_PORT=6379
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# APP
EXPO_PUBLIC_API_URL=http://localhost:8001/api/v1
```

- [ ] **Step 2: Add `.env` to all `.gitignore` files**

In `ulky_new/.gitignore`, verify `.env` is listed. In `backend/.gitignore`, verify `.env` is listed (Laravel default has it).

- [ ] **Step 3: Commit**
```bash
git add .env.example ulky_new/.gitignore backend/.gitignore
git commit -m "chore: add root .env.example, confirm .env in gitignore"
```

---

## BLOCK B — Backend: Replace Sanctum with Clerk Guard

### Task B1: Install clerk/clerk-sdk-php, remove Sanctum and Socialite

- [ ] **Step 1: Install clerk/clerk-sdk-php**
```bash
cd backend
composer require clerk/clerk-sdk-php
```
Expected: `composer.json` gains `"clerk/clerk-sdk-php"` in require.

- [ ] **Step 2: Remove Sanctum, Socialite, firebase/php-jwt**
```bash
composer remove laravel/sanctum laravel/socialite firebase/php-jwt
```
Expected: these packages removed from `composer.json`.

- [ ] **Step 3: Tighten PHP version constraint**

In `backend/composer.json`, change `"php": "^8.2"` to `"php": "^8.4"`.

- [ ] **Step 4: Commit**
```bash
git add composer.json composer.lock
git commit -m "feat(backend): install clerk/clerk-sdk-php, remove sanctum + socialite + firebase/php-jwt"
```

---

### Task B2: Add clerk_id to users table

- [ ] **Step 1: Write failing test**

File: `backend/tests/Feature/ClerkGuardTest.php`
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClerkGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    }

    public function test_request_with_valid_clerk_jwt_returns_200(): void
    {
        $user = User::factory()->create(['clerk_id' => 'user_test_abc123']);

        $response = $this->actingAs($user, 'clerk')
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_request_with_invalid_jwt_returns_401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid.jwt.token'])
            ->getJson('/api/v1/me');

        $response->assertStatus(401);
    }
}
```

- [ ] **Step 2: Run to confirm RED**
```bash
php artisan test tests/Feature/ClerkGuardTest.php
```
Expected: FAIL — `clerk_id` column missing, guard not registered, route not defined.

- [ ] **Step 3: Create migration**

File: `backend/database/migrations/2026_06_12_000001_add_clerk_id_to_users_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('clerk_id', 255)
                ->nullable()
                ->unique()
                ->after('id');
            // Index justified: every authenticated API request looks up by clerk_id
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['clerk_id']);
            $table->dropColumn('clerk_id');
        });
    }
};
```

- [ ] **Step 4: Delete conflicting migration stub**

Delete `backend/database/migrations/2026_06_11_184510_create_users_table.php` (the empty duplicate).
Delete `backend/database/migrations/2026_06_11_184416_create_personal_access_tokens_table.php` (Sanctum — no longer needed).

- [ ] **Step 5: Update `User.php` model**

File: `backend/app/Models/User.php`
- Remove `use Laravel\Sanctum\HasApiTokens;` and the trait from `use HasFactory, HasApiTokens;` → `use HasFactory;`
- Add `'clerk_id'` to `$fillable`

```php
protected $fillable = [
    'name',
    'email',
    'clerk_id',
];
```

- [ ] **Step 6: Update `UserFactory`**

In `database/factories/UserFactory.php`, add `clerk_id` to definition:
```php
'clerk_id' => null, // set explicitly in tests that need it
```

- [ ] **Step 7: Run migration**
```bash
php artisan migrate
```
Expected: new column `clerk_id` added to `users`.

- [ ] **Step 8: Commit**
```bash
git add database/migrations/ app/Models/User.php database/factories/UserFactory.php
git commit -m "feat(backend): add clerk_id to users table and model; remove sanctum personal_access_tokens"
```

---

### Task B3: Implement ClerkGuard

**Before writing:** Read `vendor/clerk/clerk-sdk-php/` to verify the exact method name for JWT verification. Look for `verifyToken`, `verifySessionToken`, or similar in the SDK.

- [ ] **Step 1: Create `app/Auth/ClerkGuard.php`**

```php
<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class ClerkGuard implements Guard
{
    use GuardHelpers;

    public function __construct(
        UserProvider $provider,
        private readonly Request $request,
    ) {}

    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user; // @phpstan-ignore-line
        }

        $token = $this->getBearerToken();
        if ($token === null) {
            return null;
        }

        try {
            // Read the exact SDK class/method from vendor/clerk/clerk-sdk-php
            // before finalizing this call. The pattern below is the intended design.
            $sdk = new \Clerk\Backend\ClerkClient([
                'secretKey' => config('services.clerk.secret_key'),
            ]);

            $payload = $sdk->verifyToken($token, [
                'authorizedParties' => explode(',', config('services.clerk.authorized_parties', '')),
                'clockSkewInMs' => 60000, // 60s tolerance for distributed clock drift
            ]);
        } catch (\Throwable) {
            return null;
        }

        $clerkId = $payload->sub ?? null;
        if ($clerkId === null) {
            return null;
        }

        // find-or-create: webhook may not have provisioned the local mirror yet
        $this->user = User::firstOrCreate(
            ['clerk_id' => $clerkId],
            ['name' => 'Utilisateur', 'email' => $clerkId . '@clerk.placeholder'],
        );

        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        return false; // stateless guard
    }

    private function getBearerToken(): ?string
    {
        $header = $this->request->header('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }
        return substr($header, 7);
    }
}
```

- [ ] **Step 2: Register guard in `AppServiceProvider`**

File: `backend/app/Providers/AppServiceProvider.php`
```php
use App\Auth\ClerkGuard;
use Illuminate\Support\Facades\Auth;

public function boot(): void
{
    Auth::extend('clerk', function ($app, $name, array $config): ClerkGuard {
        return new ClerkGuard(
            Auth::createUserProvider($config['provider']),
            $app['request'],
        );
    });
}
```

- [ ] **Step 3: Register guard in `config/auth.php`**

In the `guards` array:
```php
'clerk' => [
    'driver' => 'clerk',
    'provider' => 'users',
],
```

Set as the API default:
```php
'api' => [
    'driver' => 'clerk',
    'provider' => 'users',
],
```

- [ ] **Step 4: Update `config/services.php`**

```php
'clerk' => [
    'secret_key' => env('CLERK_SECRET_KEY'),
    'publishable_key' => env('EXPO_PUBLIC_CLERK_PUBLISHABLE_KEY'),
    'authorized_parties' => env('CLERK_AUTHORIZED_PARTIES', 'com.ulky.app'),
    'jwks_url' => env('CLERK_JWKS_URL'),
],
```

- [ ] **Step 5: Create `MeController`**

File: `backend/app/Http/Controllers/Api/MeController.php`
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'clerk_id' => $user->clerk_id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'meta' => [],
        ]);
    }
}
```

- [ ] **Step 6: Update `routes/api.php`**

```php
<?php

use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('status', [StatusController::class, 'index']);

    Route::middleware('auth:clerk')->group(function () {
        Route::get('me', [MeController::class, 'show']);
    });
});
```

- [ ] **Step 7: Run tests — confirm GREEN**
```bash
php artisan test tests/Feature/ClerkGuardTest.php
```
Expected: 3 passing.

- [ ] **Step 8: Commit**
```bash
git add app/Auth/ClerkGuard.php app/Providers/AppServiceProvider.php config/auth.php config/services.php app/Http/Controllers/Api/MeController.php routes/api.php
git commit -m "feat(backend): implement Clerk JWT guard, /api/v1/me endpoint"
```

---

### Task B4: Status endpoint

- [ ] **Step 1: Write failing test**

File: `backend/tests/Feature/StatusTest.php`
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class StatusTest extends TestCase
{
    public function test_status_returns_200_with_envelope(): void
    {
        $response = $this->getJson('/api/v1/status');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['status', 'version', 'environment'], 'meta']);
    }

    public function test_status_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/status');
        $response->assertStatus(200);
    }
}
```

- [ ] **Step 2: Run to confirm RED**
```bash
php artisan test tests/Feature/StatusTest.php
```
Expected: FAIL — route exists but controller missing.

- [ ] **Step 3: Create `StatusController`**

File: `backend/app/Http/Controllers/Api/StatusController.php`
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'version' => config('app.version', '0.1.0'),
                'environment' => config('app.env'),
            ],
            'meta' => [],
        ]);
    }
}
```

- [ ] **Step 4: Run to confirm GREEN**
```bash
php artisan test tests/Feature/StatusTest.php
```
Expected: 2 passing.

- [ ] **Step 5: Run full suite**
```bash
php artisan test
vendor/bin/pint --test
```
Expected: all green, pint exit 0. Fix any pint style issues with `vendor/bin/pint`.

- [ ] **Step 6: Commit**
```bash
git add app/Http/Controllers/Api/StatusController.php tests/Feature/StatusTest.php
git commit -m "feat(backend): add GET /api/v1/status health endpoint"
```

---

### Task B5: Delete legacy auth code

- [ ] **Step 1: Delete legacy files**
```bash
rm backend/app/Http/Controllers/AuthController.php
rm backend/app/Http/Controllers/Api/AuthController.php
# Delete any Google OAuth request classes if they exist
```

- [ ] **Step 2: Run full test suite to confirm nothing broke**
```bash
php artisan test
```
Expected: all green.

- [ ] **Step 3: Commit**
```bash
git commit -m "refactor(backend): remove sanctum/socialite auth controllers; clerk guard is the only auth"
```

---

## BLOCK C — Backend: PostgreSQL Configuration

### Task C1: Configure PostgreSQL for dev

- [ ] **Step 1: Update `backend/.env.example`**
```
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ulky
DB_USERNAME=ulky
DB_PASSWORD=secret
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

- [ ] **Step 2: Create `backend/.env` for local dev (not committed)**

Copy `.env.example` to `.env` and fill in real values for local PostgreSQL (or use Docker from Block G).

- [ ] **Step 3: Run migrations on PostgreSQL**
```bash
php artisan migrate:fresh
```
Expected: all migrations run cleanly on PostgreSQL.

- [ ] **Step 4: Run full test suite**

PHPUnit uses SQLite `:memory:` (from `phpunit.xml` env). Confirm `phpunit.xml` has:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```
```bash
php artisan test
```
Expected: green.

- [ ] **Step 5: Commit**
```bash
git add backend/.env.example backend/phpunit.xml
git commit -m "chore(backend): configure postgresql for dev; sqlite :memory: for tests"
```

---

## BLOCK D — Frontend: Fix Auth Architecture

### Task D1: Install missing dev dependencies

- [ ] **Step 1: Install test and lint tools**
```bash
cd ulky_new
npx expo install jest-expo @testing-library/react-native @testing-library/jest-native
npm install --save-dev eslint @typescript-eslint/eslint-plugin @typescript-eslint/parser eslint-plugin-react eslint-plugin-react-native eslint-plugin-react-hooks eslint-config-expo
```

- [ ] **Step 2: Create `jest.config.js`**
```js
module.exports = {
  preset: 'jest-expo',
  setupFilesAfterFramework: ['@testing-library/jest-native/extend-expect'],
  transformIgnorePatterns: [
    'node_modules/(?!(jest-)?react-native|@react-native|expo(nent)?|@expo(nent)?/.*|@unimodules|unimodules|sentry-expo|native-base|@sentry|nativewind|@clerk)',
  ],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1',
  },
};
```

- [ ] **Step 3: Create `.eslintrc.js`**
```js
module.exports = {
  root: true,
  extends: ['expo', 'plugin:@typescript-eslint/recommended'],
  plugins: ['@typescript-eslint'],
  rules: {
    '@typescript-eslint/no-explicit-any': 'error',
    '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
    'no-console': ['warn', { allow: ['warn', 'error'] }],
  },
};
```

- [ ] **Step 4: Add `jest` script to `package.json`**
```json
"scripts": {
  "test": "jest",
  "test:ci": "jest --ci --coverage"
}
```

- [ ] **Step 5: Commit**
```bash
git add jest.config.js .eslintrc.js package.json package-lock.json
git commit -m "chore(frontend): add jest-expo, testing-library, eslint configuration"
```

---

### Task D2: Create type and config files

- [ ] **Step 1: Create `src/types/api.ts`**
```ts
export interface ApiMeta {
  total?: number;
  current_page?: number;
  last_page?: number;
  per_page?: number;
}

export interface ApiResponse<T> {
  data: T;
  meta: ApiMeta;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}
```

- [ ] **Step 2: Create `src/constants/config.ts`**
```ts
import Constants from 'expo-constants';

type ExtraConfig = {
  clerkPublishableKey: string;
  apiUrl: string;
};

const extra = Constants.expoConfig?.extra as ExtraConfig | undefined;

export const CLERK_PUBLISHABLE_KEY = extra?.clerkPublishableKey ?? '';
export const API_URL = extra?.apiUrl ?? 'http://localhost:8001/api/v1';
```

- [ ] **Step 3: Create `app.config.ts` to replace `app.json`**

Read `app.json` to copy existing config. Create `app.config.ts`:
```ts
import { ExpoConfig } from 'expo/config';

const config: ExpoConfig = {
  name: 'ulky',
  slug: 'ulky',
  version: '1.0.0',
  orientation: 'portrait',
  // ... copy remaining fields from app.json
  extra: {
    clerkPublishableKey: process.env.EXPO_PUBLIC_CLERK_PUBLISHABLE_KEY ?? '',
    apiUrl: process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8001/api/v1',
  },
};

export default config;
```

Then delete `app.json`.

- [ ] **Step 4: Create `ulky_new/.env` (not committed)**
```
EXPO_PUBLIC_CLERK_PUBLISHABLE_KEY=pk_test_...
EXPO_PUBLIC_API_URL=http://localhost:8001/api/v1
```

- [ ] **Step 5: Commit**
```bash
git add src/types/api.ts src/constants/config.ts app.config.ts
git rm app.json
git commit -m "feat(frontend): add ApiResponse type, config constants, app.config.ts"
```

---

### Task D3: Fix Axios interceptor — critical AGENTS.md violation

**Before writing:** Check if `getClerkInstance` exists in `node_modules/@clerk/clerk-expo/`:
```bash
grep -r "getClerkInstance" node_modules/@clerk/clerk-expo/src/ 2>/dev/null | head -5
```

**If `getClerkInstance` exists:**

- [ ] **Step 1: Write failing test**

File: `__tests__/services/api.test.ts`
```ts
import { api } from '@/services/api';

jest.mock('@clerk/clerk-expo', () => ({
  getClerkInstance: jest.fn(() => ({
    session: {
      getToken: jest.fn().mockResolvedValue('mock-clerk-jwt'),
    },
  })),
}));

describe('api interceptor', () => {
  it('attaches Bearer token from Clerk on every request', async () => {
    const mockAdapter = jest.fn().mockResolvedValue({
      data: {},
      status: 200,
      headers: {},
      config: {},
    });
    api.defaults.adapter = mockAdapter;

    await api.get('/test');

    const requestConfig = mockAdapter.mock.calls[0][0];
    expect(requestConfig.headers['Authorization']).toBe('Bearer mock-clerk-jwt');
  });

  it('does not memoize the token — calls getToken on every request', async () => {
    const getToken = jest.fn()
      .mockResolvedValueOnce('token-1')
      .mockResolvedValueOnce('token-2');

    const { getClerkInstance } = require('@clerk/clerk-expo');
    (getClerkInstance as jest.Mock).mockReturnValue({ session: { getToken } });

    const mockAdapter = jest.fn().mockResolvedValue({
      data: {},
      status: 200,
      headers: {},
      config: {},
    });
    api.defaults.adapter = mockAdapter;

    await api.get('/first');
    await api.get('/second');

    expect(getToken).toHaveBeenCalledTimes(2);
  });
});
```

- [ ] **Step 2: Run to confirm RED**
```bash
npx jest __tests__/services/api.test.ts
```
Expected: FAIL — current `api.ts` uses tokenStorage, not getClerkInstance.

- [ ] **Step 3: Rewrite `src/services/api.ts`**
```ts
import axios from 'axios';
import { getClerkInstance } from '@clerk/clerk-expo';
import { API_URL } from '@/constants/config';

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  // getToken() called fresh on every request — never memoized (AGENTS.md rule)
  const clerk = getClerkInstance();
  const token = (await clerk.session?.getToken()) ?? null;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => Promise.reject(error),
);
```

**If `getClerkInstance` does NOT exist in the installed version**, use the module-level getter pattern instead:

```ts
import axios from 'axios';
import { API_URL } from '@/constants/config';

// Set by useClerkTokenProvider hook in root layout
let _getToken: (() => Promise<string | null>) | null = null;

export function setClerkTokenGetter(fn: () => Promise<string | null>): void {
  _getToken = fn;
}

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use(async (config) => {
  const token = _getToken ? await _getToken() : null;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

Then in `src/app/_layout.tsx`:
```tsx
import { useAuth } from '@clerk/expo';
import { setClerkTokenGetter } from '@/services/api';

// Inside RootLayout component:
const { getToken } = useAuth();
useEffect(() => {
  setClerkTokenGetter(getToken);
}, [getToken]);
```

Adjust the test mock accordingly.

- [ ] **Step 4: Run to confirm GREEN**
```bash
npx jest __tests__/services/api.test.ts
```
Expected: 2 passing.

- [ ] **Step 5: Commit**
```bash
git add src/services/api.ts __tests__/services/api.test.ts
git commit -m "fix(frontend): replace sanctum tokenStorage with Clerk getToken() in axios interceptor"
```

---

### Task D4: Delete legacy auth service files

- [ ] **Step 1: Delete legacy files**
```bash
rm ulky_new/src/services/auth.ts
rm ulky_new/src/services/tokenStorage.ts   # if it exists as a separate file
```

- [ ] **Step 2: Run TypeScript check**
```bash
npx tsc --noEmit
```
Fix any errors from the removed imports.

- [ ] **Step 3: Commit**
```bash
git commit -m "refactor(frontend): remove sanctum token exchange service"
```

---

### Task D5: Fix auth state — useAuth() instead of useAuthStore

- [ ] **Step 1: Update `src/store/auth.ts`**

Remove all token-related state. Keep only ephemeral UI state if needed, rename to `useUiStore`:
```ts
import { create } from 'zustand';

// Ephemeral UI state only. Auth state comes from Clerk: useAuth().isSignedIn
type UiStore = {
  isHydrating: boolean;
  setHydrating: (v: boolean) => void;
};

export const useUiStore = create<UiStore>((set) => ({
  isHydrating: true,
  setHydrating: (v) => set({ isHydrating: v }),
}));
```

- [ ] **Step 2: Update `src/app/_layout.tsx`**

```tsx
import { ClerkProvider, useAuth } from '@clerk/expo';
import * as SecureStore from 'expo-secure-store';
import { CLERK_PUBLISHABLE_KEY } from '@/constants/config';

const tokenCache = {
  getToken: (key: string) => SecureStore.getItemAsync(key),
  saveToken: (key: string, value: string) => SecureStore.setItemAsync(key, value),
  clearToken: (key: string) => SecureStore.deleteItemAsync(key),
};

function RootLayoutNav() {
  const { isLoaded } = useAuth();

  if (!isLoaded) {
    return (
      <View className="flex-1 items-center justify-center bg-white">
        <ActivityIndicator size="large" color="#10B981" />
      </View>
    );
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="(auth)" />
      <Stack.Screen name="(app)" />
    </Stack>
  );
}

export default function RootLayout() {
  return (
    <ClerkProvider publishableKey={CLERK_PUBLISHABLE_KEY} tokenCache={tokenCache}>
      <QueryClientProvider client={queryClient}>
        <RootLayoutNav />
      </QueryClientProvider>
    </ClerkProvider>
  );
}
```

- [ ] **Step 3: Update `src/app/(app)/_layout.tsx`**
```tsx
import { useAuth } from '@clerk/expo';
import { Redirect, Stack } from 'expo-router';

export default function AppLayout() {
  const { isSignedIn } = useAuth();
  if (!isSignedIn) return <Redirect href="/(auth)/login" />;
  return <Stack screenOptions={{ headerShown: false }} />;
}
```

- [ ] **Step 4: Update `src/app/(auth)/_layout.tsx`**
```tsx
import { useAuth } from '@clerk/expo';
import { Redirect, Stack } from 'expo-router';

export default function AuthLayout() {
  const { isSignedIn } = useAuth();
  if (isSignedIn) return <Redirect href="/(app)" />;
  return <Stack screenOptions={{ headerShown: false }} />;
}
```

- [ ] **Step 5: Update `src/app/(auth)/login.tsx`**

Remove `loginWithClerkToken()` call. After `setActive({ session: createdSessionId })`, Clerk sets `isSignedIn = true` automatically. The route guard in `(app)/_layout.tsx` handles the redirect. No manual navigation needed.

- [ ] **Step 6: Update `src/app/(auth)/sso-callback.tsx`**

Remove `loginWithClerkToken()` call. Only `useSSO` / `handleSSOCallback` from Clerk needed.

- [ ] **Step 7: Run TypeScript check**
```bash
npx tsc --noEmit
```
Expected: 0 errors.

- [ ] **Step 8: Commit**
```bash
git commit -m "refactor(frontend): replace useAuthStore auth state with useAuth() from Clerk"
```

---

### Task D6: Create StatusScreen and scaffold missing directories

- [ ] **Step 1: Create directory scaffold**
```bash
mkdir -p ulky_new/src/components
mkdir -p ulky_new/src/features/status
mkdir -p ulky_new/src/hooks
mkdir -p ulky_new/src/utils
touch ulky_new/src/components/.gitkeep
touch ulky_new/src/hooks/.gitkeep
touch ulky_new/src/utils/.gitkeep
```

- [ ] **Step 2: Create `src/services/status.ts`**
```ts
import { api } from '@/services/api';
import type { ApiResponse } from '@/types/api';

export type ApiStatus = {
  status: string;
  version: string;
  environment: string;
};

export async function fetchStatus(): Promise<ApiStatus> {
  const response = await api.get<ApiResponse<ApiStatus>>('/status');
  return response.data.data;
}
```

- [ ] **Step 3: Create `src/features/status/useStatus.ts`**
```ts
import { useQuery } from '@tanstack/react-query';
import { fetchStatus } from '@/services/status';

export function useStatus() {
  return useQuery({
    queryKey: ['status'],
    queryFn: fetchStatus,
    staleTime: 30_000,
  });
}
```

- [ ] **Step 4: Create `src/features/status/StatusScreen.tsx`**
```tsx
import { View, Text, ActivityIndicator } from 'react-native';
import { useStatus } from './useStatus';

export function StatusScreen() {
  const { data, isLoading, error } = useStatus();

  if (isLoading) {
    return <ActivityIndicator size="small" color="#10B981" />;
  }

  if (error) {
    return <Text className="text-red-500 text-xs">Erreur de connexion API</Text>;
  }

  return (
    <View className="p-3 rounded-lg border border-emerald-200 bg-emerald-50">
      <Text className="text-xs text-emerald-700 font-mono">API: {data?.status}</Text>
      <Text className="text-xs text-emerald-600 font-mono">
        v{data?.version} · {data?.environment}
      </Text>
    </View>
  );
}
```

- [ ] **Step 5: Add `StatusScreen` to `src/app/(app)/index.tsx`**

Add `<StatusScreen />` somewhere visible on the home screen. Import from `@/features/status/StatusScreen`.

- [ ] **Step 6: Run verification loop**
```bash
npx tsc --noEmit    # expect: 0 errors
npx eslint src/     # expect: 0 errors
npx jest            # expect: all passing
```

Fix any issues before committing.

- [ ] **Step 7: Commit**
```bash
git add src/features/ src/services/status.ts src/components/.gitkeep src/hooks/.gitkeep src/utils/.gitkeep
git commit -m "feat(frontend): add StatusScreen + directory scaffold per AGENTS.md architecture"
```

---

## BLOCK E — Docker

### Task E1: Create Docker services

- [ ] **Step 1: Create `docker/php/Dockerfile`**
```dockerfile
FROM php:8.4-fpm-alpine

RUN apk add --no-cache postgresql-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

COPY backend/ .
RUN composer run-script post-autoload-dump

RUN chown -R www-data:www-data storage bootstrap/cache
```

- [ ] **Step 2: Create `docker/nginx/default.conf`**
```nginx
server {
    listen 80;
    server_name _;
    root /var/www/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

- [ ] **Step 3: Create `docker-compose.yml`**
```yaml
version: '3.9'

services:
  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    volumes:
      - ./backend:/var/www
      - /var/www/vendor
    environment:
      APP_ENV: local
      APP_KEY: ${APP_KEY}
      APP_DEBUG: "true"
      DB_CONNECTION: pgsql
      DB_HOST: postgres
      DB_PORT: 5432
      DB_DATABASE: ulky
      DB_USERNAME: ulky
      DB_PASSWORD: secret
      REDIS_HOST: redis
      CACHE_STORE: redis
      QUEUE_CONNECTION: redis
      CLERK_SECRET_KEY: ${CLERK_SECRET_KEY}
      CLERK_JWKS_URL: ${CLERK_JWKS_URL}
      CLERK_AUTHORIZED_PARTIES: ${CLERK_AUTHORIZED_PARTIES:-com.ulky.app}
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_started

  nginx:
    image: nginx:1.27-alpine
    ports:
      - "8001:80"
    volumes:
      - ./backend/public:/var/www/public
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on: [php]

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: ulky
      POSTGRES_USER: ulky
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ulky"]
      interval: 5s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  postgres_data:
```

- [ ] **Step 4: Create backup script `docker/postgres/backup.sh`**
```bash
#!/bin/bash
set -euo pipefail
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="/backups/ulky_${TIMESTAMP}.sql.gz"
mkdir -p /backups
PGPASSWORD="${POSTGRES_PASSWORD}" pg_dump -U "${POSTGRES_USER}" "${POSTGRES_DB}" | gzip > "${BACKUP_FILE}"
echo "Backup: ${BACKUP_FILE}"
# Keep last 7 days
find /backups -name "*.sql.gz" -mtime +7 -delete
```
```bash
chmod +x docker/postgres/backup.sh
```

- [ ] **Step 5: Verify Docker stack starts**
```bash
docker compose up -d
docker compose exec php php artisan migrate
docker compose exec php php artisan test
```
Expected: migrations run, tests green.

- [ ] **Step 6: Restoration drill (exit criterion proof)**
```bash
# Create backup
docker compose exec postgres bash -c "PGPASSWORD=secret pg_dump -U ulky ulky | gzip > /tmp/test_backup.sql.gz"
docker compose cp postgres:/tmp/test_backup.sql.gz ./test_backup.sql.gz

# Restore to fresh database
docker compose exec postgres psql -U ulky -c "DROP DATABASE IF EXISTS ulky_restore; CREATE DATABASE ulky_restore;"
gunzip -c test_backup.sql.gz | docker compose exec -T postgres psql -U ulky ulky_restore

# Verify restore
docker compose exec postgres psql -U ulky ulky_restore -c "\dt"
```
Expected: all tables present in `ulky_restore`.

- [ ] **Step 7: Commit**
```bash
git add docker/ docker-compose.yml
git commit -m "feat(infra): docker compose with postgres + redis + nginx; backup script"
```

---

## BLOCK F — GitHub Actions CI

### Task F1: Create CI workflow

- [ ] **Step 1: Create `.github/workflows/ci.yml`**
```yaml
name: CI

on:
  pull_request:
    branches: [main, develop]
  push:
    branches: [main]

jobs:
  frontend:
    name: Frontend (tsc + eslint + jest)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: ulky_new
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          cache-dependency-path: ulky_new/package-lock.json
      - run: npm ci
      - name: TypeScript
        run: npx tsc --noEmit
      - name: ESLint
        run: npx eslint src/ --ext .ts,.tsx --max-warnings 0
      - name: Jest
        run: npx jest --ci

  backend:
    name: Backend (pint + phpunit)
    runs-on: ubuntu-latest
    defaults:
      run:
        working-directory: backend
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, pdo_sqlite, redis, zip
          coverage: none
      - run: composer install --prefer-dist --no-interaction
      - name: Pint style check
        run: vendor/bin/pint --test
      - name: PHPUnit
        env:
          APP_KEY: base64:dGVzdGtleXRlc3RrZXl0ZXN0a2V5dGVzdGtleXQ=
          APP_ENV: testing
          DB_CONNECTION: sqlite
          DB_DATABASE: ':memory:'
          CACHE_STORE: array
          QUEUE_CONNECTION: sync
          CLERK_SECRET_KEY: test_key_not_used_in_tests
          CLERK_AUTHORIZED_PARTIES: com.ulky.app
        run: php artisan test
```

- [ ] **Step 2: Push to a branch and open a PR to verify CI runs**

If no remote yet:
```bash
git remote add origin https://github.com/<org>/ulky.git
git push -u origin main
```
Open a PR from a feature branch. Verify both jobs appear and pass.

- [ ] **Step 3: Confirm CI is required to merge**

In GitHub: Settings → Branches → Branch protection rules → Require status checks to pass before merging → Add `Frontend (tsc + eslint + jest)` and `Backend (pint + phpunit)`.

- [ ] **Step 4: Commit**
```bash
git add .github/
git commit -m "feat(ci): github actions CI blocking on AGENTS.md verification loop"
```

---

## Exit Criterion Checklist

Run through each point and document the result before declaring Phase 0 complete:

- [ ] **Screen shows live API data in staging**
  - Open the app (Expo Go or dev build) pointed at staging API URL
  - Log in → home screen → `StatusScreen` shows `API: ok · v0.1.0 · staging`
  - Screenshot or screen recording kept as proof

- [ ] **CI is green and blocking**
  - Open a test PR with a deliberate TypeScript error → CI fails → PR cannot merge
  - Fix the error → CI passes → PR can merge
  - CI badge added to repository README

- [ ] **Database backup successfully restored**
  - Run backup script on staging PostgreSQL
  - Restore to a fresh PostgreSQL container
  - Run `php artisan migrate:status` — all migrations show as `Ran`
  - Document timestamp of the drill

---

## Blocking Decisions Before Coding

These must be resolved (or a working assumption recorded) before starting:

1. **`getClerkInstance()` in `@clerk/clerk-expo@3.4.2`** — grep `node_modules/@clerk/clerk-expo` before writing `api.ts`. If missing, use the `setClerkTokenGetter` pattern from Task D3.

2. **`clerk/clerk-sdk-php` JWT verification method** — read `vendor/clerk/clerk-sdk-php` after install to confirm exact class name and method signature before writing `ClerkGuard.php`.

3. **GitHub repository** — CI workflow requires a GitHub remote. If not yet created, create it before Block F.

4. **Staging server** — the deploy job in CI requires `STAGING_HOST`, `STAGING_USER`, `STAGING_SSH_KEY` secrets. Staging can be manual for Phase 0 (deploy job marked `if: false` until server is provisioned) but the server must be live and HTTPS-configured by exit criterion time.

5. **Clerk SMS deliverability to +241 (Gabon)** — per the spec, verify before Phase 1 begins. Not blocking for Phase 0 (no citizen-facing auth yet), but must be confirmed before Phase 1 starts.
