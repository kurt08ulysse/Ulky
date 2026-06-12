const { getDefaultConfig } = require('expo/metro-config');
const { withNativeWind } = require('nativewind/metro');

const config = getDefaultConfig(__dirname);

// Sans 'web' dans cette liste, Metro ne résout pas les variantes .web.{js,ts}
// des dépendances. Le défaut Expo SDK 56 est ['ios','android'] uniquement.
config.resolver.platforms = ['ios', 'android', 'native', 'web'];

module.exports = withNativeWind(config, { input: './src/global.css' });
