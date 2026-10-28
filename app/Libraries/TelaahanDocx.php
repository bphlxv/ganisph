<?php

namespace App\Libraries;

use DOMDocument;
use DOMXPath;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Menyiapkan template DOCX pengguna dan mengisi field telaahan penugasan.
 *
 * Template sumber memakai mail merge Word dengan tampilan «NAMA», «TANGGAL»,
 * dan seterusnya. Saat disiapkan, struktur mail merge dilepas dan placeholder
 * diubah menjadi ${NAMA}, ${TANGGAL}, ... agar dapat diisi oleh PhpWord.
 */
class TelaahanDocx
{
    private string $templatePath;
    private string $sourcePath;

    public function __construct(?string $templatePath = null, ?string $sourcePath = null)
    {
        $config = ganisph_config();

        $this->templatePath = $templatePath ?? $config->telaahanTemplate;
        $this->sourcePath   = $sourcePath ?? $config->sourceTemplate;

        $this->configureTempDir();
    }

    public function templatePath(): string
    {
        return $this->templatePath;
    }

    public function sourcePath(): string
    {
        return $this->sourcePath;
    }

    public function ensureTemplate(): void
    {
        $hasTemplate = is_file($this->templatePath);
        $hasSource   = is_file($this->sourcePath);

        if ($hasTemplate && (! $hasSource || filemtime($this->templatePath) >= filemtime($this->sourcePath))) {
            return;
        }

        if (! $hasSource) {
            if ($hasTemplate) {
                return;
            }

            throw new RuntimeException('Template sumber DOCX tidak ditemukan: ' . $this->sourcePath);
        }

        $this->convertTemplate();
    }

    /**
     * Membuat dokumen telaahan terisi untuk satu data penugasan.
     *
     * @param array<string, mixed>      $penugasan
     * @param array<string, mixed>|null $official Tidak dipakai; tanda tangan pada
     *                                             template pengguna bersifat tetap.
     */
    public function render(array $penugasan, ?array $official = null): string
    {
        $this->ensureTemplate();

        $directory = WRITEPATH . 'telaahan';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder dokumen telaahan tidak dapat dibuat.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($penugasan['nama'] ?? 'ganisph'));
        $safeName = trim((string) $safeName, '_');
        if ($safeName === '') {
            $safeName = 'ganisph';
        }

        $fileName = 'Telaahan_GANISPH_' . date('Ymd_His') . '_' . $safeName . '_' . (int) ($penugasan['id'] ?? 0) . '.docx';
        $output   = $directory . DIRECTORY_SEPARATOR . $fileName;

        $values = [
            'PERUSAHAAN_'            => $this->value($penugasan, 'perusahaan'),
            'ENTT'                   => $this->value($penugasan, 'entt'),
            'TANGGAL'                => $this->dateValue($penugasan['tanggal'] ?? null),
            'NAMA'                   => $this->value($penugasan, 'nama'),
            'NO_REG'                 => $this->value($penugasan, 'no_reg'),
            'MASA_BERLAKU'           => $this->value($penugasan, 'masa_berlaku'),
            'KUALIFIKASI1'           => $this->value($penugasan, 'kualifikasi'),
            'KUALIFIKASI'            => $this->value($penugasan, 'kualifikasi_singkat'),
            'KABKOTA'                => $this->value($penugasan, 'kabkota'),
            'PROVINSI'               => $this->value($penugasan, 'provinsi'),
            'JANGKA_WAKTU_PENUGASAN' => $this->value($penugasan, 'jangka_waktu'),
            'JENIS_IZIN1'            => $this->value($penugasan, 'jenis_izin'),
            'JENIS_IZIN'             => $this->value($penugasan, 'jenis_izin_singkat'),
            'PEMBERI_IZIN'           => $this->value($penugasan, 'pemberi_izin'),
            'NO_IZIN'                => $this->value($penugasan, 'no_izin'),
            'TANGGAL1'               => $this->dateValue($penugasan['tanggal_izin'] ?? null),
            'KAPASITAS_IZIN'         => $this->value($penugasan, 'kapasitas_izin'),
            'STATUS_GANISPH'         => $this->value($penugasan, 'status_ganisph'),
        ];

        try {
            $template = new TemplateProcessor($this->templatePath);

            foreach ($values as $key => $value) {
                $template->setValue($key, $this->escape($value));
            }

            $template->saveAs($output);
        } catch (Throwable $e) {
            @unlink($output);

            throw new RuntimeException('Dokumen telaahan gagal dibuat: ' . $e->getMessage(), 0, $e);
        }

        return $output;
    }

    private function configureTempDir(): void
    {
        $directory = WRITEPATH . 'tmp';

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder sementara aplikasi tidak dapat dibuat.');
        }

        if (! is_writable($directory)) {
            throw new RuntimeException('Folder sementara aplikasi tidak dapat ditulis.');
        }

        PhpWordSettings::setTempDir($directory);
    }

    private function convertTemplate(): void
    {
        $directory = dirname($this->templatePath);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder template tidak dapat dibuat.');
        }

        $tmp = $this->templatePath . '.tmp.' . bin2hex(random_bytes(4));

        if (! @copy($this->sourcePath, $tmp)) {
            throw new RuntimeException('Template sumber tidak dapat disalin ke folder tujuan.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);

            throw new RuntimeException('Template DOCX tidak dapat dibuka.');
        }

        try {
            $entries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entries[] = $zip->getNameIndex($index);
            }

            foreach ($entries as $entry) {
                if (! preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', (string) $entry)) {
                    continue;
                }

                $xml = $zip->getFromName($entry);
                if ($xml === false || $xml === '') {
                    continue;
                }

                $converted = $this->convertXml($xml);
                if ($converted !== null) {
                    $zip->addFromString($entry, $converted);
                }
            }
        } finally {
            $zip->close();
        }

        if (! @rename($tmp, $this->templatePath)) {
            if (! @copy($tmp, $this->templatePath)) {
                @unlink($tmp);

                throw new RuntimeException('Template DOCX tidak dapat disimpan.');
            }

            @unlink($tmp);
        }

        @chmod($this->templatePath, 0664);
    }

    private function convertXml(string $xml): ?string
    {
        $dom      = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded   = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            $converted = preg_replace_callback(
                '/«([A-Z0-9_]+)»/u',
                static fn (array $matches): string => '${' . $matches[1] . '}',
                $xml
            );

            return is_string($converted) ? $converted : null;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Lepaskan struktur mail merge, pertahankan teks hasil tampilannya.
        foreach (iterator_to_array($xpath->query('//w:fldChar')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach (iterator_to_array($xpath->query('//w:instrText')) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//w:t') as $textNode) {
            $value = (string) ($textNode->nodeValue ?? '');

            if (! str_contains($value, '«')) {
                continue;
            }

            $textNode->nodeValue = preg_replace_callback(
                '/«([A-Z0-9_]+)»/u',
                static fn (array $matches): string => '${' . $matches[1] . '}',
                $value
            );
        }

        $output = $dom->saveXML();

        return $output === false ? null : $output;
    }

    /**
     * @param array<string, mixed> $penugasan
     */
    private function value(array $penugasan, string $field): string
    {
        $value = trim((string) ($penugasan[$field] ?? ''));

        return $value === '' ? '-' : $value;
    }

    private function dateValue(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return '-';
        }

        return tanggal_id($value);
    }

    private function escape(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
