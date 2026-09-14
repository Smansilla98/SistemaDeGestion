const { getDefaultConfig } = require('expo/metro-config');

const projectRoot = __dirname;
const config = getDefaultConfig(projectRoot);

// No vigilar el monorepo Laravel; solo esta app.
config.watchFolders = [projectRoot];

// Importante: no bloquear "**/vendor/**" — RN usa Libraries/vendor/emitter/EventEmitter.
module.exports = config;
