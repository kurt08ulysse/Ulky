/**
 * ULKY Design Tokens — Colors
 * Palette minimaliste + flat design pour confiance administrative
 * Accessible : tous les textes ≥ 4.5:1 contrast
 */

export const colors = {
  // Primary — Bleu confiance
  primary: {
    400: '#5B7FFF',
    500: '#2962FF',
    600: '#1F55C5',
  },

  // Status Colors
  success: {
    600: '#00A651',
  },
  warning: {
    600: '#F77F00',
  },
  error: {
    600: '#D32F2F',
  },

  // Neutral Scale
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

  // Semantic
  text: {
    primary: '#1A1A1A', // neutral-900
    secondary: '#424242', // neutral-700
    tertiary: '#757575', // neutral-600
    disabled: '#9E9E9E', // neutral-500
    inverse: '#FFFFFF',
  },

  background: {
    default: '#FFFFFF',
    subtle: '#FAFAFA', // neutral-50
    muted: '#F5F5F5', // neutral-100
  },

  border: {
    light: '#EEEEEE', // neutral-200
    default: '#E0E0E0', // neutral-300
  },

  // Functional
  focus: '#2962FF', // primary-500
  link: '#1F55C5', // primary-600
  placeholder: '#9E9E9E', // neutral-500
};
