<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel penugasan GANISPH mengikuti kolom berkas Excel resmi:
 * PERUSAHAAN, E-NTT, TANGGAL, NAMA, NO. REG, MASA BERLAKU, KUALIFIKASI,
 * KAB./KOTA, PROVINSI, JANGKA WAKTU PENUGASAN, JENIS IZIN, PEMBERI IZIN,
 * NO IZIN, TANGGAL, KAPASITAS IZIN, STATUS GANISPH, KET.
 */
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
            'perusahaan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'entt' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'tanggal' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'no_reg' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'masa_berlaku' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'kualifikasi' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'kualifikasi_singkat' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'kabkota' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'provinsi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'jangka_waktu' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'jenis_izin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'jenis_izin_singkat' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'pemberi_izin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'no_izin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'tanggal_izin' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'kapasitas_izin' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status_ganisph' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'ket' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'nilai_t' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'catatan_u' => [
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
        $this->forge->addKey('nama');
        $this->forge->addKey('no_reg');
        $this->forge->addKey('no_izin');
        $this->forge->addKey('status_ganisph');
        $this->forge->addKey('tanggal');
        $this->forge->createTable('penugasan', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('penugasan', true);
    }
}
