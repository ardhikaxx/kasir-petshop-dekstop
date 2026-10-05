/**
 * Electron Main Process for Kasir Pet Shop Desktop
 * Bundles Laravel + Local PHP Runtime into Windows .exe
 */

const { app, BrowserWindow } = require('electron');
const path = require('path');
const { spawn } = require('child_process');
const http = require('http');

let mainWindow = null;
let phpServerProcess = null;
const HOST = '127.0.0.1';
const PORT = 8765;
const APP_URL = `http://${HOST}:${PORT}`;

function startPhpServer(callback) {
  // Check if server is already responding
  checkServerReady(ready => {
    if (ready) {
      console.log('PHP Server already running on ' + APP_URL);
      callback();
      return;
    }

    const phpExecutable = process.env.PHP_PATH || 'php';
    const projectRoot = path.join(__dirname, '..');
    const publicDir = path.join(projectRoot, 'public');

    // 1. Run bootstrap desktop command first
    const initCmd = spawn(phpExecutable, ['artisan', 'app:desktop-init'], {
      cwd: projectRoot,
      env: process.env,
      stdio: 'inherit'
    });

    initCmd.on('close', () => {
      // 2. Start local built-in server strictly bound to 127.0.0.1
      phpServerProcess = spawn(
        phpExecutable,
        ['-S', `${HOST}:${PORT}`, '-t', publicDir],
        { cwd: projectRoot, env: process.env }
      );

      phpServerProcess.stdout.on('data', data => console.log(`[PHP]: ${data}`));
      phpServerProcess.stderr.on('data', data => console.error(`[PHP ERR]: ${data}`));

      // Poll until server responds
      let attempts = 0;
      const interval = setInterval(() => {
        attempts++;
        checkServerReady(isUp => {
          if (isUp || attempts > 20) {
            clearInterval(interval);
            callback();
          }
        });
      }, 300);
    });
  });
}

function checkServerReady(cb) {
  const req = http.get(APP_URL, () => cb(true));
  req.on('error', () => cb(false));
  req.setTimeout(500, () => {
    req.destroy();
    cb(false);
  });
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1366,
    height: 768,
    minWidth: 1280,
    minHeight: 720,
    title: 'Kasir Pet Shop Desktop - Offline POS',
    backgroundColor: '#0f172a',
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.cjs'),
      nodeIntegration: false,
      contextIsolation: true
    }
  });

  mainWindow.loadURL(APP_URL);

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

app.whenReady().then(() => {
  startPhpServer(() => {
    createWindow();
  });

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  if (phpServerProcess) {
    phpServerProcess.kill();
  }
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('will-quit', () => {
  if (phpServerProcess) {
    phpServerProcess.kill();
  }
});
