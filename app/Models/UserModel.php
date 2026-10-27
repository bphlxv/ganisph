<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nama',
        'nip',
        'jabatan',
        'password_hash',
        'is_admin',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function findByNip(string $nip): ?array
    {
        return $this->where('nip', $nip)->first();
    }

    public function isNipTaken(string $nip, ?int $exceptId = null): bool
    {
        $builder = $this->builder()->where('nip', $nip);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    public function adminCount(?int $exceptId = null): int
    {
        $builder = $this->builder()->where('is_admin', 1);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(?string $keyword = null): array
    {
        if ($keyword !== null && trim($keyword) !== '') {
            $keyword = trim($keyword);
            $this->groupStart()
                ->like('nama', $keyword)
                ->orLike('nip', $keyword)
                ->orLike('jabatan', $keyword)
                ->groupEnd();
        }

        return $this->orderBy('is_admin', 'DESC')
            ->orderBy('nama', 'ASC')
            ->findAll();
    }

    public function updatePassword(int $id, string $plainPassword): bool
    {
        return $this->builder()
            ->where('id', $id)
            ->update([
                'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
    }
}
