using System;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Net.Sockets;
using System.Threading;
using System.Windows.Forms;

namespace KasirPetShopLauncher
{
    static class Program
    {
        private const string Host = "127.0.0.1";
        private const int Port = 8765;
        private static Process _phpProcess = null;
        private static NotifyIcon _trayIcon = null;
        private static string _logPath = null;
        private static string _userDataDir = null;

        private static void Log(string msg)
        {
            try
            {
                if (!string.IsNullOrEmpty(_logPath))
                {
                    File.AppendAllText(_logPath, string.Format("[{0:yyyy-MM-dd HH:mm:ss.fff}] {1}\r\n", DateTime.Now, msg));
                }
            }
            catch { }
        }

        [STAThread]
        static void Main(string[] args)
        {
            // Global unhandled exception handlers
            Application.SetUnhandledExceptionMode(UnhandledExceptionMode.CatchException);
            Application.ThreadException += delegate(object s, ThreadExceptionEventArgs e) {
                Log("ThreadException: " + e.Exception.ToString());
            };
            AppDomain.CurrentDomain.UnhandledException += delegate(object s, UnhandledExceptionEventArgs e) {
                Log("UnhandledException: " + e.ExceptionObject.ToString());
            };

            bool isNewInstance = false;
            using (Mutex mutex = new Mutex(true, "KasirPetShopDesktopSingleInstanceMutex", out isNewInstance))
            {
                if (!isNewInstance)
                {
                    // Already running: bring active POS window to front
                    string existingUrl = string.Format("http://{0}:{1}/pos", Host, Port);
                    LaunchAppWindow(existingUrl);
                    return;
                }

                try
                {
                    Application.EnableVisualStyles();
                    Application.SetCompatibleTextRenderingDefault(false);

                    // 1. Locate Application Installation Directory
                    string projectDir = FindProjectDirectory();
                    if (string.IsNullOrEmpty(projectDir))
                    {
                        ShowFriendlyError(
                            "Folder instalasi Kasir Pet Shop tidak ditemukan.",
                            "Pastikan aplikasi telah diinstal dengan benar melalui installer resmi."
                        );
                        return;
                    }

                    // 2. Setup Persistent User Data Directory in %LOCALAPPDATA%\KasirPetShop
                    string localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
                    _userDataDir = Path.Combine(localAppData, "KasirPetShop");
                    string dataDir = Path.Combine(_userDataDir, "data");
                    string backupsDir = Path.Combine(_userDataDir, "backups");
                    string logsDir = Path.Combine(_userDataDir, "logs");
                    string storageDir = Path.Combine(_userDataDir, "storage");

                    Directory.CreateDirectory(dataDir);
                    Directory.CreateDirectory(backupsDir);
                    Directory.CreateDirectory(logsDir);
                    Directory.CreateDirectory(storageDir);

                    _logPath = Path.Combine(logsDir, "launcher.log");
                    Log("=== Kasir Pet Shop Desktop Started ===");
                    Log("App Directory: " + projectDir);
                    Log("User Data Directory: " + _userDataDir);

                    // 3. Setup Persistent SQLite Database
                    string userDbPath = Path.Combine(dataDir, "database.sqlite");
                    string appDefaultDb = Path.Combine(projectDir, "database", "database.sqlite");

                    if (!File.Exists(userDbPath))
                    {
                        if (File.Exists(appDefaultDb))
                        {
                            File.Copy(appDefaultDb, userDbPath, false);
                            Log("Copied initial clean database to user data directory.");
                        }
                        else
                        {
                            using (File.Create(userDbPath)) { }
                            Log("Created new empty SQLite database in user data directory.");
                        }
                    }
                    else
                    {
                        Log("Existing user database found at: " + userDbPath);
                    }

                    // 4. Locate PHP Executable
                    string phpPath = FindPhpExecutable(projectDir);
                    Log("PHP Executable: " + phpPath);
                    if (string.IsNullOrEmpty(phpPath))
                    {
                        ShowFriendlyError(
                            "Komponen PHP runtime tidak ditemukan.",
                            "Runtime mandiri aplikasi rusak atau belum terpasang. Silakan instal ulang aplikasi."
                        );
                        return;
                    }

                    string phpDir = Path.GetDirectoryName(phpPath);
                    string phpIni = Path.Combine(phpDir, "php.ini");
                    string iniArg = File.Exists(phpIni) ? string.Format("-c \"{0}\" ", phpIni) : "";

                    // 5. Ensure Desktop Shortcut exists
                    string currentExe = Process.GetCurrentProcess().MainModule.FileName;
                    CreateDesktopShortcut(currentExe, projectDir);

                    // 6. Run Safe Migrations Silently
                    Log("Running safe migration bootstrap...");
                    RunSilentProcess(phpPath, iniArg + "artisan app:desktop-init", projectDir, userDbPath, _userDataDir);
                    Log("Migration bootstrap complete.");

                    // 7. Register Process Exit Handlers
                    AppDomain.CurrentDomain.ProcessExit += delegate { CleanupPhp(); };
                    Application.ApplicationExit += delegate { CleanupPhp(); };

                    // 8. Start Local PHP Server (bound strictly to 127.0.0.1)
                    if (!IsPortInUse(Port))
                    {
                        Log("Starting PHP local server on " + Host + ":" + Port + "...");
                        string publicDir = Path.Combine(projectDir, "public");
                        _phpProcess = new Process();
                        _phpProcess.StartInfo = new ProcessStartInfo
                        {
                            FileName = phpPath,
                            Arguments = string.Format("{0}-S {1}:{2} -t \"{3}\"", iniArg, Host, Port, publicDir),
                            WorkingDirectory = projectDir,
                            CreateNoWindow = true,
                            UseShellExecute = false,
                            RedirectStandardOutput = true,
                            RedirectStandardError = true,
                            WindowStyle = ProcessWindowStyle.Hidden
                        };

                        // Inject Persistent User Data Environment Variables
                        _phpProcess.StartInfo.EnvironmentVariables["KASIR_USER_DATA_DIR"] = _userDataDir;
                        _phpProcess.StartInfo.EnvironmentVariables["DB_DATABASE"] = userDbPath;
                        _phpProcess.StartInfo.EnvironmentVariables["APP_URL"] = string.Format("http://{0}:{1}", Host, Port);

                        _phpProcess.EnableRaisingEvents = true;
                        _phpProcess.OutputDataReceived += delegate(object s, DataReceivedEventArgs e) {
                            if (!string.IsNullOrEmpty(e.Data)) Log("PHP: " + e.Data);
                        };
                        _phpProcess.ErrorDataReceived += delegate(object s, DataReceivedEventArgs e) {
                            if (!string.IsNullOrEmpty(e.Data)) Log("PHP Log: " + e.Data);
                        };
                        _phpProcess.Exited += delegate {
                            Log("WARNING: Local PHP process exited.");
                        };

                        _phpProcess.Start();
                        _phpProcess.BeginOutputReadLine();
                        _phpProcess.BeginErrorReadLine();

                        // Wait up to 3.5 seconds for server to bind
                        int retries = 0;
                        while (!IsPortInUse(Port) && retries < 18)
                        {
                            Thread.Sleep(200);
                            retries++;
                        }
                        Log("PHP server readiness confirmed. isPortInUse = " + IsPortInUse(Port));
                    }
                    else
                    {
                        Log("Port 8765 is already listening.");
                    }

                    // 9. URLs
                    string posUrl = string.Format("http://{0}:{1}/pos", Host, Port);
                    string dashboardUrl = string.Format("http://{0}:{1}/dashboard", Host, Port);
                    string productsUrl = string.Format("http://{0}:{1}/products", Host, Port);
                    string reportsUrl = string.Format("http://{0}:{1}/reports", Host, Port);

                    // 10. System Tray Icon Setup
                    _trayIcon = new NotifyIcon();
                    try
                    {
                        _trayIcon.Icon = Icon.ExtractAssociatedIcon(currentExe);
                    }
                    catch
                    {
                        _trayIcon.Icon = SystemIcons.Application;
                    }
                    _trayIcon.Text = "Kasir Pet Shop Desktop (Aktif)";
                    _trayIcon.Visible = true;

                    ContextMenu contextMenu = new ContextMenu();
                    MenuItem header = new MenuItem("🐾 Kasir Pet Shop Desktop (Aktif)");
                    header.Enabled = false;
                    contextMenu.MenuItems.Add(header);
                    contextMenu.MenuItems.Add("-");

                    contextMenu.MenuItems.Add("Buka Kasir (POS)", delegate { LaunchAppWindow(posUrl); });
                    contextMenu.MenuItems.Add("Buka Dashboard", delegate { LaunchAppWindow(dashboardUrl); });
                    contextMenu.MenuItems.Add("Buka Katalog Produk", delegate { LaunchAppWindow(productsUrl); });
                    contextMenu.MenuItems.Add("Buka Laporan Penjualan", delegate { LaunchAppWindow(reportsUrl); });
                    contextMenu.MenuItems.Add("-");

                    contextMenu.MenuItems.Add("Buka Folder Data Pengguna", delegate {
                        try { Process.Start("explorer.exe", _userDataDir); } catch { }
                    });
                    contextMenu.MenuItems.Add("-");

                    contextMenu.MenuItems.Add("Tutup Kasir Pet Shop (Keluar)", delegate {
                        Log("User clicked Tutup Kasir Pet Shop.");
                        CleanupPhp();
                        if (_trayIcon != null)
                        {
                            _trayIcon.Visible = false;
                            _trayIcon.Dispose();
                            _trayIcon = null;
                        }
                        Application.Exit();
                    });

                    _trayIcon.ContextMenu = contextMenu;
                    _trayIcon.DoubleClick += delegate { LaunchAppWindow(posUrl); };

                    _trayIcon.ShowBalloonTip(
                        3000,
                        "Kasir Pet Shop Desktop",
                        "Aplikasi berjalan offline. Klik ganda ikon ini kapan saja untuk membuka layar kasir.",
                        ToolTipIcon.Info
                    );

                    // 11. Launch Dedicated Desktop Window
                    Log("Opening POS application window: " + posUrl);
                    LaunchAppWindow(posUrl);

                    // 12. Run Application Event Loop
                    Application.Run(new KasirAppContext());
                }
                catch (Exception ex)
                {
                    Log("Exception in Main: " + ex.ToString());
                    ShowFriendlyError(
                        "Terjadi kesalahan saat memulai aplikasi Kasir Pet Shop.",
                        "Detail masalah telah disimpan di: " + (_logPath ?? "folder data aplikasi.")
                    );
                    CleanupPhp();
                }
                finally
                {
                    CleanupPhp();
                }
            }
        }

        private static string FindProjectDirectory()
        {
            string current = AppDomain.CurrentDomain.BaseDirectory.TrimEnd(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar);
            if (File.Exists(Path.Combine(current, "artisan"))) return current;

            DirectoryInfo parentInfo = Directory.GetParent(current);
            string parent = parentInfo != null ? parentInfo.FullName : null;
            if (parent != null && File.Exists(Path.Combine(parent, "artisan"))) return parent;

            string localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
            string appDataInstalled = Path.Combine(localAppData, "Programs", "KasirPetShop");
            if (File.Exists(Path.Combine(appDataInstalled, "artisan"))) return appDataInstalled;

            string cInstalled = @"C:\KasirPetShop";
            if (File.Exists(Path.Combine(cInstalled, "artisan"))) return cInstalled;

            string defaultPath = @"C:\xampp\htdocs\kasir-petshop-desktop";
            if (File.Exists(Path.Combine(defaultPath, "artisan"))) return defaultPath;

            return null;
        }

        private static string FindPhpExecutable(string projectDir)
        {
            // 1. Embedded portable PHP inside app directory
            string localPhp = Path.Combine(projectDir, "php", "php.exe");
            if (File.Exists(localPhp)) return localPhp;

            // 2. Sibling php folder
            string parentDir = Path.GetDirectoryName(projectDir);
            if (!string.IsNullOrEmpty(parentDir))
            {
                string siblingPhp = Path.Combine(parentDir, "php", "php.exe");
                if (File.Exists(siblingPhp)) return siblingPhp;
            }

            // 3. Fallback: XAMPP PHP (development)
            string xamppPhp = @"C:\xampp\php\php.exe";
            if (File.Exists(xamppPhp)) return xamppPhp;

            // 4. PATH candidates
            string envPath = Environment.GetEnvironmentVariable("PATH");
            if (envPath != null)
            {
                foreach (string p in envPath.Split(';'))
                {
                    string trimmed = p.Trim();
                    if (!string.IsNullOrEmpty(trimmed))
                    {
                        string candidate = Path.Combine(trimmed, "php.exe");
                        if (File.Exists(candidate)) return candidate;
                    }
                }
            }

            return null;
        }

        private static bool IsPortInUse(int port)
        {
            try
            {
                using (TcpClient client = new TcpClient())
                {
                    IAsyncResult result = client.BeginConnect(Host, port, null, null);
                    bool success = result.AsyncWaitHandle.WaitOne(300);
                    if (success && client.Connected)
                    {
                        client.EndConnect(result);
                        return true;
                    }
                }
            }
            catch { }
            return false;
        }

        private static void RunSilentProcess(string fileName, string args, string workingDir, string dbPath, string userDataDir)
        {
            try
            {
                Process p = new Process();
                p.StartInfo = new ProcessStartInfo
                {
                    FileName = fileName,
                    Arguments = args,
                    WorkingDirectory = workingDir,
                    CreateNoWindow = true,
                    UseShellExecute = false,
                    WindowStyle = ProcessWindowStyle.Hidden
                };
                if (!string.IsNullOrEmpty(dbPath)) p.StartInfo.EnvironmentVariables["DB_DATABASE"] = dbPath;
                if (!string.IsNullOrEmpty(userDataDir)) p.StartInfo.EnvironmentVariables["KASIR_USER_DATA_DIR"] = userDataDir;

                p.Start();
                p.WaitForExit(15000);
            }
            catch { }
        }

        private static Process LaunchAppWindow(string url)
        {
            string[] browserPaths = new string[]
            {
                @"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
                @"C:\Program Files\Microsoft\Edge\Application\msedge.exe",
                @"C:\Program Files\Google\Chrome\Application\chrome.exe",
                @"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
            };

            foreach (string browser in browserPaths)
            {
                if (File.Exists(browser))
                {
                    try
                    {
                        return Process.Start(new ProcessStartInfo
                        {
                            FileName = browser,
                            Arguments = string.Format("--app=\"{0}\" --window-size=1366,768", url),
                            UseShellExecute = false
                        });
                    }
                    catch { }
                }
            }

            return Process.Start(new ProcessStartInfo
            {
                FileName = url,
                UseShellExecute = true
            });
        }

        private static void CreateDesktopShortcut(string exePath, string projectDir)
        {
            try
            {
                string desktop = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
                string shortcutPath = Path.Combine(desktop, "Kasir Pet Shop.lnk");

                Type shellType = Type.GetTypeFromProgID("WScript.Shell");
                if (shellType != null)
                {
                    object shell = Activator.CreateInstance(shellType);
                    object shortcut = shell.GetType().InvokeMember("CreateShortcut", System.Reflection.BindingFlags.InvokeMethod, null, shell, new object[] { shortcutPath });
                    shortcut.GetType().InvokeMember("TargetPath", System.Reflection.BindingFlags.SetProperty, null, shortcut, new object[] { exePath });
                    shortcut.GetType().InvokeMember("WorkingDirectory", System.Reflection.BindingFlags.SetProperty, null, shortcut, new object[] { projectDir });
                    shortcut.GetType().InvokeMember("Description", System.Reflection.BindingFlags.SetProperty, null, shortcut, new object[] { "Aplikasi Kasir Pet Shop Desktop Offline" });
                    shortcut.GetType().InvokeMember("IconLocation", System.Reflection.BindingFlags.SetProperty, null, shortcut, new object[] { exePath + ",0" });
                    shortcut.GetType().InvokeMember("Save", System.Reflection.BindingFlags.InvokeMethod, null, shortcut, null);
                }
            }
            catch { }
        }

        private static void ShowFriendlyError(string title, string suggestion)
        {
            MessageBox.Show(
                title + "\n\n" + suggestion,
                "Kasir Pet Shop Desktop",
                MessageBoxButtons.OK,
                MessageBoxIcon.Warning
            );
        }

        private static void CleanupPhp()
        {
            try
            {
                Log("CleanupPhp called.");
                if (_phpProcess != null && !_phpProcess.HasExited)
                {
                    Log("Terminating PHP process pid: " + _phpProcess.Id);
                    _phpProcess.Kill();
                    _phpProcess.Dispose();
                    _phpProcess = null;
                }
            }
            catch { }
        }
    }

    public class KasirAppContext : ApplicationContext
    {
        public KasirAppContext()
        {
        }
    }
}
