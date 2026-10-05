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
        private static string _logPath = @"C:\xampp\htdocs\kasir-petshop-desktop\storage\logs\launcher_debug.log";

        private static void Log(string msg)
        {
            try
            {
                File.AppendAllText(_logPath, string.Format("[{0:yyyy-MM-dd HH:mm:ss.fff}] {1}\r\n", DateTime.Now, msg));
            }
            catch { }
        }

        [STAThread]
        static void Main(string[] args)
        {
            Log("Main started.");

            // Global exception handlers
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
                Log("Mutex check: isNewInstance = " + isNewInstance);
                if (!isNewInstance)
                {
                    Log("Application is already running. Focusing/opening POS window...");
                    string existingUrl = string.Format("http://{0}:{1}/pos", Host, Port);
                    LaunchAppWindow(existingUrl);
                    return;
                }

                try
                {
                    Application.EnableVisualStyles();
                    Application.SetCompatibleTextRenderingDefault(false);

                    // 1. Locate project root directory
                    string projectDir = FindProjectDirectory();
                    Log("projectDir = " + projectDir);
                    if (string.IsNullOrEmpty(projectDir))
                    {
                        MessageBox.Show(
                            "Folder aplikasi Kasir Pet Shop tidak ditemukan.\nPastikan proyek berada di 'C:\\xampp\\htdocs\\kasir-petshop-desktop' atau folder yang sesuai.",
                            "Kasir Pet Shop Desktop - Error",
                            MessageBoxButtons.OK,
                            MessageBoxIcon.Error
                        );
                        return;
                    }

                    // 2. Locate PHP executable
                    string phpPath = FindPhpExecutable(projectDir);
                    Log("phpPath = " + phpPath);
                    if (string.IsNullOrEmpty(phpPath))
                    {
                        MessageBox.Show(
                            "PHP tidak ditemukan pada sistem komputer ini.\nPastikan XAMPP terinstall di 'C:\\xampp\\php\\php.exe' atau terdaftar di PATH environment.",
                            "Kasir Pet Shop Desktop - Error",
                            MessageBoxButtons.OK,
                            MessageBoxIcon.Error
                        );
                        return;
                    }

                    // 3. Create desktop shortcut pointing to this executable
                    string currentExe = Process.GetCurrentProcess().MainModule.FileName;
                    CreateDesktopShortcut(currentExe, projectDir);
                    Log("Desktop shortcut confirmed at Desktop.");

                    // 4. Initialize Database & Migrations silently
                    Log("Running silent app:desktop-init...");
                    RunSilentProcess(phpPath, "artisan app:desktop-init", projectDir);
                    Log("app:desktop-init finished.");

                    // 5. Register application exit handlers
                    AppDomain.CurrentDomain.ProcessExit += delegate {
                        Log("ProcessExit event received.");
                        CleanupPhp();
                    };
                    Application.ApplicationExit += delegate {
                        Log("ApplicationExit event received.");
                        CleanupPhp();
                    };

                    // 6. Start local PHP server if not already running on port 8765
                    if (!IsPortInUse(Port))
                    {
                        Log("Starting background PHP built-in server on " + Host + ":" + Port + "...");
                        string publicDir = Path.Combine(projectDir, "public");
                        _phpProcess = new Process();
                        _phpProcess.StartInfo = new ProcessStartInfo
                        {
                            FileName = phpPath,
                            Arguments = string.Format("-S {0}:{1} -t \"{2}\"", Host, Port, publicDir),
                            WorkingDirectory = projectDir,
                            CreateNoWindow = true,
                            UseShellExecute = false,
                            RedirectStandardOutput = true,
                            RedirectStandardError = true,
                            WindowStyle = ProcessWindowStyle.Hidden
                        };
                        _phpProcess.EnableRaisingEvents = true;
                        _phpProcess.OutputDataReceived += delegate(object s, DataReceivedEventArgs e) {
                            if (!string.IsNullOrEmpty(e.Data))
                            {
                                Log("PHP stdout: " + e.Data);
                            }
                        };
                        _phpProcess.ErrorDataReceived += delegate(object s, DataReceivedEventArgs e) {
                            if (!string.IsNullOrEmpty(e.Data))
                            {
                                Log("PHP stderr: " + e.Data);
                            }
                        };
                        _phpProcess.Exited += delegate {
                            Log("WARNING: PHP process exited!");
                        };

                        _phpProcess.Start();
                        _phpProcess.BeginOutputReadLine();
                        _phpProcess.BeginErrorReadLine();
                        Log("PHP process started, pid = " + _phpProcess.Id);

                        // Wait up to 3 seconds for server to respond
                        int retries = 0;
                        while (!IsPortInUse(Port) && retries < 15)
                        {
                            Thread.Sleep(200);
                            retries++;
                        }
                        Log("PHP server wait completed, retries = " + retries + ", isPortInUse = " + IsPortInUse(Port));
                    }
                    else
                    {
                        Log("Port 8765 is already listening.");
                    }

                    // 7. Setup URLs
                    string posUrl = string.Format("http://{0}:{1}/pos", Host, Port);
                    string dashboardUrl = string.Format("http://{0}:{1}/", Host, Port);
                    string productsUrl = string.Format("http://{0}:{1}/products", Host, Port);
                    string reportsUrl = string.Format("http://{0}:{1}/reports", Host, Port);

                    // 8. Setup System Tray Icon
                    Log("Setting up System Tray Icon...");
                    _trayIcon = new NotifyIcon();
                    _trayIcon.Icon = SystemIcons.Application;
                    _trayIcon.Text = "Kasir Pet Shop Desktop (Aktif)";
                    _trayIcon.Visible = true;

                    ContextMenu contextMenu = new ContextMenu();

                    MenuItem titleItem = new MenuItem("🐾 Kasir Pet Shop Desktop (Aktif)");
                    titleItem.Enabled = false;
                    contextMenu.MenuItems.Add(titleItem);

                    contextMenu.MenuItems.Add("-");

                    contextMenu.MenuItems.Add("Buka Layar Kasir (POS)", delegate {
                        LaunchAppWindow(posUrl);
                    });

                    contextMenu.MenuItems.Add("Buka Dashboard", delegate {
                        LaunchAppWindow(dashboardUrl);
                    });

                    contextMenu.MenuItems.Add("Buka Katalog Produk", delegate {
                        LaunchAppWindow(productsUrl);
                    });

                    contextMenu.MenuItems.Add("Buka Laporan Penjualan", delegate {
                        LaunchAppWindow(reportsUrl);
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
                    _trayIcon.DoubleClick += delegate {
                        LaunchAppWindow(posUrl);
                    };

                    _trayIcon.ShowBalloonTip(
                        3000,
                        "Kasir Pet Shop Desktop",
                        "Aplikasi kasir berjalan offline. Klik ganda ikon ini kapan saja untuk membuka layar kasir.",
                        ToolTipIcon.Info
                    );
                    Log("System Tray icon ready.");

                    // 9. Launch Desktop App Window
                    Log("Launching app window: " + posUrl);
                    LaunchAppWindow(posUrl);

                    // 10. Run Application message loop with ApplicationContext
                    Log("Entering Application.Run(new KasirAppContext())...");
                    Application.Run(new KasirAppContext());
                    Log("Application.Run exited.");
                }
                catch (Exception ex)
                {
                    Log("Exception in Main: " + ex.ToString());
                    MessageBox.Show(
                        "Terjadi kesalahan saat memulai aplikasi: " + ex.Message,
                        "Kasir Pet Shop Desktop - Error",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Error
                    );
                    CleanupPhp();
                }
                finally
                {
                    Log("Finally block reached, calling CleanupPhp().");
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

            string defaultPath = @"C:\xampp\htdocs\kasir-petshop-desktop";
            if (File.Exists(Path.Combine(defaultPath, "artisan"))) return defaultPath;

            return null;
        }

        private static string FindPhpExecutable(string projectDir)
        {
            string localPhp = Path.Combine(projectDir, "php", "php.exe");
            if (File.Exists(localPhp)) return localPhp;

            string xamppPhp = @"C:\xampp\php\php.exe";
            if (File.Exists(xamppPhp)) return xamppPhp;

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

        private static void RunSilentProcess(string fileName, string args, string workingDir)
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
                p.Start();
                p.WaitForExit(10000);
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
                    shortcut.GetType().InvokeMember("Save", System.Reflection.BindingFlags.InvokeMethod, null, shortcut, null);
                }
            }
            catch { }
        }

        private static void CleanupPhp()
        {
            try
            {
                Log("CleanupPhp called.");
                if (_phpProcess != null && !_phpProcess.HasExited)
                {
                    Log("Killing _phpProcess pid: " + _phpProcess.Id);
                    _phpProcess.Kill();
                    _phpProcess.Dispose();
                    _phpProcess = null;
                }
            }
            catch (Exception ex)
            {
                Log("CleanupPhp exception: " + ex.Message);
            }
        }
    }

    public class KasirAppContext : ApplicationContext
    {
        public KasirAppContext()
        {
        }
    }
}
