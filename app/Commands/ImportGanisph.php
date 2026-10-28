<?php

namespace App\Commands;

use App\Libraries\PenugasanExcel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Mengimpor data penugasan dari berkas Excel sumber pengguna.
 */
class ImportGanisph extends BaseCommand
{
    protected $group       = 'GANISPH';
    protected $name        = 'ganisph:import';
    protected $description = 'Mengimpor data dari dokumen/data_penugasan_ganisph.xlsx ke database.';
    protected $usage       = 'ganisph:import [--replace]';
    protected $arguments   = [];
    protected $options     = [
        '--replace' => 'Kosongkan tabel penugasan terlebih dahulu, lalu impor ulang.',
    ];

    public function run(array $params)
    {
        $replace = array_key_exists('replace', $params) || CLI::getOption('replace') !== null;

        $excel = new PenugasanExcel();

        try {
            $count = $excel->importSource($replace);
        } catch (Throwable $e) {
            CLI::error('Impor gagal: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if (! $replace && $count === 0) {
            CLI::write('Tabel penugasan sudah berisi data. Gunakan --replace untuk mengimpor ulang.', 'yellow');

            return EXIT_SUCCESS;
        }

        $excel->refreshFromDatabase();

        CLI::write('Impor selesai: ' . number_format($count, 0, ',', '.') . ' baris.', 'green');
        CLI::write('Berkas Excel kerja diperbarui: ' . $excel->path());

        return EXIT_SUCCESS;
    }
}
