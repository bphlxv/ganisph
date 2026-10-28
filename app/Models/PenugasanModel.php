<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model data penugasan GANISPH.
 *
 * Kolom tabel mengikuti kolom berkas Excel resmi pada folder dokumen.
 */
class PenugasanModel extends Model
{
    public const STATUS_GANISPH = ['ASN', 'NON-ASN'];
    public const KET_OPTIONS    = ['B1', 'B2', 'B3'];

    protected $table            = 'penugasan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'perusahaan',
        'entt',
        'tanggal',
        'nama',
        'no_reg',
        'masa_berlaku',
        'kualifikasi',
        'kualifikasi_singkat',
        'kabkota',
        'provinsi',
        'jangka_waktu',
        'jenis_izin',
        'jenis_izin_singkat',
        'pemberi_izin',
        'no_izin',
        'tanggal_izin',
        'kapasitas_izin',
        'status_ganisph',
        'ket',
        'nilai_t',
        'catatan_u',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $keyword = null, ?string $status = null, ?string $ket = null): array
    {
        if ($keyword !== null && trim($keyword) !== '') {
            $keyword = trim($keyword);
            $this->groupStart()
                ->like('nama', $keyword)
                ->orLike('no_reg', $keyword)
                ->orLike('perusahaan', $keyword)
                ->orLike('entt', $keyword)
                ->orLike('no_izin', $keyword)
                ->orLike('kualifikasi', $keyword)
                ->orLike('kualifikasi_singkat', $keyword)
                ->orLike('jenis_izin', $keyword)
                ->orLike('jenis_izin_singkat', $keyword)
                ->orLike('kabkota', $keyword)
                ->orLike('provinsi', $keyword)
                ->orLike('pemberi_izin', $keyword)
                ->groupEnd();
        }

        if ($status !== null && in_array($status, self::STATUS_GANISPH, true)) {
            $this->where('status_ganisph', $status);
        }

        if ($ket !== null && in_array($ket, self::KET_OPTIONS, true)) {
            $this->where('ket', $ket);
        }

        return $this->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 5): array
    {
        return $this->orderBy('tanggal', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    public function countByStatusGanisph(string $status): int
    {
        return $this->where('status_ganisph', $status)->countAllResults();
    }

    public function countPerusahaan(): int
    {
        $row = $this->select('COUNT(DISTINCT perusahaan) AS jumlah', false)->first();

        return (int) ($row['jumlah'] ?? 0);
    }

    /**
     * @return list<array{kualifikasi_singkat: string, jumlah: int}>
     */
    public function countByKualifikasi(int $limit = 5): array
    {
        $rows = $this->select('kualifikasi_singkat, COUNT(*) AS jumlah', false)
            ->where('kualifikasi_singkat IS NOT NULL', null, false)
            ->where('kualifikasi_singkat !=', '')
            ->groupBy('kualifikasi_singkat')
            ->orderBy('jumlah', 'DESC')
            ->orderBy('kualifikasi_singkat', 'ASC')
            ->findAll($limit);

        return array_map(static fn (array $row): array => [
            'kualifikasi_singkat' => (string) $row['kualifikasi_singkat'],
            'jumlah'              => (int) $row['jumlah'],
        ], $rows);
    }

    /**
     * @return array<string, int> key B1/B2/B3
     */
    public function countByKet(): array
    {
        $rows = $this->select('ket, COUNT(*) AS jumlah', false)
            ->where('ket IS NOT NULL', null, false)
            ->where('ket !=', '')
            ->groupBy('ket')
            ->findAll();

        $counts = array_fill_keys(self::KET_OPTIONS, 0);
        foreach ($rows as $row) {
            $key = (string) $row['ket'];
            if (array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row['jumlah'];
            }
        }

        return $counts;
    }

    /**
     * Jumlah penugasan per bulan, berpatokan pada tanggal data terbaru.
     *
     * @return array<string, int> key Y-m
     */
    public function monthlyCounts(int $months = 12): array
    {
        $latest = $this->selectMax('tanggal')->first();
        $anchor = (string) ($latest['tanggal'] ?? '');
        $anchorTime = strtotime($anchor) ?: time();

        $endMonth   = date('Y-m-01', $anchorTime);
        $startMonth = date('Y-m-01', strtotime('-' . ($months - 1) . ' months', strtotime($endMonth)));
        $endDate    = date('Y-m-t', strtotime($endMonth));

        $rows = $this->select("strftime('%Y-%m', tanggal) AS bulan, COUNT(*) AS jumlah", false)
            ->where('tanggal >=', $startMonth)
            ->where('tanggal <=', $endDate)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->findAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['bulan']] = (int) $row['jumlah'];
        }

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key          = date('Y-m', strtotime('-' . $i . ' months', strtotime($endMonth)));
            $result[$key] = $counts[$key] ?? 0;
        }

        return $result;
    }
}
