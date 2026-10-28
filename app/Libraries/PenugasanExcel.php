<?php

namespace App\Libraries;

use App\Models\PenugasanModel;
use DateTimeImmutable;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Throwable;

/**
 * Sinkronisasi dua arah antara tabel penugasan dan berkas Excel.
 *
 * Susunan kolom mengikuti berkas resmi pengguna:
 * A PERUSAHAAN, B E-NTT, C TANGGAL, D NAMA, E NO. REG, F MASA BERLAKU,
 * G KUALIFIKASI (panjang), H KUALIFIKASI (singkat), I KAB./KOTA, J PROVINSI,
 * K JANGKA WAKTU PENUGASAN, L JENIS IZIN (panjang), M JENIS IZIN (singkat),
 * N PEMBERI IZIN, O NO IZIN, P TANGGAL (izin), Q KAPASITAS IZIN,
 * R STATUS GANISPH, S KET., T NULL, U NULL.
 *
 * Kolom V (ID) ditambahkan aplikasi sebagai kunci sinkronisasi. Kolom A-U
 * dipertahankan persis seperti berkas asli.
 */
class PenugasanExcel
{
    public const SHEET_TITLE = '2025';

    /**
     * Urutan kolom A sampai V. Label duplikat dan "NULL" dipertahankan
     * agar berkas tetap sama dengan berkas sumber pengguna.
     */
    public const HEADERS = [
        'perusahaan'          => 'PERUSAHAAN',
        'entt'                => 'E-NTT',
        'tanggal'             => 'TANGGAL',
        'nama'                => 'NAMA',
        'no_reg'              => 'NO. REG',
        'masa_berlaku'        => 'MASA BERLAKU',
        'kualifikasi'         => 'KUALIFIKASI',
        'kualifikasi_singkat'  => 'KUALIFIKASI',
        'kabkota'             => 'KAB./KOTA',
        'provinsi'            => 'PROVINSI',
        'jangka_waktu'        => 'JANGKA WAKTU PENUGASAN',
        'jenis_izin'          => 'JENIS IZIN',
        'jenis_izin_singkat'  => 'JENIS IZIN',
        'pemberi_izin'        => 'PEMBERI IZIN',
        'no_izin'             => 'NO IZIN',
        'tanggal_izin'        => 'TANGGAL',
        'kapasitas_izin'      => 'KAPASITAS IZIN',
        'status_ganisph'      => 'STATUS GANISPH',
        'ket'                 => 'KET.',
        'nilai_t'             => 'NULL',
        'catatan_u'           => 'NULL',
        'id'                  => 'ID',
    ];

    public const DATE_FIELDS = ['tanggal', 'tanggal_izin'];

    /**
     * Kolom wajib saat mengimpor baris baru.
     */
    private const REQUIRED_FIELDS = [
        'perusahaan'         => 'Perusahaan',
        'entt'               => 'E-NTT',
        'tanggal'            => 'Tanggal',
        'nama'               => 'Nama',
        'no_reg'             => 'No. Reg',
        'masa_berlaku'       => 'Masa Berlaku',
        'kualifikasi'        => 'Kualifikasi',
        'kualifikasi_singkat'=> 'Kualifikasi Singkat',
        'kabkota'            => 'Kab./Kota',
        'provinsi'           => 'Provinsi',
        'jangka_waktu'       => 'Jangka Waktu Penugasan',
        'status_ganisph'     => 'Status GANISPH',
        'ket'                => 'KET.',
    ];

    private PenugasanModel $penugasanModel;

    public function __construct(?PenugasanModel $penugasanModel = null)
    {
        $this->penugasanModel = $penugasanModel ?? model(PenugasanModel::class);
    }

    public function path(): string
    {
        return ganisph_config()->excelFile;
    }

    public function sourcePath(): string
    {
        return ganisph_config()->sourceExcel;
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    /**
     * Menyiapkan berkas Excel kerja. Bila tabel masih kosong dan berkas
     * sumber pengguna tersedia, seluruh baris sumber diimpor terlebih dahulu.
     *
     * @return int jumlah baris yang diimpor
     */
    public function ensureFile(): int
    {
        $imported = 0;

        if ($this->exists()) {
            return 0;
        }

        if (is_file($this->sourcePath())) {
            try {
                $imported = $this->importSource(false);
            } catch (Throwable $e) {
                log_message('error', 'Impor Excel sumber gagal: {message}', ['message' => $e->getMessage()]);
            }
        }

        $this->refreshFromDatabase();

        return $imported;
    }

    /**
     * Mengimpor seluruh baris dari berkas sumber pengguna.
     *
     * @param bool $replace Kosongkan tabel terlebih dahulu.
     *
     * @return int jumlah baris yang berhasil diimpor
     */
    public function importSource(bool $replace = false): int
    {
        $source = $this->sourcePath();
        if (! is_file($source)) {
            throw new RuntimeException('Berkas sumber Excel tidak ditemukan: ' . $source);
        }

        if (! $replace && $this->penugasanModel->countAllResults() > 0) {
            return 0;
        }

        try {
            $spreadsheet = IOFactory::load($source);
        } catch (Throwable $e) {
            throw new RuntimeException('Berkas sumber Excel tidak dapat dibuka: ' . $e->getMessage(), 0, $e);
        }

        $sheet = $spreadsheet->getSheetByName(self::SHEET_TITLE) ?? $spreadsheet->getActiveSheet();
        $this->assertHeader($sheet, $source);
        $rows = $this->readSheetRows($sheet);

        if ($replace) {
            db_connect()->table('penugasan')->emptyTable();
        }

        $imported = 0;
        $notes    = [];

        foreach ($rows as $row) {
            $line     = (int) $row['_line'];
            $prepared = $this->normalizeRow($row, $line, $notes);

            if ($prepared === null) {
                continue;
            }

            $prepared['created_by'] = null;

            if ($this->penugasanModel->insert($prepared, true) === false) {
                $notes[] = "Baris {$line}: gagal disimpan ke database.";
                continue;
            }

            $imported++;
        }

        $spreadsheet->disconnectWorksheets();

        return $imported;
    }

    /**
     * Menulis seluruh data ke berkas Excel kerja.
     *
     * @param list<array<string, mixed>> $rows
     */
    public function export(array $rows): void
    {
        $this->ensureDirectory();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('GANISPH')
            ->setTitle('Data Penugasan GANISPH')
            ->setDescription('Cermin data penugasan GANISPH yang dapat disinkronkan dengan aplikasi.');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(self::SHEET_TITLE);

        $column = 1;
        foreach (self::HEADERS as $label) {
            $sheet->setCellValueExplicit(
                Coordinate::stringFromColumnIndex($column) . '1',
                $label,
                DataType::TYPE_STRING
            );
            $column++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count(self::HEADERS));
        $rowNumber  = 2;

        foreach ($rows as $row) {
            $column = 1;
            foreach (array_keys(self::HEADERS) as $field) {
                $coordinate = Coordinate::stringFromColumnIndex($column) . $rowNumber;

                if (in_array($field, self::DATE_FIELDS, true)) {
                    $date = $this->normalizeDate($row[$field] ?? null);

                    if ($date === null) {
                        $sheet->setCellValueExplicit($coordinate, '', DataType::TYPE_STRING);
                    } else {
                        $sheet->setCellValue($coordinate, ExcelDate::PHPToExcel(new DateTimeImmutable($date)));
                        $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('[$-421]dd mmmm yyyy;@');
                    }
                } else {
                    $sheet->setCellValueExplicit(
                        $coordinate,
                        $this->stringValue($row[$field] ?? ''),
                        DataType::TYPE_STRING
                    );
                }

                $column++;
            }
            $rowNumber++;
        }

        $dataLastRow = max(1, $rowNumber - 1);

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '123B6D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0B2B50']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        if ($dataLastRow >= 2) {
            $sheet->getStyle('A2:' . $lastColumn . $dataLastRow)->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9E2EC']]],
                'font'      => ['size' => 10],
            ]);
        }

        $widths = [
            'A' => 30, 'B' => 20, 'C' => 18, 'D' => 24, 'E' => 16, 'F' => 32,
            'G' => 34, 'H' => 14, 'I' => 20, 'J' => 20, 'K' => 32, 'L' => 38,
            'M' => 14, 'N' => 38, 'O' => 28, 'P' => 18, 'Q' => 16, 'R' => 16,
            'S' => 8, 'T' => 8, 'U' => 24, 'V' => 8,
        ];
        foreach ($widths as $letter => $width) {
            $sheet->getColumnDimension($letter)->setWidth($width);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . max(2, $dataLastRow));

        if ($dataLastRow >= 2) {
            $statusColumn = $this->columnLetterForField('status_ganisph');
            $ketColumn    = $this->columnLetterForField('ket');

            for ($row = 2; $row <= $dataLastRow; $row++) {
                $statusValidation = $sheet->getCell($statusColumn . $row)->getDataValidation();
                $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $statusValidation->setAllowBlank(false);
                $statusValidation->setShowDropDown(true);
                $statusValidation->setFormula1('"' . implode(',', PenugasanModel::STATUS_GANISPH) . '"');

                $ketValidation = $sheet->getCell($ketColumn . $row)->getDataValidation();
                $ketValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $ketValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $ketValidation->setAllowBlank(false);
                $ketValidation->setShowDropDown(true);
                $ketValidation->setFormula1('"' . implode(',', PenugasanModel::KET_OPTIONS) . '"');
            }
        }

        $this->addInstructionSheet($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        $this->writeAtomically($spreadsheet);
    }

    /**
     * Menyimpan ulang seluruh data DB ke Excel.
     */
    public function refreshFromDatabase(): void
    {
        $this->export($this->rowsForDatabase());
    }

    /**
     * Sinkronisasi dua arah.
     *
     * Baris tanpa ID dianggap data baru. Untuk baris dengan ID yang isinya
     * berbeda, berkas Excel dipakai bila waktu modifikasi file lebih baru
     * daripada kolom updated_at di database.
     *
     * @return array{imported:int, exported:int, skipped:int, conflicts:int, notes:list<string>}
     */
    public function sync(int $actorId): array
    {
        $summary = [
            'imported'  => 0,
            'exported'  => 0,
            'skipped'   => 0,
            'conflicts' => 0,
            'notes'     => [],
        ];

        if (! $this->exists()) {
            $summary['imported'] = $this->ensureFile();
            $summary['notes'][]  = 'Berkas Excel belum ada, file dibuat dari data sumber/database.';

            return $summary;
        }

        try {
            $excelRows = $this->readRows();
        } catch (Throwable $e) {
            $this->refreshFromDatabase();
            $summary['notes'][] = 'Berkas Excel tidak dapat dibaca sehingga dibuat ulang dari database: ' . $e->getMessage();

            return $summary;
        }

        $databaseRows = [];
        foreach ($this->penugasanModel->orderBy('id', 'ASC')->findAll() as $row) {
            $databaseRows[(int) $row['id']] = $row;
        }

        $seenIds   = [];
        $dbWinsIds = [];
        $fileTime  = (int) (@filemtime($this->path()) ?: time());

        foreach ($excelRows as $excelRow) {
            $line = (int) $excelRow['_line'];
            $id   = $this->rowId($excelRow);
            $existing = $id !== null && isset($databaseRows[$id]) ? $databaseRows[$id] : null;

            $prepared = $this->normalizeRow($excelRow, $line, $summary['notes']);
            if ($prepared === null) {
                $summary['skipped']++;
                continue;
            }

            if ($existing === null) {
                $prepared['created_by'] = $actorId > 0 ? $actorId : null;

                if ($this->penugasanModel->insert($prepared, true) === false) {
                    $summary['skipped']++;
                    $summary['notes'][] = "Baris {$line}: gagal disimpan ke database.";
                    continue;
                }

                $summary['imported']++;
                $summary['notes'][] = "Baris {$line}: data baru \"{$prepared['nama']}\" ditambahkan.";
                continue;
            }

            $seenIds[$id] = true;

            if (! $this->hasMeaningfulDifference($prepared, $existing)) {
                continue;
            }

            $dbTime = strtotime((string) ($existing['updated_at'] ?? '')) ?: 0;

            if ($fileTime <= $dbTime + 1) {
                $dbWinsIds[$id] = true;
                $summary['conflicts']++;
                continue;
            }

            $prepared['created_by'] = $existing['created_by'] ?? ($actorId > 0 ? $actorId : null);

            if ($this->penugasanModel->update($id, $prepared) === false) {
                $summary['skipped']++;
                $summary['notes'][] = "Baris {$line}: perubahan ID {$id} gagal disimpan.";
                continue;
            }

            $summary['imported']++;
            $summary['notes'][] = "Baris {$line}: perubahan ID {$id} diimpor ke database.";
        }

        foreach ($databaseRows as $id => $row) {
            if (! isset($seenIds[$id]) || isset($dbWinsIds[$id])) {
                $summary['exported']++;
            }
        }

        try {
            $this->refreshFromDatabase();
        } catch (Throwable $e) {
            $summary['notes'][] = 'Database tersinkron, tetapi file Excel gagal ditulis ulang: ' . $e->getMessage();
        }

        return $summary;
    }

    /**
     * Membaca baris dari berkas Excel kerja.
     *
     * @return list<array<string, mixed>>
     */
    public function readRows(): array
    {
        try {
            $spreadsheet = IOFactory::load($this->path());
        } catch (Throwable $e) {
            throw new RuntimeException('Berkas Excel tidak dapat dibuka: ' . $e->getMessage(), 0, $e);
        }

        $sheet = $spreadsheet->getSheetByName(self::SHEET_TITLE) ?? $spreadsheet->getActiveSheet();
        $this->assertHeader($sheet, $this->path());

        $rows = $this->readSheetRows($sheet);
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readSheetRows(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestDataRow();
        $rows       = [];

        for ($line = 2; $line <= $highestRow; $line++) {
            $row   = [];
            $blank = true;

            $column = 1;
            foreach (array_keys(self::HEADERS) as $field) {
                $coordinate = Coordinate::stringFromColumnIndex($column) . $line;
                $value      = $this->cellValue($sheet->getCell($coordinate), $field);

                $row[$field] = $value;

                if ($field !== 'id' && $value !== null && trim((string) $value) !== '') {
                    $blank = false;
                }

                $column++;
            }

            if ($blank) {
                continue;
            }

            $row['_line'] = $line;
            $rows[]       = $row;
        }

        return $rows;
    }

    private function cellValue(Cell $cell, string $field): ?string
    {
        $value = $cell->getValue();

        if ($value === null) {
            return null;
        }

        if (in_array($field, self::DATE_FIELDS, true)) {
            $date = $this->normalizeDate($value);

            return $date ?? '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (floor($value) === $value) {
                return sprintf('%.0f', $value);
            }

            return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string>         $notes
     *
     * @return array<string, mixed>|null
     */
    private function normalizeRow(array $row, int $line, array &$notes): ?array
    {
        $data = [];

        foreach (array_keys(self::HEADERS) as $field) {
            if ($field === 'id') {
                continue;
            }

            $value = $row[$field] ?? null;

            if (in_array($field, self::DATE_FIELDS, true)) {
                $data[$field] = $this->normalizeDate($value);
                continue;
            }

            $data[$field] = $this->cleanText($value, $field === 'catatan_u');
        }

        foreach (self::REQUIRED_FIELDS as $field => $label) {
            if (($data[$field] ?? '') === '') {
                $notes[] = "Baris {$line}: {$label} kosong, baris dilewati.";

                return null;
            }
        }

        if ($data['tanggal'] === null) {
            $notes[] = "Baris {$line}: tanggal tidak valid, baris dilewati.";

            return null;
        }

        $status = strtoupper($data['status_ganisph']);
        if (! in_array($status, PenugasanModel::STATUS_GANISPH, true)) {
            $notes[] = "Baris {$line}: STATUS GANISPH harus ASN atau NON-ASN, baris dilewati.";

            return null;
        }
        $data['status_ganisph'] = $status;

        $ket = strtoupper($data['ket']);
        if (! in_array($ket, PenugasanModel::KET_OPTIONS, true)) {
            $notes[] = "Baris {$line}: KET. harus B1, B2, atau B3, baris dilewati.";

            return null;
        }
        $data['ket'] = $ket;

        return $data;
    }

    /**
     * @param array<string, mixed> $prepared
     * @param array<string, mixed> $existing
     */
    private function hasMeaningfulDifference(array $prepared, array $existing): bool
    {
        foreach (array_keys(self::HEADERS) as $field) {
            if ($field === 'id') {
                continue;
            }

            $left  = $this->comparisonValue($prepared[$field] ?? null, $field);
            $right = $this->comparisonValue($existing[$field] ?? null, $field);

            if ($left !== $right) {
                return true;
            }
        }

        return false;
    }

    private function comparisonValue(mixed $value, string $field): string
    {
        if (in_array($field, self::DATE_FIELDS, true)) {
            return (string) ($this->normalizeDate($value) ?? '');
        }

        return trim((string) $value);
    }

    private function cleanText(mixed $value, bool $multiline = false): string
    {
        if ($value === null) {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", (string) $value);
        $value = trim($value);

        if ($value === '' || strtoupper($value) === 'NULL') {
            return '';
        }

        return $multiline ? $value : (string) preg_replace('/\s+/u', ' ', $value);
    }

    private function rowId(array $row): ?int
    {
        $id = trim((string) ($row['id'] ?? ''));

        return $id !== '' && ctype_digit($id) && (int) $id > 0 ? (int) $id : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsForDatabase(): array
    {
        return $this->penugasanModel->orderBy('id', 'ASC')->findAll();
    }

    private function assertHeader(Worksheet $sheet, string $source): void
    {
        $first = trim((string) $sheet->getCell('A1')->getValue());
        $fourth = trim((string) $sheet->getCell('D1')->getValue());

        if (mb_strtoupper($first) !== 'PERUSAHAAN' || mb_strtoupper($fourth) !== 'NAMA') {
            throw new RuntimeException(
                'Format kolom tidak sesuai. Kolom A harus PERUSAHAAN dan kolom D harus NAMA pada berkas ' . basename($source) . '.'
            );
        }
    }

    private function columnLetterForField(string $field): string
    {
        $index = (int) array_search($field, array_keys(self::HEADERS), true) + 1;

        return Coordinate::stringFromColumnIndex($index);
    }

    private function addInstructionSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Petunjuk');

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'PETUNJUK SINKRONISASI EXCEL GANISPH');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('123B6D');

        $lines = [
            '1. Sheet "' . self::SHEET_TITLE . '" adalah cermin data yang tersimpan di database aplikasi.',
            '2. Jangan mengubah nama/urutan kolom A-U dan jangan menghapus kolom ID (V).',
            '3. Baris tanpa ID dianggap data baru dan akan ditambahkan ke database saat sinkronisasi.',
            '4. Kolom ID adalah kunci sistem. Jangan mengubah nilainya secara manual.',
            '5. Kolom TANGGAL (C) dan TANGGAL izin (P) memakai format tanggal Excel dd mmmm yyyy.',
            '6. Kolom STATUS GANISPH hanya boleh berisi: ' . implode(', ', PenugasanModel::STATUS_GANISPH) . '.',
            '7. Kolom KET. hanya boleh berisi: ' . implode(', ', PenugasanModel::KET_OPTIONS) . '.',
            '8. Kolom A-U mengikuti berkas resmi. Kolom G/H dan L/M sengaja memiliki nama sama.',
            '9. Setelah mengubah isi Excel, simpan file lalu tekan tombol "Sinkron Excel" di aplikasi.',
            '10. Perubahan dari aplikasi otomatis memperbarui berkas ini.',
            '11. Jika isi berbeda, file Excel dipakai bila waktu modifikasinya lebih baru dari database.',
            '12. File ini terakhir dibuat: ' . date('d/m/Y H:i:s'),
        ];

        $row = 3;
        foreach ($lines as $line) {
            $sheet->setCellValue('A' . $row, $line);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(24);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(24);
    }

    private function writeAtomically(Spreadsheet $spreadsheet): void
    {
        $this->ensureDirectory();
        $tmp = $this->path() . '.tmp.' . bin2hex(random_bytes(4));

        try {
            $writer = new Xlsx($spreadsheet);
            $writer->save($tmp);

            if (! @rename($tmp, $this->path())) {
                if (! @copy($tmp, $this->path())) {
                    throw new RuntimeException('Berkas Excel tidak dapat ditulis.');
                }
                @unlink($tmp);
            }

            @chmod($this->path(), 0664);
        } finally {
            $spreadsheet->disconnectWorksheets();
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function ensureDirectory(): void
    {
        $directory = dirname($this->path());
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan Excel tidak dapat dibuat.');
        }

        if (! is_writable($directory)) {
            throw new RuntimeException('Folder penyimpanan Excel tidak dapat ditulis.');
        }
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            return sprintf('%.0f', $value);
        }

        return trim((string) $value);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_int($value) || is_float($value)) {
            $number = (float) $value;
            if ($number > 1 && $number < 2958466) {
                try {
                    return ExcelDate::excelToDateTimeObject($number)->format('Y-m-d');
                } catch (Throwable) {
                    // Lanjutkan ke parser teks.
                }
            }
        }

        $value = trim((string) $value);
        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
            if ($date !== false && $date->format('Y-m-d') === substr($value, 0, 10)) {
                return $date->format('Y-m-d');
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'd M Y', 'd F Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
            'january' => 1, 'february' => 2, 'march' => 3, 'may' => 5, 'june' => 6,
            'july' => 7, 'august' => 8, 'october' => 10, 'december' => 12,
        ];

        if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $value, $matches) === 1) {
            $month = $months[strtolower($matches[2])] ?? null;
            if ($month !== null && checkdate($month, (int) $matches[1], (int) $matches[3])) {
                return sprintf('%04d-%02d-%02d', (int) $matches[3], $month, (int) $matches[1]);
            }
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
