// Configuration ESLint (flat config) — préréglage officiel Expo.
// https://docs.expo.dev/guides/using-eslint/
const { defineConfig } = require('eslint/config');
const expoConfig = require('eslint-config-expo/flat');

module.exports = defineConfig([
  expoConfig,
  {
    ignores: ['dist/*', 'node_modules/*', '.expo/*', 'expo-env.d.ts'],
  },
  {
    rules: {
      // UI 100% francophone : les apostrophes/guillemets dans le texte JSX sont
      // omniprésents et légitimes ; l'échappement en entités HTML nuit à la
      // lisibilité sans bénéfice réel. Règle purement stylistique désactivée.
      'react/no-unescaped-entities': 'off',
      // Faux positif connu avec axios (export par défaut + export nommé `create`).
      'import/no-named-as-default-member': 'off',
    },
  },
]);
