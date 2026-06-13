// Déclaration de type pour les imports side-effect de feuilles CSS (NativeWind/Tailwind).
// global.css est importé pour ses effets de bord (@tailwind) ; sans cette déclaration,
// TypeScript échoue avec TS2882 sur l'import.
declare module '*.css';
