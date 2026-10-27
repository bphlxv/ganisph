<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Application-specific configuration for GANISPH.
 */
class Ganisph extends BaseConfig
{
    public string $appName        = 'GANISPH';
    public string $appDescription = 'Sistem Informasi Penugasan dan Telaahan GANISPH';
    public string $version        = '1.0.0';

    /**
     * Password awal untuk pengguna baru dan hasil reset password.
     */
    public string $defaultPassword = '123';

    /**
     * Batas minimal karakter untuk password baru.
     */
    public int $minPasswordLength = 6;

    /**
     * Berkas Excel yang menjadi cermin data penugasan.
     */
    public string $excelFile = WRITEPATH . 'excel/penugasan_ganisph.xlsx';

    /**
     * Template dokumen telaahan.
     */
    public string $telaahanTemplate = WRITEPATH . 'templates/telaahan_penugasan.docx';
}
