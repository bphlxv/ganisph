<?php

namespace App\Libraries;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

/**
 * Membuat template dan dokumen telaahan penugasan berformat DOCX.
 */
class TelaahanDocx
{
    private string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?? ganisph_config()->telaahanTemplate;
    }

    public function templatePath(): string
    {
        return $this->templatePath;
    }

    public function ensureTemplate(): void
    {
        if (is_file($this->templatePath)) {
            return;
        }

        $directory = dirname($this->templatePath);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder template tidak dapat dibuat.');
        }

        $phpWord = new PhpWord();
        $phpWord->getDocInfo()
            ->setCreator('GANISPH')
            ->setTitle('Template Telaahan Penugasan GANISPH')
            ->setDescription('Template resmi telaahan penugasan GANISPH.');

        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop'    => 1800,
            'marginBottom' => 1600,
            'marginLeft'   => 1800,
            'marginRight'  => 1800,
        ]);

        $section->addText('GANISPH', [
            'name' => 'Times New Roman',
            'size' => 16,
            'bold' => true,
            'color' => '123B6D',
            'allCaps' => true,
        ], ['alignment' => 'left', 'spaceAfter' => 0]);

        $section->addText('Sistem Informasi Penugasan dan Telaahan', [
            'name' => 'Times New Roman',
            'size' => 9,
            'color' => '4B5563',
        ], ['spaceAfter' => 40]);

        $line = $section->addText('', ['size' => 2], [
            'borderBottomSize' => 12,
            'borderBottomColor' => '123B6D',
            'spaceAfter' => 260,
        ]);

        $section->addText('TELAAHAN PENUGASAN GANISPH', [
            'name' => 'Times New Roman',
            'size' => 14,
            'bold' => true,
        ], ['alignment' => 'center', 'spaceAfter' => 40]);

        $section->addText('Nomor: ${NOMOR_TELAAHAN}', [
            'name' => 'Times New Roman',
            'size' => 11,
        ], ['alignment' => 'center', 'spaceAfter' => 260]);

        $section->addText(
            'Berdasarkan Surat Tugas Nomor ${NOMOR_SURAT} tanggal ${TANGGAL_SURAT}, telah dilakukan telaahan terhadap penugasan GANISPH dengan data sebagai berikut:',
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 200, 'lineHeight' => 1.15]
        );

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'B8C4D2',
            'cellMargin' => 90,
            'alignment' => 'center',
            'width' => 9200,
        ]);

        $this->addDataRow($table, 'Nama Pegawai', '${NAMA}');
        $this->addDataRow($table, 'NIP', '${NIP}');
        $this->addDataRow($table, 'Jabatan', '${JABATAN}');
        $this->addDataRow($table, 'Nomor Surat Tugas', '${NOMOR_SURAT}');
        $this->addDataRow($table, 'Tanggal Surat Tugas', '${TANGGAL_SURAT}');
        $this->addDataRow($table, 'Nama Kegiatan', '${KEGIATAN}');
        $this->addDataRow($table, 'Peran dalam Penugasan', '${PERAN}');
        $this->addDataRow($table, 'Penyelenggara', '${PENYELENGGARA}');
        $this->addDataRow($table, 'Lokasi', '${LOKASI}');
        $this->addDataRow($table, 'Waktu Pelaksanaan', '${PERIODE}');
        $this->addDataRow($table, 'Status', '${STATUS}');
        $this->addDataRow($table, 'Keterangan', '${KETERANGAN}');

        $section->addText('', ['size' => 4], ['spaceAfter' => 120]);

        $section->addText('Hasil Telaahan', [
            'name' => 'Times New Roman',
            'size' => 11,
            'bold' => true,
        ], ['spaceAfter' => 80]);

        $section->addText(
            'Penugasan GANISPH sebagaimana tercantum di atas telah ditelaah sesuai dengan data penugasan yang tersimpan pada sistem. ${CATATAN_TELAAHAN}',
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 220, 'lineHeight' => 1.15]
        );

        $section->addText(
            'Demikian telaahan penugasan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.',
            ['name' => 'Times New Roman', 'size' => 11],
            ['alignment' => 'both', 'spaceAfter' => 320, 'lineHeight' => 1.15]
        );

        $signature = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => 0,
            'alignment' => 'right',
            'width' => 9100,
        ]);
        $signature->addRow();
        $signature->addCell(5300);
        $cell = $signature->addCell(3800);
        $cell->addText('${TANGGAL_TELAAHAN}', [
            'name' => 'Times New Roman',
            'size' => 11,
        ], ['alignment' => 'left', 'spaceAfter' => 100]);
        $cell->addText('Pejabat yang Menelaah,', [
            'name' => 'Times New Roman',
            'size' => 11,
        ], ['alignment' => 'left', 'spaceAfter' => 500]);
        $cell->addText('${PEJABAT_NAMA}', [
            'name' => 'Times New Roman',
            'size' => 11,
            'bold' => true,
            'underline' => 'single',
        ], ['alignment' => 'left', 'spaceAfter' => 0]);
        $cell->addText('NIP. ${PEJABAT_NIP}', [
            'name' => 'Times New Roman',
            'size' => 11,
        ], ['alignment' => 'left', 'spaceAfter' => 0]);

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Dokumen Telaahan Penugasan GANISPH  |  Halaman {PAGE} dari {NUMPAGES}',
            ['name' => 'Times New Roman', 'size' => 8, 'color' => '6B7280'],
            ['alignment' => 'center']
        );

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($this->templatePath);
        @chmod($this->templatePath, 0664);
    }

    /**
     * Membuat dokumen telaahan terisi untuk satu data penugasan.
     *
     * @param array<string, mixed>      $penugasan
     * @param array<string, mixed>|null $official
     */
    public function render(array $penugasan, ?array $official = null): string
    {
        $this->ensureTemplate();

        $directory = WRITEPATH . 'telaahan';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder dokumen telaahan tidak dapat dibuat.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($penugasan['nama_pegawai'] ?? 'pegawai'));
        $fileName = 'Telaahan_GANISPH_' . date('Ymd_His') . '_' . $safeName . '_' . (int) $penugasan['id'] . '.docx';
        $output   = $directory . DIRECTORY_SEPARATOR . $fileName;

        $official = $official ?? [];
        $keterangan = trim((string) ($penugasan['keterangan'] ?? ''));
        if ($keterangan === '') {
            $keterangan = 'Tidak ada catatan tambahan.';
        }

        $catatan = ($penugasan['status'] ?? '') === 'Selesai'
            ? 'Penugasan telah selesai dilaksanakan dan seluruh data telah sesuai.'
            : 'Penugasan masih berjalan dan data yang tercantum telah sesuai dengan surat tugas.';

        $values = [
            'NOMOR_TELAAHAN'   => $this->nomorTelaahan($penugasan),
            'NOMOR_SURAT'      => (string) ($penugasan['nomor_surat'] ?? '-'),
            'TANGGAL_SURAT'    => tanggal_id($penugasan['tanggal_surat'] ?? null),
            'NAMA'             => (string) ($penugasan['nama_pegawai'] ?? '-'),
            'NIP'              => (string) ($penugasan['nip'] ?? '-'),
            'JABATAN'          => (string) ($penugasan['jabatan'] ?? '-'),
            'KEGIATAN'         => (string) ($penugasan['nama_kegiatan'] ?? '-'),
            'PERAN'            => (string) ($penugasan['peran'] ?? '-'),
            'PENYELENGGARA'    => (string) ($penugasan['penyelenggara'] ?? '-'),
            'LOKASI'           => (string) ($penugasan['lokasi'] ?? '-'),
            'PERIODE'          => periode_penugasan($penugasan['tanggal_mulai'] ?? null, $penugasan['tanggal_selesai'] ?? null),
            'STATUS'           => (string) ($penugasan['status'] ?? 'Aktif'),
            'KETERANGAN'       => $keterangan,
            'CATATAN_TELAAHAN' => $catatan,
            'TANGGAL_TELAAHAN' => tanggal_id(date('Y-m-d')),
            'PEJABAT_NAMA'     => (string) ($official['nama'] ?? 'Administrator'),
            'PEJABAT_NIP'      => (string) ($official['nip'] ?? '-'),
        ];

        try {
            $template = new TemplateProcessor($this->templatePath);
            foreach ($values as $key => $value) {
                $template->setValue($key, $this->escape($value));
            }
            $template->saveAs($output);
        } catch (\Throwable $e) {
            @unlink($output);
            throw new RuntimeException('Dokumen telaahan gagal dibuat: ' . $e->getMessage(), 0, $e);
        }

        return $output;
    }

    /**
     * @param \PhpOffice\PhpWord\Element\Table $table
     */
    private function addDataRow($table, string $label, string $placeholder): void
    {
        $table->addRow();
        $labelCell = $table->addCell(2600, ['bgColor' => 'F1F5F9']);
        $labelCell->addText($label, [
            'name' => 'Times New Roman',
            'size' => 10,
            'bold' => true,
        ], ['spaceAfter' => 0]);

        $valueCell = $table->addCell(6500);
        $valueCell->addText($placeholder, [
            'name' => 'Times New Roman',
            'size' => 10,
        ], ['spaceAfter' => 0]);
    }

    private function nomorTelaahan(array $penugasan): string
    {
        $date    = strtotime((string) ($penugasan['tanggal_surat'] ?? date('Y-m-d'))) ?: time();
        $roman   = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $month   = $roman[(int) date('n', $date) - 1];
        $year    = date('Y', $date);
        $number  = str_pad((string) ($penugasan['id'] ?? '1'), 3, '0', STR_PAD_LEFT);

        return "TEL-GANISPH/{$number}/{$month}/{$year}";
    }

    private function escape(string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $escaped = str_replace(["\r\n", "\r"], "\n", $escaped);

        return $escaped;
    }
}
