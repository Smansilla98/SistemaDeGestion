const { getDefaultConfig } = require('expo/metro-config');

const projectRoot = __dirname;
const config = getDefaultConfig(projectRoot);

// Limitar raíces vigiladas al proyecto mobile (no todo el monorepo).
config.watchFolders = [projectRoot];

const block = [
  /\/node_modules\/.*\/node_modules\/.*/,
  /\/node_modules\/@jest\/.*/,
  /\/node_modules\/jest-.*/,
  /\/\.git\/.*/,
  /\/vendor\/.*/,
];

config.resolver.blockList = Array.isArray(config.resolver.blockList)
  ? [...config.resolver.blockList, ...block]
  : block;

module.exports = config;
