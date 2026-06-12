// Re-export colors for easier imports
export * from './colors';

/**
 * ULKY Design Tokens — Spacing & Layout
 * Échelle mobile-first, 8px base unit
 */

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
  '2xl': 48,
  '3xl': 64,
};

/**
 * ULKY Design Tokens — Typography
 * Inter font, min 14px body, 1.6 line-height pour lisibilité
 * Accessible : Dynamic Type support (iOS), min 44pt touch targets
 */

export const typography = {
  h1: {
    fontSize: 32,
    lineHeight: 38,
    fontWeight: '700' as const,
    letterSpacing: 0.5,
  },
  h2: {
    fontSize: 24,
    lineHeight: 31,
    fontWeight: '700' as const,
    letterSpacing: 0.5,
  },
  h3: {
    fontSize: 20,
    lineHeight: 26,
    fontWeight: '600' as const,
  },
  bodyLg: {
    fontSize: 16,
    lineHeight: 26,
    fontWeight: '400' as const,
  },
  body: {
    fontSize: 14,
    lineHeight: 22,
    fontWeight: '400' as const,
  },
  caption: {
    fontSize: 12,
    lineHeight: 16,
    fontWeight: '500' as const,
  },
  button: {
    fontSize: 16,
    lineHeight: 22,
    fontWeight: '600' as const,
  },
};

/**
 * ULKY Shadows
 * Subtil pour flat design
 */

export const shadows = {
  subtle: {
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.12,
    shadowRadius: 3,
    elevation: 1,
  },
  medium: {
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.16,
    shadowRadius: 6,
    elevation: 2,
  },
  strong: {
    shadowColor: '#000000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.20,
    shadowRadius: 12,
    elevation: 4,
  },
};

/**
 * ULKY Touch targets & Accessibility
 */

export const touch = {
  minSize: 44, // 44×44pt minimum (Apple HIG)
  minSpacing: 8, // 8pt minimum between targets
};

/**
 * ULKY Breakpoints (mobile-first)
 */

export const breakpoints = {
  sm: 384, // Small phone (iPhone SE)
  md: 412, // Standard phone (iPhone 14)
  lg: 768, // Tablet (future)
  xl: 1024, // Desktop (future)
};
