# Design System & UI Plan — ULKY Phase 0-1

**Projet** : ULKY — Plateforme de digitalisation des services municipaux (Gabon)  
**Tech Stack** : Expo (React Native), NativeWind v5, Tailwind CSS v4  
**Date** : 2026-06-12  
**Phase** : 0 (Socle) & 1 (Identité et rôles)

---

## 1. Analyse Produit & Contexte

### Utilisateurs & Contexte Gabon
- **Population cible** : Citoyens, commerçants, agents municipaux, régisseurs
- **Appareils** : Mix de smartphones bas à haut de gamme ; connexion 4G/3G variable
- **Langue** : Français (majorité)
- **Accessibilité critique** : Diversité de compétences numériques

### Principes de Design
1. **Minimalisme + Flat Design** — clarté administrative, performance, confiance
2. **Accessibility FIRST** — contraste élevé, typographie grande, navigation tactile 
3. **Mobile-first** — construit pour le mobile d'abord, responsive (pas de horizontal scroll)
4. **Performance** — images optimisées (WebP), lazy loading, CLS < 0.1
5. **Localisation** — future-ready pour autres langues, dates/devise gabonaises

---

## 2. Design Tokens (Atomic Design)

### 2.1 Couleurs

**Palette primaire**
| Token | Hex | Usage | Contrastes |
|-------|-----|-------|-----------|
| `primary-600` | `#1F55C5` | Boutons principaux, liens, accents | 4.8:1 sur blanc |
| `primary-500` | `#2962FF` | Hover, focus states |  |
| `primary-400` | `#5B7FFF` | Disabled, placeholder |  |
| `success-600` | `#00A651` | Confirmations, paiement validé | 5.2:1 sur blanc |
| `warning-600` | `#F77F00` | Avertissements, états d'attente | 4.7:1 sur blanc |
| `error-600` | `#D32F2F` | Erreurs, rejets, danger | 6.1:1 sur blanc |
| `neutral-900` | `#1A1A1A` | Texte body, sémantique forte |  |
| `neutral-700` | `#424242` | Texte secondaire |  |
| `neutral-100` | `#F5F5F5` | Fond light, cartes |  |
| `neutral-50` | `#FAFAFA` | Fond page |  |

**Palette secondaire** (future : transactions, statuts)
- Accent vert (confiance) : `#00A651` pour "paiement confirmé"
- Accent orange : `#F77F00` pour "en attente"
- Accent rouge : `#D32F2F` pour "refusé"

### 2.2 Typography

| Element | Font | Size | Weight | Line-Height | Usage |
|---------|------|------|--------|-------------|-------|
| `h1` | Inter | 28–32px | 700 | 1.2 | Titres écrans (auth, accueil) |
| `h2` | Inter | 22–24px | 700 | 1.3 | Sous-titres, en-têtes sections |
| `h3` | Inter | 18–20px | 600 | 1.4 | Titres éléments |
| `body-lg` | Inter | 16px | 400 | 1.6 | Texte corps principal (default) |
| `body` | Inter | 14px | 400 | 1.6 | Texte corps texte **min accessibility** |
| `caption` | Inter | 12px | 500 | 1.4 | Labels, helper text |
| `button` | Inter | 16px | 600 | 1.4 | Texte boutons |

**Font Pairing** : Inter (sans-serif moderna, accessible, lisible sur mobile)  
**Dynamic Type (iOS)** : Support scaling texte système (min 14px base → 28px max)  
**Caractéristiques**
- Pas de texte < 12px sauf `caption` rare
- Line-height ≥ 1.5 (lisibilité)
- Letter-spacing : +0.5px sur `h1`, `h2` (clarté)

### 2.3 Spacing Scale

```
xs: 4px
sm: 8px
md: 16px
lg: 24px
xl: 32px
2xl: 48px
3xl: 64px
```

Usage :
- **Padding conteneur** : `md` (16px) à `lg` (24px)
- **Touch target spacing** : min `sm` (8px) entre éléments
- **Vertical rhythm** : `md` entre sections (`margin-y`)

### 2.4 Composants Core

#### Button

**Variants**
| Type | BG | Text | Border | Min Size | State |
|------|----|----|--------|----------|-------|
| Primary | `primary-600` | white | none | 44×44pt | hover: `primary-500`, press: scale 0.95 |
| Secondary | `neutral-100` | `primary-600` | 1px `primary-600` | 44×44pt | hover: `neutral-200`, press: scale 0.95 |
| Tertiary | transparent | `primary-600` | none | 44×44pt | hover: `neutral-50` bg, press: scale 0.95 |
| Destructive | `error-600` | white | none | 44×44pt | hover: `error-700`, press: scale 0.95 |
| Disabled | `neutral-200` | `neutral-500` | none | 44×44pt | no interaction, cursor: not-allowed |

**Loading State** : Spinner centered + text disabled, bouton unclickable

#### Input

- **Height** : 48pt (taille tactile)
- **Border** : 1px `neutral-300` (default), `primary-600` (focus), `error-600` (error)
- **Padding** : 12pt horizontal, 10pt vertical
- **Placeholder** : `neutral-500`, italic (non-label)
- **Label** : `caption` au-dessus, 8px spacing
- **Helper text** : `caption`, `neutral-700`, sous le champ
- **Error message** : rouge `error-600`, sous le champ, + icon ⚠️
- **Focus state** : outline 2pt `primary-500`, pas de remove

#### Form Field Block

```
┌─────────────────────────┐
│ Label (caption, 600)     │ ← 8px spacing
├─────────────────────────┤
│ Input field (48pt H)     │
│ placeholder text         │
├─────────────────────────┤
│ Helper text (caption)    │ ← 4px spacing
└─────────────────────────┘
```

#### Card

- **BG** : `neutral-100` ou white (si ombre)
- **Border** : 1px `neutral-200` (subtil)
- **Border-radius** : 8px
- **Padding** : `md` (16px) `lg` (24px)
- **Shadow** : subtle `0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)`
- **Spacing** : `md` (16px) entre cartes

#### Navigation (Bottom Tabs)

- **Height** : 56pt (iOS) / 56dp (Android)
- **Items** : 4–5 max (Phase 1 : Home, Profile, Paiements, Support, Profil)
- **Active indicator** : Highlight sur icône + label, color `primary-600`
- **Inactive** : `neutral-600`, plus pâle
- **Safe area** : respects notch, gesture bar

---

## 3. Pages Phase 0-1

### 3.1 Écran de Splash / Onboarding

**URL** : `/(root)/splash`  
**Type** : Écran de transition (non-interactif, 2–3 sec)  
**Critère d'affichage** : Première visite, token vide

**Layout**
```
┌─────────────────────────────────┐
│        ULKY Logo (SVG)          │ ← Centré, 120×120pt
│                                 │
│     "Digitalisation des         │ ← h2, centered, margin-top: lg
│      services municipaux"       │
│                                 │
│  Spinner or loading animation   │
└─────────────────────────────────┘
```

**Notes**
- Pas de interactions (auto-navigate après 2–3s ou token ready)
- Fond gradient subtil : white → `neutral-50`
- Respect safe area (notch, gesture bar)

---

### 3.2 Écran d'Authentification (Phone + OTP)

**URL** : `/(auth)/sign-in`  
**Types** : Deux sous-écrans (Phone, puis OTP)  
**Retour utilisateur** : Citoyen non-identifié

#### 3.2.1 Étape 1 : Téléphone

**Layout**
```
┌─────────────────────────────────┐
│                                 │
│  "Connexion sécurisée"          │ ← h2, neutral-900
│  "Identifiez-vous par           │ ← body-lg, neutral-700
│   téléphone"                    │
│                                 │ ← margin: lg
├─────────────────────────────────┤
│                                 │
│ 📱 Téléphone                    │ ← label, caption
│ ┌──────────────────────────────┐│
│ │ +241 | ________              ││ ← input, 48pt
│ │ [Placeholder: 06 XX XX XX]   ││
│ └──────────────────────────────┘│
│ Entrez votre numéro (10 chiffres)│ ← helper text
│                                 │ ← margin: md
│ ┌──────────────────────────────┐│
│ │ Envoyer le code              │ ← button primary
│ └──────────────────────────────┘│
│                                 │
│ Vous n'avez pas de compte ?     │ ← body, neutral-700
│ [Créer un compte] →             │ ← link tertiary
│                                 │
└─────────────────────────────────┘
```

**Comportement**
- Validation au blur : `+241\d{8,9}` (Gabon 8–9 chiffres)
- Submit : disabled si vide ou format invalide
- Loading : spinner on button, disable during request
- Erreur réseau : toast rouge avec retry

#### 3.2.2 Étape 2 : OTP

**Layout**
```
┌─────────────────────────────────┐
│                                 │
│  "Confirmez votre identité"     │ ← h2, neutral-900
│  "Un code de 6 chiffres a été   │ ← body-lg, neutral-700
│   envoyé à +241 06 XX XX XX"   │
│                                 │ ← margin: lg
├─────────────────────────────────┤
│                                 │
│ Code OTP (6 chiffres)           │ ← label, caption
│ ┌─┐ ┌─┐ ┌─┐ ┌─┐ ┌─┐ ┌─┐       │ ← 6 inputs 32×48pt
│ │ │ │ │ │ │ │ │ │ │ │ │       │
│ └─┘ └─┘ └─┘ └─┘ └─┘ └─┘       │
│                                 │
│ Entrez les 6 chiffres           │ ← caption, neutral-700
│                                 │ ← margin: md
│ ┌──────────────────────────────┐│
│ │ Vérifier                     │ ← button primary
│ └──────────────────────────────┘│
│                                 │
│ Code expiré ou erroné ?         │ ← body-sm, neutral-700
│ [Renvoyer le code] →            │ ← link tertiary, disabled 30s
│                                 │
│ Numéro incorrect ? [Modifier] → │ ← link tertiary
│                                 │
└─────────────────────────────────┘
```

**Comportement**
- Inputs OTP auto-focus, auto-tab (UX native)
- Submit : auto-submit si 6 chiffres remplis (optionnel : bouton aussi)
- Timer 30s avant "Renvoyer" clickable (disabled après)
- Erreur OTP : highlight field + message rouge
- Max tentatives : 3 → message d'attente 10 min

#### 3.2.3 Étape 3 : Profil (Optionnel Phase 0)

Si user n'a pas de profil local (webhook pas encore passé) : formulaire minimal

**Layout**
```
┌─────────────────────────────────┐
│  "Complétez votre profil"       │ ← h2
│                                 │
│ Prénom                          │ ← label
│ ┌──────────────────────────────┐│
│ │ [Prénom]                     ││ ← input
│ └──────────────────────────────┘│
│                                 │
│ Nom                             │ ← label
│ ┌──────────────────────────────┐│
│ │ [Nom]                        ││ ← input
│ └──────────────────────────────┘│
│                                 │
│ Email (optionnel)               │ ← label
│ ┌──────────────────────────────┐│
│ │ [Email]                      ││ ← input
│ └──────────────────────────────┘│
│                                 │
│ Type de contribuable             │ ← label
│ ┌─────────────────────────────── ┐
│ ◉ Particulier                    │ ← radio
│ ○ Commerçant                     │ ← radio
│ └─────────────────────────────── ┘
│                                 │
│ ┌──────────────────────────────┐│
│ │ Terminer l'inscription       │ ← button primary
│ └──────────────────────────────┘│
│                                 │
└─────────────────────────────────┘
```

---

### 3.3 Écran d'Accueil / Dashboard Citoyen

**URL** : `/(app)/(tabs)/` (index)  
**Type** : Écran principal  
**Critère d'accès** : Token valide (user authentifié)

**Layout - Header Section**
```
┌─────────────────────────────────┐
│ ☰ Menu  ULKY     🔔 Notif    │ ← navbar minimal (Phase 0 : pas drawer)
├─────────────────────────────────┤
│                                 │
│ "Bienvenue, Marcellin"          │ ← h2, neutral-900
│ "Mardi 12 juin 2026"            │ ← body, neutral-700
│                                 │ ← margin: md
└─────────────────────────────────┘
```

**Layout - Quick Actions (Cards)**
```
┌─────────────────────────────────┐
│ ┌─────────────┐ ┌─────────────┐ │
│ │   Payer     │ │   Mes       │ │
│ │   une taxe  │ │  Paiements  │ │
│ │     💰      │ │     📄      │ │
│ └─────────────┘ └─────────────┘ │ ← 2 cols, card style
│                                 │
│ ┌─────────────┐ ┌─────────────┐ │
│ │   Mes       │ │   Aide &    │ │
│ │  Démarches  │ │  Support    │ │
│ │     📋      │ │     ❓      │ │
│ └─────────────┘ └─────────────┘ │
│                                 │
└─────────────────────────────────┘
```

**Layout - Taxes Dues (List)**
```
┌─────────────────────────────────┐
│ "Taxes à jour"                  │ ← h3
│                                 │
│ ┌─────────────────────────────┐ │
│ │ Impôt sur le Revenu         │ ← card, left-border accent
│ │ Montant : 50 000 FCFA       │
│ │ Échéance : 30 juin 2026     │
│ │ [Payer]                     │ ← button tertiary (inline)
│ └─────────────────────────────┘ │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ Patente Commerciale         │
│ │ Montant : 25 000 FCFA       │
│ │ Échéance : 30 juin 2026     │
│ │ [Payer]                     │
│ └─────────────────────────────┘ │
│                                 │
└─────────────────────────────────┘

"Pas de taxes dues" ← state vide, centered icon + text
```

**Comportement**
- Statut "À jour" : vert, icône ✓
- Statut "En attente" : orange, icône ⏳
- Statut "En retard" : rouge, icône ⚠️
- Clic "Payer" → `/pay/[tax-id]`

---

### 3.4 Écran de Profil

**URL** : `/(app)/(tabs)/profile`  
**Type** : Détails utilisateur + actions

**Layout**
```
┌─────────────────────────────────┐
│        Avatar (initiales)       │ ← 80×80pt, bg primary-600, white text
│        Marcellin Nsebi          │ ← h2, centered
│        marcellin@example.com    │ ← body, neutral-700, centered
│                                 │ ← margin: lg
├─────────────────────────────────┤
│ "Mes informations"              │ ← h3
│                                 │
│ Téléphone                       │ ← label (caption)
│ +241 06 01 23 45                │ ← body, neutral-900
│ [Modifier]                      │ ← link tertiary (right-aligned)
│                                 │
│ Email                           │ ← label (caption)
│ marcellin@example.com           │ ← body, neutral-900
│ [Ajouter]                       │ ← link tertiary (optional)
│                                 │
│ Rôle                            │ ← label (caption)
│ Citoyen                         │ ← badge primary-600 light bg
│                                 │
│ Type de contribuable            │ ← label (caption)
│ Particulier                     │ ← body, neutral-900
│                                 │
├─────────────────────────────────┤
│ "Préférences"                   │ ← h3
│                                 │
│ ☑️ Notifications activées       │ ← toggle switch
│ ☑️ SMS de confirmation          │ ← toggle switch
│ ☐ Rapport mensuel par email     │ ← toggle switch
│                                 │
├─────────────────────────────────┤
│ "Sécurité"                      │ ← h3
│                                 │
│ [Changer mot de passe]          │ ← button secondary (future)
│ [Sessions actives]              │ ← button secondary (future)
│ [Révoquer l'accès]              │ ← button secondary (future)
│                                 │
├─────────────────────────────────┤
│ [Déconnexion]                   │ ← button destructive
│                                 │
└─────────────────────────────────┘
```

**Comportement**
- Toggles : save immédiat (no confirm button)
- Avatar clic → photo picker (Phase 5+, pour MVP pas de upload)
- "Déconnexion" : confirm modal, revoke Clerk token + clear local state

---

### 3.5 Bottom Navigation

**État Phase 0–1**
```
┌─────────────────────────────────┐
│  🏠 Accueil │ 👤 Profil │ ℹ️ Info  │
│   Active   │           │        │
└─────────────────────────────────┘
```

5 items futur (Phase 3+) :
1. **Accueil** — `/` (home, taxes à jour)
2. **Paiements** — `/payments` (historique, phase 3)
3. **Démarches** — `/applications` (phase 5)
4. **Signalements** — `/reports` (phase 6)
5. **Profil** — `/profile`

**Phase 0–1** : Garder light = Accueil + Profil (minimal viable), + Info/Help floating

---

## 4. Interaction States & Feedback

### Loading States

**Button loading**
```
[Envoyer le code]  →  [⟳ Envoi en cours...]  →  [✓ Code envoyé]
```

**List loading** : Skeleton cards (subtil)  
**Full page loading** : Spinner centré

### Error States

**Field error**
```
┌──────────────────────────────────┐
│ 📱 Téléphone                     │ ← label red
│ ┌────────────────────────────────┤ ← input border red
│ │ +241 06 XX X                   │
│ └────────────────────────────────┘
│ ⚠️ Format invalide. Ex: +241 06 XX XX XX  ← error text, red-600, caption
└──────────────────────────────────┘
```

**Toast errors** (temporary, top/bottom)
```
⚠️ Erreur de connexion. Réessayez.  ← error-600, auto-dismiss 5s
✓ Code envoyé avec succès!         ← success-600, auto-dismiss 3s
```

**Empty states**
```
📭 Aucune taxe à payer en ce moment
[Consulter les rapports]           ← button secondary
```

---

## 5. Animations & Motion

### Principles
- Duration : **150–300ms** (snappy, not laggy)
- Easing : `cubic-bezier(0.4, 0, 0.2, 1)` (Material standard)
- Avoid : decoration-only animations, `prefers-reduced-motion` respected

### Examples

**Button press**
```css
@tailwind components;
@layer components {
  @apply active:scale-95 transition-transform duration-150;
}
```

**Page transition** (Expo Router native) : Fade or slide (platform default)

**Loading spinner** : 2s full rotation, continuous  
**Error shake** : Input field on validation fail, ±2px × 200ms

---

## 6. Accessibility Checklist (CRITICAL)

- [ ] **Contrast** : All text ≥ 4.5:1 (tested with WebAIM)
- [ ] **Touch targets** : Min 44×44pt, 8pt spacing
- [ ] **Focus states** : Visible ring on tab, not removed
- [ ] **Keyboard nav** : Full keyboard support (PhoneOS/Android)
- [ ] **Labels** : All inputs have label (not placeholder-only)
- [ ] **Alt text** : All images have descriptive alt
- [ ] **Dynamic Type** : Text grows to 32px min on max scaling
- [ ] **Reduced motion** : `prefers-reduced-motion` respected
- [ ] **Screen reader** : accessibilityLabel on buttons, logical tab order
- [ ] **Color alone** : Never convey info by color (add icon/text)
- [ ] **Error location** : Error message next to field, not top only
- [ ] **Safe area** : Buttons 8pt from notch/gesture bar edges

---

## 7. Responsive Breakpoints (Mobile-first)

| Breakpoint | Width | Device |
|-----------|-------|--------|
| `sm` | 384px | Small phone (iPhone SE) |
| `md` | 412px | Standard phone (iPhone 14) |
| `lg` | 768px | Tablet (future, phase 5) |
| `xl` | 1024px | Desktop (future, admin) |

**Rule** : Single column for Phase 0–1 (no tablet yet). All content stacks vertically.

---

## 8. Performance Budget

### Targets
- **First Contentful Paint** : < 2s on 4G
- **Largest Contentful Paint** : < 3.5s on 4G
- **Cumulative Layout Shift** : < 0.1 (no jank)
- **Interaction to Paint** : < 100ms

### Optimizations
- **Images** : WebP (with PNG fallback), lazy load below fold
- **Fonts** : Variable fonts (Inter V), subset: Latin-ext
- **Code splitting** : Route-based (Expo Router native)
- **State** : Zustand (minimal re-renders vs Redux)
- **HTTP** : React Query caching, avoid waterfall requests

---

## 9. Implementation Roadmap

### Week 1 (Phase 0 Foundation)
- [ ] Design tokens in `src/theme/colors.ts`, `src/theme/spacing.ts`
- [ ] Tailwind config (NativeWind) + custom utilities
- [ ] Base components : Button, Input, Card, Badge (Storybook optional)
- [ ] Splash screen layout
- [ ] Navigation structure (Bottom Tabs)

### Week 2 (Phase 1 Auth)
- [ ] Auth screens : Phone + OTP (Clerk integration)
- [ ] Form validation + error states
- [ ] Loading & error feedback
- [ ] Accessibility audit (contrast, focus, keyboard nav)

### Week 3 (Phase 1 Complete)
- [ ] Home dashboard + Tax list
- [ ] Profile screen
- [ ] E2E test flows (signup → home → profile)
- [ ] Figma handoff for dev (if design tool used)

---

## 10. Design Decisions Log

| Decision | Rationale | Status |
|----------|-----------|--------|
| Minimalism + Flat Design | Confiance adminiss, perf mobile low-end, accessible | ✓ Approved |
| Phone auth (no email) | Pénétration email basse au Gabon | Pending Clerk SMS test |
| Bottom navigation (5 items) | Mobile UX best practice, thumb reach | ✓ Approved |
| Clerk guard + local mirror | No API endpoints for auth, webhook idempotency | ✓ Approved |
| Bleu + Vert + Rouge | Confiance (bleu), succès (vert), alerte (rouge) | ✓ Approved |
| NativeWind v5 | Tailwind on React Native, no two styling systems | ✓ Approved |
| 44×44pt touch min | Apple HIG + WCAG WCAG 2.5.5 | ✓ Approved |

---

## 11. Assets & Resources

### Files to Create
```
src/
├── theme/
│   ├── colors.ts         ← Palette tokens
│   ├── spacing.ts        ← Scale
│   ├── typography.ts     ← Font sizes, weights, line-heights
│   └── shadows.ts        ← subtle, medium, strong
│
├── components/
│   ├── Button.tsx        ← Variants (primary, secondary, etc.)
│   ├── Input.tsx         ← Text input, validation, error
│   ├── Card.tsx          ← Base card component
│   ├── Badge.tsx         ← Status badges
│   ├── BottomTabs.tsx    ← Navigation
│   ├── Toast.tsx         ← Error/success feedback
│   └── Spinner.tsx       ← Loading indicator
│
├── screens/
│   ├── SplashScreen.tsx
│   ├── SignInScreen.tsx
│   ├── SignInOTPScreen.tsx
│   ├── HomeScreen.tsx
│   └── ProfileScreen.tsx
│
└── styles/
    └── global.css        ← Tailwind directives, custom utilities
```

### Figma (Optional for Team)
- Component library : Button, Input, Card, etc.
- Screen prototypes : Auth flow, Home, Profile
- Responsive demo : sm (384px) viewport

### Colors Reference (Copy-paste for Tailwind)
```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          400: '#5B7FFF',
          500: '#2962FF',
          600: '#1F55C5',
        },
        success: { 600: '#00A651' },
        warning: { 600: '#F77F00' },
        error: { 600: '#D32F2F' },
        neutral: {
          50: '#FAFAFA',
          100: '#F5F5F5',
          200: '#EEEEEE',
          300: '#E0E0E0',
          500: '#9E9E9E',
          600: '#757575',
          700: '#424242',
          900: '#1A1A1A',
        },
      },
    },
  },
};
```

---

## 12. Notes & Blockers

### Blockers
1. **Clerk SMS routing** (Decision 2) : Délivrabilité SMS +241 ? Coût ? → A vérifier avant Phase 1 code
2. **Souveraineté données** (Decision 5) : Vérifier conformité CNPDCP + Clerk TOS → A trancher avant prod

### Open Questions
- Avatar upload : Phase 0–1 ou Phase 5+ ?
- Email mandatory or optional in signup ? (Hypothesis : optional, phone primary)
- Support channel : In-app chat (Phase 5+) ou lien external ?

### Future Screens (Phase 3+)
- **Payment flow** : Tax select → MobileNumber choice → Amount confirm → OTP (MobileOp) → Receipt PDF
- **Transaction list** : Filterable, searchable, with date range
- **Admin dashboard** : Revenue charts, user management, PDF export
- **Deep linking** : `ulky://tax/[id]`, `ulky://payment/[id]` (share receipts)

---

**Document version** : 1.0  
**Last updated** : 2026-06-12  
**Author** : Design System (UI/UX Pro Max)
