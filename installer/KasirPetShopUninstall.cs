using System;
using System.Diagnostics;
using System.IO;
using System.Windows.Forms;
using Microsoft.Win32;

namespace KasirPetShopUninstaller
{
    static class Program
    {
        [STAThread]
        static void Main(string[] args)
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            DialogResult confirm = MessageBox.Show(
                "Apakah Anda yakin ingin menghapus aplikasi Kasir Pet Shop Desktop dari komputer ini?",
                "Konfirmasi Uninstall - Kasir Pet Shop Desktop",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (confirm != DialogResult.Yes)
            {
                return;
            }

            try
            {
                // 1. Terminate running instances
                KillProcessesByName("KasirPetShop");
                KillProcessesByName("php");

                string installDir = AppDomain.CurrentDomain.BaseDirectory.TrimEnd(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar);

                // 2. Remove Desktop Shortcut
                string desktop = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
                string desktopShortcut = Path.Combine(desktop, "Kasir Pet Shop.lnk");
                if (File.Exists(desktopShortcut))
                {
                    try { File.Delete(desktopShortcut); } catch { }
                }

                // 3. Remove Start Menu Shortcut
                string startMenu = Environment.GetFolderPath(Environment.SpecialFolder.Programs);
                string startShortcut = Path.Combine(startMenu, "Kasir Pet Shop.lnk");
                if (File.Exists(startShortcut))
                {
                    try { File.Delete(startShortcut); } catch { }
                }

                // 4. Remove Registry Uninstall Key
                try
                {
                    using (RegistryKey key = Registry.CurrentUser.OpenSubKey(@"Software\Microsoft\Windows\CurrentVersion\Uninstall", true))
                    {
                        if (key != null)
                        {
                            key.DeleteSubKeyTree("KasirPetShop", false);
                        }
                    }
                }
                catch { }

                // 5. Ask user about user data directory
                string localAppData = Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData);
                string userDataDir = Path.Combine(localAppData, "KasirPetShop");

                if (Directory.Exists(userDataDir))
                {
                    DialogResult removeData = MessageBox.Show(
                        "Apakah Anda juga ingin menghapus seluruh data transaksi, produk, dan backup lokal?\n\nPilih 'Tidak' jika Anda ingin menyimpan database untuk instalasi berikutnya.",
                        "Hapus Data Penjualan?",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Question
                    );

                    if (removeData == DialogResult.Yes)
                    {
                        try { Directory.Delete(userDataDir, true); } catch { }
                    }
                }

                // 6. Delete install folder via detached self-deleting cmd script
                string batchScript = Path.Combine(Path.GetTempPath(), "uninstall_kasirpetshop.bat");
                string scriptContent = string.Format(
                    "@echo off\r\nping 127.0.0.1 -n 2 > nul\r\nrd /s /q \"{0}\"\r\ndel \"%~f0\"\r\n",
                    installDir
                );
                File.WriteAllText(batchScript, scriptContent);

                Process.Start(new ProcessStartInfo
                {
                    FileName = batchScript,
                    CreateNoWindow = true,
                    UseShellExecute = false,
                    WindowStyle = ProcessWindowStyle.Hidden
                });

                MessageBox.Show(
                    "Kasir Pet Shop Desktop telah berhasil dihapus dari komputer Anda.",
                    "Uninstall Selesai",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information
                );
            }
            catch (Exception ex)
            {
                MessageBox.Show(
                    "Terjadi kesalahan saat proses uninstall: " + ex.Message,
                    "Uninstall Gagal",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error
                );
            }
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
