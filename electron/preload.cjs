/**
 * Electron Preload Process for Kasir Pet Shop Desktop
 */

const { contextBridge } = require('electron');

contextBridge.exposeInMainWorld('desktopApp', {
  isDesktop: true,
  platform: process.platform,
  version: '1.0.0'
});
