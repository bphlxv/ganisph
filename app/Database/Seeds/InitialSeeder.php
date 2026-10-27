<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $db = db_connect();

        $nip = '199204222025061006';
        $existing = $db->table('users')->where('nip', $nip)->get()->getRowArray();

        if ($existing !== null) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $db->table('users')->insert([
            'nama'          => 'Supriadi',
            'nip'           => $nip,
            'jabatan'       => 'Pranata SDM Aparatur Terampil',
            'password_hash' => password_hash('123', PASSWORD_DEFAULT),
            'is_admin'      => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
