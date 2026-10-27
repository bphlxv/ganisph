<?php

namespace App\Libraries;

use App\Models\PenugasanModel;
use App\Models\UserModel;
use DateTimeImmutable;
use DateTimeInterface;
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

/**
 * Menjaga sinkronisasi dua arah antara tabel penugasan dan berkas Excel.
 *
 * Aturan sinkronisasi:
 * - Baris tanpa ID dianggap data baru dari Excel dan akan ditambahkan ke DB.
 * - NIP harus terdaftar pada menu Data Pengguna.
 * - Jika terjadi perubahan pada baris yang sama, nilai dengan "Terakhir Diperbarui"
 *   paling baru yang dipakai. Jika waktunya sama, isi Excel dianggap perubahan manual.
 * - Setelah proses, seluruh isi database ditulis ulang ke Excel agar kedua sisi identik.
 */
class PenugasanExcel
{
    public const HEADERS = [
        'id'              => 'ID',
        'nama_pegawai'    => 'Nama Pegawai',
        'nip'             => 'NIP',
        'jabatan'         => 'Jabatan',
        'nomor_surat'     => 'Nomor Surat Tugas',
        'tanggal_surat'   => 'Tanggal Surat',
        'nama_kegiatan'   => 'Nama Kegiatan',
        'peran'           => 'Peran',
        'penyelenggara'   => 'Penyelenggara',
        'lokasi'          => 'Lokasi',
        'tanggal_mulai'   => 'Tanggal Mulai',
        'tanggal_selesai' => 'Tanggal Selesai',
        'status'          => 'Status',
        'keterangan'      => 'Keterangan',
        'updated_at'      => 'Terakhir Diperbarui',
    ];

    private const DATE_FIELDS = ['tanggal_surat', 'tanggal_mulai', 'tanggal_selesai'];

    private PenugasanModel $penugasanModel;
    private UserModel $userModel;

    public function __construct(?PenugasanModel $penugasanModel = null, ?UserModel $userModel = null)
    {
        $this->penugasanModel = $penugasanModel ?? model(PenugasanModel::class);
        $this->userModel      = $userModel ?? model(UserModel::class);
    }

    public function path(): string
    {
        return ganisph_config()->excelFile;
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    public function ensureFile(): void
    {
        if (! $this->exists()) {
            $this->export($this->rowsForDatabase());
        }
    }

    /**
     * Menulis seluruh data ke berkas Excel.
     *
     * @param list<array<string, mixed>> $rows
     */
    public function export(array $rows): void
    {
        $this->ensureDirectory();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('GANISPH')
            ->setTitle('Penugasan GANISPH')
            ->setDescription('Cermin data penugasan GANISPH yang dapat disinkronkan dengan aplikasi.');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penugasan');

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
                if ($field === 'updated_at') {
                    $value = $this->formatTimestamp($row['updated_at'] ?? null);
                } elseif (in_array($field, self::DATE_FIELDS, true)) {
                    $value = $this->formatDate($row[$field] ?? null);
                } else {
                    $value = (string) ($row[$field] ?? '');
                }

                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($column) . $rowNumber,
                    $value,
                    DataType::TYPE_STRING
                );
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
        $sheet->getRowDimension(1)->setRowHeight(28);

        if ($dataLastRow >= 2) {
            $sheet->getStyle('A2:' . $lastColumn . $dataLastRow)->applyFromArray([
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9E2EC']]],
                'font'      => ['size' => 10],
            ]);
        }

        $widths = [
            'A' => 7, 'B' => 24, 'C' => 20, 'D' => 30, 'E' => 30,
            'F' => 14, 'G' => 38, 'H' => 18, 'I' => 26, 'J' => 24,
            'K' => 14, 'L' => 14, 'M' => 13, 'N' => 38, 'O' => 21,
        ];
        foreach ($widths as $letter => $width) {
            $sheet->getColumnDimension($letter)->setWidth($width);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . max(2, $dataLastRow));

        $statusColumn = Coordinate::stringFromColumnIndex(
            (int) array_search('status', array_keys(self::HEADERS), true) + 1
        );
        if ($dataLastRow >= 2) {
            for ($row = 2; $row <= $dataLastRow; $row++) {
                $validation = $sheet->getCell($statusColumn . $row)->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"' . implode(',', PenugasanModel::STATUSES) . '"');
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
     * Sinkronisasi dua arah. Mengembalikan ringkasan proses.
     *
     * @return array{imported:int, exported:int, skipped:int, conflicts:int, notes:list<string>}
     */
    public function sync(int $actorId): array
    {
        $summary = [
            'imported' => 0,
            'exported' => 0,
            'skipped'  => 0,
            'conflicts'=> 0,
            'notes'    => [],
        ];

        if (! $this->exists()) {
            $this->refreshFromDatabase();
            $summary['notes'][] = 'Berkas Excel belum ada, file baru dibuat dari data database.';

            return $summary;
        }

        try {
            $excelRows = $this->readRows();
        } catch (RuntimeException $e) {
            $this->refreshFromDatabase();
            $summary['notes'][] = 'Berkas Excel tidak dapat dibaca sehingga dibuat ulang dari database.';

            return $summary;
        }

        $databaseRows = [];
        foreach ($this->penugasanModel->orderBy('id', 'ASC')->findAll() as $row) {
            $databaseRows[(int) $row['id']] = $row;
        }

        $seenIds    = [];
        $dbWinsIds  = [];

        foreach ($excelRows as $excelRow) {
            $line = $excelRow['_line'];
            $id   = ($excelRow['id'] ?? null) !== null && $excelRow['id'] !== '' && is_numeric($excelRow['id'])
                ? (int) $excelRow['id']
                : null;

            $existing = ($id !== null && isset($databaseRows[$id])) ? $databaseRows[$id] : null;

            $prepared = $this->prepareRow($excelRow, $line, $summary['notes'], $existing);
            if ($prepared === null) {
                $summary['skipped']++;
                continue;
            }

            if ($id !== null) {
                $seenIds[$id] = true;
            }

            if ($existing === null) {
                $prepared['created_by'] = $actorId;
                $newId                  = $this->penugasanModel->insert($prepared, true);

                if ($newId === false) {
                    $summary['skipped']++;
                    $summary['notes'][] = "Baris {$line}: gagal disimpan ke database.";
                    continue;
                }

                $summary['imported']++;
                $summary['notes'][] = "Baris {$line}: data baru \"{$prepared['nama_kegiatan']}\" ditambahkan.";
                continue;
            }

            $sim = $databaseRows[$id];
            if (! $this->hasMeaningfulDifference($prepared, $sim)) {
                continue;
            }

            $excelTime = $this->parseTimestamp($excelRow['updated_at'] ?? null);
            $fileTime  = $this->parseTimestamp($excelRow['_modified_at'] ?? null);
            $dbTime    = $this->parseTimestamp($sim['updated_at'] ?? null);

            $excelWins = true;
            if ($dbTime !== null) {
                if ($excelTime !== null && $excelTime < $dbTime) {
                    // Kolom waktu di Excel masih lama, tetapi file mungkin disimpan
                    // setelah perubahan terakhir di database. File yang lebih baru
                    // dianggap sebagai perubahan manual.
                    $excelWins = $fileTime !== null && $fileTime > $dbTime;
                } elseif ($excelTime === null && $fileTime !== null && $fileTime < $dbTime) {
                    $excelWins = false;
                }
            }

            if (! $excelWins) {
                $dbWinsIds[$id] = true;
                $summary['conflicts']++;
                continue;
            }

            $updateData             = $prepared;
            $updateData['created_by'] = $sim['created_by'] ?? $actorId;
            $this->penugasanModel->update($id, $updateData);
            $summary['imported']++;
            $summary['notes'][] = "Baris {$line}: perubahan pada ID {$id} diimpor ke database.";
        }

        foreach ($databaseRows as $id => $row) {
            if (! isset($seenIds[$id])) {
                $summary['exported']++;
            } elseif (isset($dbWinsIds[$id])) {
                $summary['exported']++;
            }
        }

        $this->refreshFromDatabase();

        return $summary;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readRows(): array
    {
        try {
            $spreadsheet = IOFactory::load($this->path());
        } catch (\Throwable $e) {
            throw new RuntimeException('Berkas Excel tidak dapat dibuka: ' . $e->getMessage(), 0, $e);
        }

        $sheet = $spreadsheet->getSheetByName('Penugasan') ?? $spreadsheet->getActiveSheet();
        $map   = $this->headerMap($sheet);

        $highestRow = $sheet->getHighestDataRow();
        $rows       = [];

        for ($line = 2; $line <= $highestRow; $line++) {
            $values = [];
            $blank  = true;

            foreach (self::HEADERS as $field => $label) {
                if (! isset($map[$field])) {
                    continue;
                }

                $coordinate = Coordinate::stringFromColumnIndex($map[$field]) . $line;
                $cell       = $sheet->getCell($coordinate);
                $value      = $cell->getValue();

                if ($value instanceof DateTimeInterface) {
                    if (in_array($field, self::DATE_FIELDS, true)) {
                        $value = $value->format('Y-m-d');
                    } elseif ($field === 'updated_at') {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                }

                if (is_string($value)) {
                    $value = trim($value);
                }

                $values[$field] = $value;

                if ($field !== 'updated_at' && $value !== null && (string) $value !== '') {
                    $blank = false;
                }
            }

            if ($blank) {
                continue;
            }

            $values['_line']        = $line;
            $values['_modified_at'] = $this->fileModifiedAt();
            $rows[]                 = $values;
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsForDatabase(): array
    {
        return $this->penugasanModel->orderBy('id', 'ASC')->findAll();
    }

    /**
     * @param array<string, mixed>      $row
     * @param list<string>              $notes
     * @param array<string, mixed>|null $existing
     *
     * @return array<string, mixed>|null
     */
    private function prepareRow(array $row, int $line, array &$notes, ?array $existing = null): ?array
    {
        $nip = preg_replace('/\s+/', '', (string) ($row['nip'] ?? ''));
        if ($nip === '') {
            $notes[] = "Baris {$line}: NIP kosong, baris dilewati.";

            return null;
        }

        $user = $this->userModel->findByNip($nip);
        if ($user === null) {
            if ($existing !== null && (string) $existing['nip'] === $nip) {
                $user = [
                    'id'      => $existing['user_id'],
                    'nama'    => $existing['nama_pegawai'],
                    'nip'     => $existing['nip'],
                    'jabatan' => $existing['jabatan'],
                ];
            } else {
                $notes[] = "Baris {$line}: NIP {$nip} belum terdaftar pada Data Pengguna, baris dilewati.";

                return null;
            }
        }

        $nomorSurat = trim((string) ($row['nomor_surat'] ?? ''));
        $kegiatan   = trim((string) ($row['nama_kegiatan'] ?? ''));
        $peran      = trim((string) ($row['peran'] ?? ''));
        $penyelenggara = trim((string) ($row['penyelenggara'] ?? ''));
        $lokasi     = trim((string) ($row['lokasi'] ?? ''));

        foreach ([
            'Nomor Surat Tugas' => $nomorSurat,
            'Nama Kegiatan'     => $kegiatan,
            'Peran'             => $peran,
            'Penyelenggara'     => $penyelenggara,
            'Lokasi'            => $lokasi,
        ] as $label => $value) {
            if ($value === '') {
                $notes[] = "Baris {$line}: {$label} kosong, baris dilewati.";

                return null;
            }
        }

        $tanggalSurat   = $this->normalizeDate($row['tanggal_surat'] ?? null);
        $tanggalMulai   = $this->normalizeDate($row['tanggal_mulai'] ?? null);
        $tanggalSelesai = $this->normalizeDate($row['tanggal_selesai'] ?? null);

        if ($tanggalSurat === null || $tanggalMulai === null || $tanggalSelesai === null) {
            $notes[] = "Baris {$line}: format tanggal tidak valid (gunakan YYYY-MM-DD), baris dilewati.";

            return null;
        }

        if ($tanggalSelesai < $tanggalMulai) {
            $notes[] = "Baris {$line}: tanggal selesai lebih awal dari tanggal mulai, baris dilewati.";

            return null;
        }

        $status = 'Aktif';
        $rawStatus = strtolower(trim((string) ($row['status'] ?? '')));
        foreach (PenugasanModel::STATUSES as $choice) {
            if (strtolower($choice) === $rawStatus) {
                $status = $choice;
                break;
            }
        }

        return [
            'user_id'         => $user['id'] !== null ? (int) $user['id'] : null,
            'nama_pegawai'    => $user['nama'],
            'nip'             => $user['nip'],
            'jabatan'         => $user['jabatan'],
            'nomor_surat'     => $nomorSurat,
            'tanggal_surat'   => $tanggalSurat,
            'nama_kegiatan'   => $kegiatan,
            'peran'           => $peran,
            'penyelenggara'   => $penyelenggara,
            'lokasi'          => $lokasi,
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'status'          => $status,
            'keterangan'      => trim((string) ($row['keterangan'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $prepared
     * @param array<string, mixed> $existing
     */
    private function hasMeaningfulDifference(array $prepared, array $existing): bool
    {
        $fields = [
            'user_id', 'nama_pegawai', 'nip', 'jabatan', 'nomor_surat', 'tanggal_surat',
            'nama_kegiatan', 'peran', 'penyelenggara', 'lokasi', 'tanggal_mulai',
            'tanggal_selesai', 'status', 'keterangan',
        ];

        foreach ($fields as $field) {
            $left  = $this->normalizeForComparison($prepared[$field] ?? '', $field);
            $right = $this->normalizeForComparison($existing[$field] ?? '', $field);

            if ($left !== $right) {
                return true;
            }
        }

        return false;
    }

    private function normalizeForComparison(mixed $value, string $field): string
    {
        if (in_array($field, self::DATE_FIELDS, true)) {
            return (string) $this->normalizeDate($value);
        }

        return trim((string) $value);
    }

    /**
     * @return array<string, int>
     */
    private function headerMap(Worksheet $sheet): array
    {
        $rawHeaders = $sheet->rangeToArray('A1:O1', null, true, true, false)[0] ?? [];
        $normalized = [];

        foreach ($rawHeaders as $index => $label) {
            $normalized[mb_strtolower(trim((string) $label))] = $index + 1;
        }

        $map = [];
        foreach (self::HEADERS as $field => $label) {
            $key = mb_strtolower($label);
            if (isset($normalized[$key])) {
                $map[$field] = $normalized[$key];
            }
        }

        foreach (['id', 'nip', 'nomor_surat', 'nama_kegiatan', 'tanggal_mulai', 'tanggal_selesai', 'updated_at'] as $required) {
            if (! isset($map[$required])) {
                throw new RuntimeException('Kolom wajib "' . self::HEADERS[$required] . '" tidak ditemukan pada berkas Excel.');
            }
        }

        return $map;
    }

    private function addInstructionSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Petunjuk');

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'PETUNJUK SINKRONISASI EXCEL GANISPH');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('123B6D');

        $lines = [
            '1. Sheet "Penugasan" adalah cermin data yang tersimpan di database aplikasi.',
            '2. Jangan mengubah atau menghapus nama kolom pada baris pertama.',
            '3. Baris dengan kolom ID kosong akan dianggap sebagai data baru saat sinkronisasi.',
            '4. NIP harus sudah terdaftar pada menu Data Pengguna. Nama dan Jabatan akan mengikuti data pengguna.',
            '5. Format tanggal adalah YYYY-MM-DD, contoh 2026-10-27.',
            '6. Kolom Status hanya boleh berisi: ' . implode(', ', PenugasanModel::STATUSES) . '.',
            '7. Setelah mengubah isi Excel, simpan file lalu tekan tombol "Sinkron Excel" di aplikasi.',
            '8. Perubahan dari aplikasi akan otomatis memperbarui berkas Excel ini.',
            '9. Jika waktu "Terakhir Diperbarui" sama, isi Excel dianggap sebagai perubahan manual.',
            '10. File ini terakhir dibuat: ' . date('d/m/Y H:i:s'),
        ];

        $row = 3;
        foreach ($lines as $line) {
            $sheet->setCellValue('A' . $row, $line);
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->getStyle('A' . $row)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(26);
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

    private function formatDate(mixed $value): string
    {
        $normalized = $this->normalizeDate($value);

        return $normalized ?? '';
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value) && (float) $value > 1 && (float) $value < 2958466) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                // Lanjutkan ke parser teks.
            }
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));
            if ($date !== false && $date->format('Y-m-d') === substr($value, 0, 10)) {
                return $date->format('Y-m-d');
            }
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    private function formatTimestamp(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        $timestamp = strtotime((string) $value);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : (string) $value;
    }

    private function parseTimestamp(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value) && (float) $value > 1) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->getTimestamp();
            } catch (\Throwable) {
                // Lanjutkan.
            }
        }

        $timestamp = strtotime((string) $value);

        return $timestamp !== false ? $timestamp : null;
    }

    private function fileModifiedAt(): string
    {
        $modified = @filemtime($this->path());

        return $modified !== false ? date('Y-m-d H:i:s', $modified) : date('Y-m-d H:i:s');
    }
}
