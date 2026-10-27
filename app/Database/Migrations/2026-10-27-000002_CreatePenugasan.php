<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePenugasan extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'nama_pegawai' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'nip' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'jabatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'nomor_surat' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'tanggal_surat' => [
                'type' => 'DATE',
            ],
            'nama_kegiatan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'peran' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'penyelenggara' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'lokasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'tanggal_mulai' => [
                'type' => 'DATE',
            ],
            'tanggal_selesai' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'Aktif',
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INTEGER',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('tanggal_mulai');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('penugasan', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('penugasan', true);
    }
}
