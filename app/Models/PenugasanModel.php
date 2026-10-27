<?php

namespace App\Models;

use CodeIgniter\Model;

class PenugasanModel extends Model
{
    public const STATUSES = ['Aktif', 'Selesai', 'Dibatalkan'];

    protected $table            = 'penugasan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'nama_pegawai',
        'nip',
        'jabatan',
        'nomor_surat',
        'tanggal_surat',
        'nama_kegiatan',
        'peran',
        'penyelenggara',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'keterangan',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $keyword = null, ?string $status = null): array
    {
        if ($keyword !== null && trim($keyword) !== '') {
            $keyword = trim($keyword);
            $this->groupStart()
                ->like('nama_pegawai', $keyword)
                ->orLike('nip', $keyword)
                ->orLike('nomor_surat', $keyword)
                ->orLike('nama_kegiatan', $keyword)
                ->orLike('peran', $keyword)
                ->groupEnd();
        }

        if ($status !== null && $status !== '' && in_array($status, self::STATUSES, true)) {
            $this->where('status', $status);
        }

        return $this->orderBy('tanggal_surat', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 5): array
    {
        return $this->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    public function countByStatus(string $status): int
    {
        return $this->where('status', $status)->countAllResults();
    }

    /**
     * Jumlah penugasan per bulan untuk beberapa bulan terakhir.
     *
     * @return array<string, int> key Y-m
     */
    public function monthlyCounts(int $months = 6): array
    {
        $start = date('Y-m-01', strtotime('-' . ($months - 1) . ' months'));
        $end   = date('Y-m-t');

        $rows = $this->select("strftime('%Y-%m', tanggal_surat) AS bulan, COUNT(*) AS jumlah", false)
            ->where('tanggal_surat >=', $start)
            ->where('tanggal_surat <=', $end)
            ->groupBy('bulan')
            ->orderBy('bulan', 'ASC')
            ->findAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['bulan']] = (int) $row['jumlah'];
        }

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key          = date('Y-m', strtotime('-' . $i . ' months'));
            $result[$key] = $counts[$key] ?? 0;
        }

        return $result;
    }
}
