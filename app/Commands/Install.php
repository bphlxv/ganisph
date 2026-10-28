<?php

namespace App\Commands;

use App\Libraries\PenugasanExcel;
use App\Libraries\TelaahanDocx;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Menyiapkan aplikasi GANISPH pada instalasi baru.
 *
 * Perintah ini aman dijalankan berulang: migrasi bersifat inkremental,
 * seeder hanya dijalankan saat tabel pengguna masih kosong, dan berkas
 * Excel/template hanya dibuat bila belum tersedia.
 */
class Install extends BaseCommand
{
    protected $group       = 'GANISPH';
    protected $name        = 'ganisph:install';
    protected $description = 'Menyiapkan database, akun admin awal, folder, Excel, dan template telaahan GANISPH.';
    protected $usage       = 'ganisph:install';

    public function run(array $params)
    {
        CLI::write('GANISPH installer', 'yellow');
        CLI::newLine();

        if (! $this->checkExtensions()) {
            return EXIT_ERROR;
        }

        $this->ensureDirectories();
        $this->runMigrations();
        $this->seedAdmin();
        $this->prepareFiles();

        $config = ganisph_config();

        CLI::newLine();
        CLI::write('Instalasi GANISPH selesai.', 'green');
        CLI::write('URL aplikasi   : ' . site_url('/'));
        CLI::write('Akun admin     : Supriadi');
        CLI::write('NIP            : 199204222025061006');
        CLI::write('Password awal  : ' . $config->defaultPassword);
        CLI::newLine();
        CLI::write('Segera login dan ganti password melalui menu Pengaturan > Ganti Password.', 'yellow');
        CLI::write('Untuk produksi, set CI_ENVIRONMENT=production dan sesuaikan app.baseURL pada .env.', 'yellow');

        return EXIT_SUCCESS;
    }

    private function checkExtensions(): bool
    {
        $required = [
            'sqlite3'   => 'SQLite3',
            'pdo_sqlite'=> 'PDO SQLite',
            'mbstring'  => 'Mbstring',
            'intl'      => 'Intl',
            'xmlreader' => 'XMLReader (PhpSpreadsheet)',
            'xmlwriter' => 'XMLWriter (PhpWord)',
            'zip'       => 'Zip (DOCX/XLSX)',
            'gd'        => 'GD (opsional untuk gambar)',
        ];

        $missing = [];
        foreach ($required as $extension => $label) {
            if (! extension_loaded($extension)) {
                $missing[] = $label;
            }
        }

        if ($missing !== []) {
            CLI::error('Ekstensi PHP berikut belum aktif: ' . implode(', ', $missing));
            CLI::write('Aktifkan ekstensi tersebut lalu jalankan ulang perintah ini.', 'yellow');

            return false;
        }

        return true;
    }

    private function ensureDirectories(): void
    {
        $directories = [
            WRITEPATH . 'database',
            WRITEPATH . 'excel',
            WRITEPATH . 'templates',
            WRITEPATH . 'telaahan',
            WRITEPATH . 'tmp',
            WRITEPATH . 'backups',
            WRITEPATH . 'cache',
            WRITEPATH . 'logs',
            WRITEPATH . 'session',
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
                CLI::error('Folder tidak dapat dibuat: ' . $directory);
                exit(EXIT_ERROR);
            }

            if (! is_writable($directory)) {
                @chmod($directory, 0775);
            }

            if (! is_writable($directory)) {
                CLI::error('Folder tidak dapat ditulis: ' . $directory);
                exit(EXIT_ERROR);
            }

            CLI::write('Folder siap: ' . $this->relativePath($directory), 'green');
        }
    }

    private function runMigrations(): void
    {
        try {
            $runner = service('migrations');
            $runner->clearCliMessages();
            $runner->latest();

            foreach ($runner->getCliMessages() as $message) {
                CLI::write($message);
            }

            $databasePath = config(\Config\Database::class)->default['database'] ?? null;
            if (is_string($databasePath) && is_file($databasePath)) {
                @chmod($databasePath, 0664);
            }

            CLI::write('Migrasi database selesai.', 'green');
        } catch (Throwable $e) {
            CLI::error('Migrasi gagal: ' . $e->getMessage());
            exit(EXIT_ERROR);
        }
    }

    private function seedAdmin(): void
    {
        $db = db_connect();

        try {
            $userCount = $db->table('users')->countAllResults();
        } catch (Throwable $e) {
            CLI::error('Tabel pengguna tidak dapat dibaca: ' . $e->getMessage());
            exit(EXIT_ERROR);
        }

        if ($userCount > 0) {
            CLI::write('Data pengguna sudah tersedia, seeder admin dilewati.', 'yellow');

            return;
        }

        try {
            $seeder = new \CodeIgniter\Database\Seeder(new \Config\Database());
            $seeder->setSilent(false)->call('InitialSeeder');
            CLI::write('Akun administrator awal berhasil dibuat.', 'green');
        } catch (Throwable $e) {
            CLI::error('Seeder gagal: ' . $e->getMessage());
            exit(EXIT_ERROR);
        }
    }

    private function prepareFiles(): void
    {
        try {
            $excel    = new PenugasanExcel();
            $imported = $excel->ensureFile();

            if ($imported > 0) {
                CLI::write('Data awal dari dokumen Excel diimpor: ' . number_format($imported, 0, ',', '.') . ' baris.', 'green');
            }

            CLI::write('Berkas Excel siap: ' . $this->relativePath($excel->path()), 'green');
        } catch (Throwable $e) {
            CLI::error('Berkas Excel gagal disiapkan: ' . $e->getMessage());
        }

        try {
            $docx = new TelaahanDocx();
            $docx->ensureTemplate();
            CLI::write('Template DOCX siap: ' . $this->relativePath($docx->templatePath()), 'green');
        } catch (Throwable $e) {
            CLI::error('Template DOCX gagal disiapkan: ' . $e->getMessage());
        }
    }

    private function relativePath(string $path): string
    {
        return str_replace(ROOTPATH, '', $path);
    }
}
