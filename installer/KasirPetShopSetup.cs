using System;
using System.ComponentModel;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.IO.Compression;
using System.Reflection;
using System.Threading;
using System.Windows.Forms;
using Microsoft.Win32;

namespace KasirPetShopInstaller
{
    static class Program
    {
        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new InstallerForm());
        }
    }

    public class InstallerForm : Form
    {
        private Panel headerPanel;
        private Label titleLabel;
        private Label subtitleLabel;
        private Panel bodyPanel;

        // Step 1: Config
        private Label folderLabel;
        private TextBox folderTextBox;
        private Button browseButton;
        private CheckBox desktopShortcutCheckBox;
        private CheckBox startMenuShortcutCheckBox;
        private CheckBox launchCheckBox;
        private Button installButton;
        private Button cancelButton;

        // Step 2: Progress
        private Label progressStatusLabel;
        private ProgressBar progressBar;
        private Label progressDetailLabel;

        // Step 3: Finished
        private Label finishTitleLabel;
        private Label finishDescLabel;
        private Button finishButton;

        private BackgroundWorker worker;
        private string targetInstallDir;
        private bool createDesktop;
        private bool createStartMenu;
        private bool launchAfter;
        private string installedExePath;

        public InstallerForm()
        {
            InitializeComponent();
        }

        private void InitializeComponent()
        {
            this.Text = "Instalasi Kasir Pet Shop Desktop v1.0.0";
            this.Size = new Size(580, 430);
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.StartPosition = FormStartPosition.CenterScreen;
            this.BackColor = Color.FromArgb(255, 245, 245);
            this.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);

            try
            {
                this.Icon = Icon.ExtractAssociatedIcon(Process.GetCurrentProcess().MainModule.FileName);
            }
            catch { }

            // 1. Header Panel
            headerPanel = new Panel();
            headerPanel.Dock = DockStyle.Top;
            headerPanel.Height = 82;
            headerPanel.BackColor = Color.FromArgb(216, 140, 154); // Mauve/Rose #D88C9A

            titleLabel = new Label();
            titleLabel.Text = "🐾 Kasir Pet Shop Desktop";
            titleLabel.ForeColor = Color.White;
            titleLabel.Font = new Font("Segoe UI", 15f, FontStyle.Bold);
            titleLabel.Location = new Point(20, 14);
            titleLabel.AutoSize = true;
            headerPanel.Controls.Add(titleLabel);

            subtitleLabel = new Label();
            subtitleLabel.Text = "Sistem Kasir POS & Inventaris Pet Shop (100% Offline, Mandiri)";
            subtitleLabel.ForeColor = Color.FromArgb(253, 232, 232); // Rose soft #FDE8E8
            subtitleLabel.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);
            subtitleLabel.Location = new Point(23, 46);
            subtitleLabel.AutoSize = true;
            headerPanel.Controls.Add(subtitleLabel);

            this.Controls.Add(headerPanel);

            // 2. Body Panel
            bodyPanel = new Panel();
            bodyPanel.Dock = DockStyle.Fill;
            bodyPanel.Padding = new Padding(24, 20, 24, 20);
            this.Controls.Add(bodyPanel);

            // Controls for Setup Options
            string localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
            string defaultPath = Path.Combine(localAppData, "Programs", "KasirPetShop");

            folderLabel = new Label();
            folderLabel.Text = "Folder tujuan pemasangan aplikasi:";
            folderLabel.Font = new Font("Segoe UI", 9.5f, FontStyle.Bold);
            folderLabel.Location = new Point(24, 15);
            folderLabel.AutoSize = true;
            bodyPanel.Controls.Add(folderLabel);

            folderTextBox = new TextBox();
            folderTextBox.Text = defaultPath;
            folderTextBox.Location = new Point(24, 40);
            folderTextBox.Size = new Size(390, 26);
            bodyPanel.Controls.Add(folderTextBox);

            browseButton = new Button();
            browseButton.Text = "Telusuri...";
            browseButton.Location = new Point(424, 38);
            browseButton.Size = new Size(110, 29);
            browseButton.Click += delegate {
                using (FolderBrowserDialog fbd = new FolderBrowserDialog())
                {
                    fbd.Description = "Pilih folder instalasi Kasir Pet Shop";
                    fbd.SelectedPath = folderTextBox.Text;
                    if (fbd.ShowDialog() == DialogResult.OK)
                    {
                        folderTextBox.Text = fbd.SelectedPath;
                    }
                }
            };
            bodyPanel.Controls.Add(browseButton);

            desktopShortcutCheckBox = new CheckBox();
            desktopShortcutCheckBox.Text = "Buat Shortcut di Desktop (Layar Utama)";
            desktopShortcutCheckBox.Checked = true;
            desktopShortcutCheckBox.Location = new Point(24, 85);
            desktopShortcutCheckBox.AutoSize = true;
            bodyPanel.Controls.Add(desktopShortcutCheckBox);

            startMenuShortcutCheckBox = new CheckBox();
            startMenuShortcutCheckBox.Text = "Buat Shortcut di Start Menu Windows";
            startMenuShortcutCheckBox.Checked = true;
            startMenuShortcutCheckBox.Location = new Point(24, 115);
            startMenuShortcutCheckBox.AutoSize = true;
            bodyPanel.Controls.Add(startMenuShortcutCheckBox);

            launchCheckBox = new CheckBox();
            launchCheckBox.Text = "Jalankan Kasir Pet Shop setelah instalasi selesai";
            launchCheckBox.Checked = true;
            launchCheckBox.Location = new Point(24, 145);
            launchCheckBox.AutoSize = true;
            bodyPanel.Controls.Add(launchCheckBox);

            Label infoNote = new Label();
            infoNote.Text = "Catatan: Database dan data transaksi akan disimpan secara terpisah di folder pengguna (%LOCALAPPDATA%\\KasirPetShop), sehingga data Anda aman dan tidak akan hilang saat aplikasi diperbarui.";
            infoNote.ForeColor = Color.FromArgb(71, 85, 105);
            infoNote.Font = new Font("Segoe UI", 8.5f, FontStyle.Regular);
            infoNote.Location = new Point(24, 185);
            infoNote.Size = new Size(510, 48);
            bodyPanel.Controls.Add(infoNote);

            installButton = new Button();
            installButton.Text = "Pasang Sekarang";
            installButton.BackColor = Color.FromArgb(216, 140, 154); // #D88C9A
            installButton.ForeColor = Color.White;
            installButton.FlatStyle = FlatStyle.Flat;
            installButton.FlatAppearance.BorderSize = 0;
            installButton.Font = new Font("Segoe UI", 10f, FontStyle.Bold);
            installButton.Location = new Point(380, 250);
            installButton.Size = new Size(154, 38);
            installButton.Cursor = Cursors.Hand;
            installButton.Click += StartInstallation;
            bodyPanel.Controls.Add(installButton);

            cancelButton = new Button();
            cancelButton.Text = "Batal";
            cancelButton.Location = new Point(275, 250);
            cancelButton.Size = new Size(95, 38);
            cancelButton.Click += delegate { this.Close(); };
            bodyPanel.Controls.Add(cancelButton);

            // Progress Controls (hidden initially)
            progressStatusLabel = new Label();
            progressStatusLabel.Text = "Mempersiapkan pemasangan...";
            progressStatusLabel.Font = new Font("Segoe UI", 11f, FontStyle.Bold);
            progressStatusLabel.Location = new Point(24, 35);
            progressStatusLabel.AutoSize = true;
            progressStatusLabel.Visible = false;
            bodyPanel.Controls.Add(progressStatusLabel);

            progressBar = new ProgressBar();
            progressBar.Location = new Point(24, 75);
            progressBar.Size = new Size(510, 28);
            progressBar.Style = ProgressBarStyle.Continuous;
            progressBar.Visible = false;
            bodyPanel.Controls.Add(progressBar);

            progressDetailLabel = new Label();
            progressDetailLabel.Text = "Memulai ekstraksi komponen mandiri...";
            progressDetailLabel.ForeColor = Color.FromArgb(138, 110, 115);
            progressDetailLabel.Location = new Point(24, 115);
            progressDetailLabel.Size = new Size(510, 50);
            progressDetailLabel.Visible = false;
            bodyPanel.Controls.Add(progressDetailLabel);

            // Finish Controls (hidden initially)
            finishTitleLabel = new Label();
            finishTitleLabel.Text = "🎉 Instalasi Berhasil!";
            finishTitleLabel.ForeColor = Color.FromArgb(183, 110, 121); // #B76E79
            finishTitleLabel.Font = new Font("Segoe UI", 15f, FontStyle.Bold);
            finishTitleLabel.Location = new Point(24, 30);
            finishTitleLabel.AutoSize = true;
            finishTitleLabel.Visible = false;
            bodyPanel.Controls.Add(finishTitleLabel);

            finishDescLabel = new Label();
            finishDescLabel.Text = "Kasir Pet Shop Desktop telah berhasil dipasang di komputer Anda.\n\n" +
                                   "• Seluruh runtime PHP & Laravel 13 telah terbundel secara mandiri.\n" +
                                   "• Database SQLite telah diinisialisasi dan siap digunakan offline.\n" +
                                   "• Anda dapat membuka aplikasi kapan saja melalui shortcut Desktop atau Start Menu.";
            finishDescLabel.ForeColor = Color.FromArgb(67, 49, 51);
            finishDescLabel.Location = new Point(24, 75);
            finishDescLabel.Size = new Size(510, 120);
            finishDescLabel.Font = new Font("Segoe UI", 9.5f, FontStyle.Regular);
            finishDescLabel.Visible = false;
            bodyPanel.Controls.Add(finishDescLabel);

            finishButton = new Button();
            finishButton.Text = "Selesai";
            finishButton.BackColor = Color.FromArgb(216, 140, 154); // #D88C9A
            finishButton.ForeColor = Color.White;
            finishButton.FlatStyle = FlatStyle.Flat;
            finishButton.FlatAppearance.BorderSize = 0;
            finishButton.Font = new Font("Segoe UI", 10f, FontStyle.Bold);
            finishButton.Location = new Point(390, 240);
            finishButton.Size = new Size(144, 38);
            finishButton.Cursor = Cursors.Hand;
            finishButton.Visible = false;
            finishButton.Click += delegate {
                if (launchAfter && !string.IsNullOrEmpty(installedExePath) && File.Exists(installedExePath))
                {
                    try { Process.Start(installedExePath); } catch { }
                }
                this.Close();
            };
            bodyPanel.Controls.Add(finishButton);

            // BackgroundWorker
            worker = new BackgroundWorker();
            worker.WorkerReportsProgress = true;
            worker.DoWork += Worker_DoWork;
            worker.ProgressChanged += Worker_ProgressChanged;
            worker.RunWorkerCompleted += Worker_RunWorkerCompleted;
        }

        private void StartInstallation(object sender, EventArgs e)
        {
            targetInstallDir = folderTextBox.Text.Trim();
            if (string.IsNullOrEmpty(targetInstallDir))
            {
                MessageBox.Show("Silakan tentukan folder tujuan instalasi.", "Validasi", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            createDesktop = desktopShortcutCheckBox.Checked;
            createStartMenu = startMenuShortcutCheckBox.Checked;
            launchAfter = launchCheckBox.Checked;

            // Switch to progress UI
            folderLabel.Visible = false;
            folderTextBox.Visible = false;
            browseButton.Visible = false;
            desktopShortcutCheckBox.Visible = false;
            startMenuShortcutCheckBox.Visible = false;
            launchCheckBox.Visible = false;
            installButton.Visible = false;
            cancelButton.Visible = false;

            progressStatusLabel.Visible = true;
            progressBar.Visible = true;
            progressDetailLabel.Visible = true;

            worker.RunWorkerAsync();
        }

        private void Worker_DoWork(object sender, DoWorkEventArgs e)
        {
            // 1. Close existing app instances
            worker.ReportProgress(3, "Menutup proses Kasir Pet Shop lama jika sedang berjalan...");
            KillProcessesByName("KasirPetShop");

            // 2. Ensure target install folder exists
            worker.ReportProgress(6, "Membuat folder instalasi...");
            if (!Directory.Exists(targetInstallDir))
            {
                Directory.CreateDirectory(targetInstallDir);
            }

            // 3. Extract Embedded ZIP archive
            worker.ReportProgress(10, "Mengekstrak paket aplikasi dan runtime PHP...");
            Stream zipStream = null;

            // First check embedded resource
            Assembly assembly = Assembly.GetExecutingAssembly();
            string[] resNames = assembly.GetManifestResourceNames();
            foreach (string name in resNames)
            {
                if (name.EndsWith("app_package.zip", StringComparison.OrdinalIgnoreCase))
                {
                    zipStream = assembly.GetManifestResourceStream(name);
                    break;
                }
            }

            // Fallback: Check local zip file alongside installer
            if (zipStream == null)
            {
                string localZip = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "app_package.zip");
                if (File.Exists(localZip))
                {
                    zipStream = File.OpenRead(localZip);
                }
            }

            if (zipStream == null)
            {
                throw new Exception("Paket instalasi app_package.zip tidak ditemukan di dalam installer!");
            }

            using (zipStream)
            using (ZipArchive archive = new ZipArchive(zipStream, ZipArchiveMode.Read))
            {
                int total = archive.Entries.Count;
                int current = 0;

                foreach (ZipArchiveEntry entry in archive.Entries)
                {
                    string destinationPath = Path.GetFullPath(Path.Combine(targetInstallDir, entry.FullName));

                    if (entry.FullName.EndsWith("/") || entry.FullName.EndsWith("\\"))
                    {
                        Directory.CreateDirectory(destinationPath);
                    }
                    else
                    {
                        string dir = Path.GetDirectoryName(destinationPath);
                        if (!Directory.Exists(dir))
                        {
                            Directory.CreateDirectory(dir);
                        }

                        // Extract file
                        entry.ExtractToFile(destinationPath, true);
                    }

                    current++;
                    if (current % 100 == 0 || current == total)
                    {
                        int pct = 10 + (int)((current / (float)total) * 70);
                        worker.ReportProgress(pct, string.Format("Mengekstrak komponen ({0}/{1})...", current, total));
                    }
                }
            }

            // 4. Setup User Data Directory (%LOCALAPPDATA%\KasirPetShop)
            worker.ReportProgress(83, "Menyiapkan direktori penyimpanan data lokal...");
            string localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
            string userDataDir = Path.Combine(localAppData, "KasirPetShop");
            string dataDir = Path.Combine(userDataDir, "data");
            string backupsDir = Path.Combine(userDataDir, "backups");
            string logsDir = Path.Combine(userDataDir, "logs");
            string storageDir = Path.Combine(userDataDir, "storage");

            Directory.CreateDirectory(dataDir);
            Directory.CreateDirectory(backupsDir);
            Directory.CreateDirectory(logsDir);
            Directory.CreateDirectory(storageDir);

            // 5. Setup SQLite database without overwriting existing data
            worker.ReportProgress(87, "Memeriksa database SQLite lokal...");
            string userDbPath = Path.Combine(dataDir, "database.sqlite");
            string packagedDbPath = Path.Combine(targetInstallDir, "database", "database.sqlite");

            if (!File.Exists(userDbPath))
            {
                if (File.Exists(packagedDbPath))
                {
                    File.Copy(packagedDbPath, userDbPath, false);
                }
                else
                {
                    using (File.Create(userDbPath)) { }
                }
            }

            // 6. Run safe migration bootstrap
            worker.ReportProgress(90, "Menginisialisasi database dan master kategori...");
            string phpExe = Path.Combine(targetInstallDir, "php", "php.exe");
            string phpIni = Path.Combine(targetInstallDir, "php", "php.ini");
            string iniArg = File.Exists(phpIni) ? string.Format("-c \"{0}\" ", phpIni) : "";

            if (File.Exists(phpExe))
            {
                try
                {
                    Process p = new Process();
                    p.StartInfo = new ProcessStartInfo
                    {
                        FileName = phpExe,
                        Arguments = iniArg + "artisan app:desktop-init",
                        WorkingDirectory = targetInstallDir,
                        CreateNoWindow = true,
                        UseShellExecute = false,
                        WindowStyle = ProcessWindowStyle.Hidden
                    };
                    p.StartInfo.EnvironmentVariables["KASIR_USER_DATA_DIR"] = userDataDir;
                    p.StartInfo.EnvironmentVariables["DB_DATABASE"] = userDbPath;
                    p.Start();
                    p.WaitForExit(15000);
                }
                catch { }
            }

            // 7. Create Shortcuts
            installedExePath = Path.Combine(targetInstallDir, "KasirPetShop.exe");
            worker.ReportProgress(94, "Membuat shortcut desktop dan menu...");

            if (createDesktop)
            {
                string desktop = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
                CreateShortcut(
                    Path.Combine(desktop, "Kasir Pet Shop.lnk"),
                    installedExePath,
                    targetInstallDir,
                    "Aplikasi Kasir Pet Shop Desktop Offline"
                );
            }

            if (createStartMenu)
            {
                string startMenu = Environment.GetFolderPath(Environment.SpecialFolder.Programs);
                CreateShortcut(
                    Path.Combine(startMenu, "Kasir Pet Shop.lnk"),
                    installedExePath,
                    targetInstallDir,
                    "Aplikasi Kasir Pet Shop Desktop Offline"
                );
            }

            // 8. Register Uninstaller in Windows Registry
            worker.ReportProgress(98, "Mendaftarkan aplikasi ke Windows...");
            RegisterWindowsUninstaller(targetInstallDir, installedExePath);

            worker.ReportProgress(100, "Instalasi selesai!");
        }

        private void Worker_ProgressChanged(object sender, ProgressChangedEventArgs e)
        {
            progressBar.Value = Math.Min(100, Math.Max(0, e.ProgressPercentage));
            progressDetailLabel.Text = (e.UserState != null) ? e.UserState.ToString() : "";
        }

        private void Worker_RunWorkerCompleted(object sender, RunWorkerCompletedEventArgs e)
        {
            if (e.Error != null)
            {
                MessageBox.Show(
                    "Terjadi kesalahan saat instalasi: " + e.Error.Message,
                    "Instalasi Gagal",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error
                );
                this.Close();
                return;
            }

            // Switch to Finish UI
            progressStatusLabel.Visible = false;
            progressBar.Visible = false;
            progressDetailLabel.Visible = false;

            finishTitleLabel.Visible = true;
            finishDescLabel.Visible = true;
            finishButton.Visible = true;
        }

        private static void CreateShortcut(string shortcutPath, string targetPath, string workingDir, string description)
        {
            try
            {
                Type shellType = Type.GetTypeFromProgID("WScript.Shell");
                if (shellType != null)
                {
                    object shell = Activator.CreateInstance(shellType);
                    object shortcut = shell.GetType().InvokeMember("CreateShortcut", BindingFlags.InvokeMethod, null, shell, new object[] { shortcutPath });
                    shortcut.GetType().InvokeMember("TargetPath", BindingFlags.SetProperty, null, shortcut, new object[] { targetPath });
                    shortcut.GetType().InvokeMember("WorkingDirectory", BindingFlags.SetProperty, null, shortcut, new object[] { workingDir });
                    shortcut.GetType().InvokeMember("Description", BindingFlags.SetProperty, null, shortcut, new object[] { description });
                    shortcut.GetType().InvokeMember("IconLocation", BindingFlags.SetProperty, null, shortcut, new object[] { targetPath + ",0" });
                    shortcut.GetType().InvokeMember("Save", BindingFlags.InvokeMethod, null, shortcut, null);
                }
            }
            catch { }
        }

        private static void RegisterWindowsUninstaller(string installDir, string exePath)
        {
            try
            {
                string uninstallerExe = Path.Combine(installDir, "uninstall.exe");
                using (RegistryKey key = Registry.CurrentUser.CreateSubKey(@"Software\Microsoft\Windows\CurrentVersion\Uninstall\KasirPetShop"))
                {
                    if (key != null)
                    {
                        key.SetValue("DisplayName", "Kasir Pet Shop Desktop");
                        key.SetValue("DisplayVersion", "1.0.0");
                        key.SetValue("Publisher", "Pet Shop Solutions");
                        key.SetValue("InstallLocation", installDir);
                        key.SetValue("DisplayIcon", exePath + ",0");
                        key.SetValue("UninstallString", "\"" + uninstallerExe + "\"");
                        key.SetValue("NoModify", 1, RegistryValueKind.DWord);
                        key.SetValue("NoRepair", 1, RegistryValueKind.DWord);
                        key.SetValue("EstimatedSize", 145000, RegistryValueKind.DWord);
                    }
                }
            }
            catch { }
        }

        private static void KillProcessesByName(string name)
        {
            try
            {
                Process current = Process.GetCurrentProcess();
                foreach (Process p in Process.GetProcessesByName(name))
                {
                    if (p.Id != current.Id)
                    {
                        try
                        {
                            p.Kill();
                            p.WaitForExit(1000);
                        }
                        catch { }
                    }
                }
            }
            catch { }
        }
    }
}
