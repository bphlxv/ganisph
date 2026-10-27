# GANISPH

**Sistem Informasi Penugasan dan Telaahan GANISPH** adalah aplikasi web berbasis
CodeIgniter 4 untuk mencatat penugasan pegawai, menyinkronkan data dengan berkas
Excel, serta menerbitkan surat telaahan dalam format DOCX.

Antarmuka menggunakan AdminLTE 4, Bootstrap 5, dan mendukung mode terang/gelap.
Seluruh aset disajikan secara lokal (tanpa CDN) dan data disimpan pada SQLite.

## Fitur

- Login menggunakan **NIP** dan password, dengan pembatasan percobaan login.
- Peran **administrator** dan **pengguna**:
  - Semua pengguna dapat menambah, mengubah, dan menghapus penugasan.
  - Semua pengguna dapat mengunduh Excel dan surat telaahan DOCX.
  - Hanya administrator yang dapat mengelola pengguna, mereset password,
    melakukan *login sebagai*, dan mencadangkan database.
- Dashboard dengan statistik, grafik enam bulan terakhir, agenda terdekat, dan
  daftar penugasan terbaru.
- Form penugasan GANISPH dengan validasi server, pengisian otomatis NIP/jabatan,
  serta penyimpanan ke database dan Excel.
- Sinkronisasi Excel **dua arah** (database ↔ Excel) dengan penyelesaian konflik
  berdasarkan waktu perubahan.
- Unduh berkas Excel kapan saja.
- Pembuatan surat telaahan DOCX otomatis dari template dan data database.
- Ganti password untuk setiap pengguna.
- Reset password pengguna ke password awal oleh administrator.
- Data pengguna: tambah, edit, hak akses administrator, login sebagai, dan hapus.
- Backup database SQLite sekali klik.
- Mode terang/gelap yang tersimpan pada browser.
- Tampilan responsif untuk desktop, tablet, dan ponsel.

## Kebutuhan Sistem

- PHP **8.2+** (disarankan PHP 8.3)
- Composer 2
- Ekstensi PHP: `intl`, `mbstring`, `sqlite3`, `pdo_sqlite`, `xmlreader`,
  `xmlwriter`, `zip`, dan `gd`
- Web server Apache/Nginx atau PHP built-in server untuk pengembangan

> PhpSpreadsheet memerlukan `ext-xmlreader`. Jika muncul pesan bahwa ekstensi
> tersebut belum aktif, pasang paket PHP yang sesuai (misalnya
> `php8.3-xmlreader`) lalu restart web server/PHP-FPM.

## Instalasi Cepat

```bash
git clone https://github.com/bphlxv/ganisph.git
cd ganisph

composer install

cp .env.example .env
# Sesuaikan app.baseURL pada .env

php spark ganisph:install
php spark serve --host localhost --port 8080
```

Buka <http://localhost:8080/>.

### Akun awal

| Keterangan | Nilai |
| --- | --- |
| Nama | Supriadi |
| NIP | `199204222025061006` |
| Jabatan | Pranata SDM Aparatur Terampil |
| Password | `123` |
| Hak akses | Administrator |

> **Penting:** password `123` hanya untuk instalasi awal. Segera ganti melalui
> menu **Pengaturan → Ganti Password** setelah login pertama.

Perintah `php spark ganisph:install` akan:

1. memeriksa ekstensi PHP yang dibutuhkan;
2. membuat folder `writable/database`, `writable/excel`, `writable/templates`,
   `writable/telaahan`, dan `writable/backups`;
3. menjalankan migrasi database SQLite;
4. membuat akun administrator awal bila tabel pengguna masih kosong;
5. membuat berkas Excel dan template DOCX bila belum ada.

Perintah tersebut aman dijalankan ulang dan tidak menghapus data yang sudah ada.

## Struktur Data

| Berkas/Folder | Keterangan |
| --- | --- |
| `writable/database/ganisph.sqlite` | Database utama SQLite |
| `writable/excel/penugasan_ganisph.xlsx` | Cermin data penugasan dalam Excel |
| `writable/templates/telaahan_penugasan.docx` | Template surat telaahan |
| `writable/telaahan/` | Dokumen DOCX hasil unduhan sementara |
| `writable/backups/` | Cadangan database yang dibuat administrator |
| `writable/session/` | Sesi login berbasis file |

Folder di atas tidak disimpan ke Git (hanya `.gitkeep`). Pastikan web server
memiliki izin tulis ke seluruh isi folder `writable/`.

## Alur Kerja Penugasan

1. Buka **GANISPH → Penugasan GANISPH**.
2. Pilih pegawai, lalu isi data surat tugas, kegiatan, peran, penyelenggara,
   lokasi, tanggal, dan status.
3. Tekan **Simpan & Tulis ke Excel**. Data langsung disimpan ke database dan
   berkas Excel di server.
4. Gunakan **Download Excel** untuk mengambil salinan berkas.
5. Gunakan **Sinkron Excel** setelah mengubah berkas Excel secara manual.

### Aturan sinkronisasi Excel

- Nama kolom pada baris pertama **tidak boleh diubah atau dihapus**.
- Kolom `ID` kosong pada baris baru berarti data baru akan ditambahkan.
- NIP harus sudah terdaftar pada menu **Data Pengguna**. Nama dan jabatan akan
  mengikuti data pengguna, bukan isi kolom Excel.
- Format tanggal: `YYYY-MM-DD`. Status hanya boleh `Aktif`, `Selesai`, atau
  `Dibatalkan`.
- Kolom `Terakhir Diperbarui` digunakan untuk menyelesaikan konflik. Jika waktu
  Excel sama atau lebih baru daripada database, isi Excel yang dipakai. Jika
  berkas Excel disimpan setelah perubahan database, simpanan tersebut juga
  dianggap sebagai perubahan manual.
- Menghapus baris dari Excel **tidak menghapus** data di database. Data akan
  ditulis ulang ke Excel saat sinkronisasi. Gunakan tombol hapus pada aplikasi
  untuk menghapus penugasan.
- Setelah sinkronisasi, seluruh isi database ditulis ulang ke Excel agar kedua
  sisi identik.

## Surat Telaahan DOCX

Menu **Telaahan Penugasan** menampilkan data inti setiap penugasan dan tombol
**Download Surat**. Dokumen dibuat dari template
`writable/templates/telaahan_penugasan.docx`.

Template bawaan dibuat otomatis saat instalasi. Untuk menggantinya, sediakan
berkas DOCX dengan placeholder berikut (format `${NAMA_PLACEHOLDER}`):

`NOMOR_TELAAHAN`, `NOMOR_SURAT`, `TANGGAL_SURAT`, `NAMA`, `NIP`, `JABATAN`,
`KEGIATAN`, `PERAN`, `PENYELENGGARA`, `LOKASI`, `PERIODE`, `STATUS`,
`KETERANGAN`, `CATATAN_TELAAHAN`, `TANGGAL_TELAAHAN`, `PEJABAT_NAMA`, dan
`PEJABAT_NIP`.

Pejabat penandatangan diambil dari administrator pertama pada Data Pengguna.
Dokumen hasil unduhan disimpan sementara di `writable/telaahan/` dan dibersihkan
otomatis setelah 24 jam.

## Backup Database

Masuk sebagai administrator, buka **Pengaturan → Data Pengguna**, lalu tekan
**Backup Database**. Berkas `.sqlite` akan langsung diunduh dan juga disimpan di
`writable/backups/`. Cadangan yang lebih lama dari tujuh hari dibersihkan
otomatis.

## Menjalankan di Produksi

1. Arahkan document root web server ke folder `public/`, bukan folder proyek.
2. Atur `.env`:

   ```dotenv
   CI_ENVIRONMENT = production
   app.baseURL = 'https://domain-anda.go.id/'
   app.forceGlobalSecureRequests = true
   cookie.secure = true
   logger.threshold = 4
   ```

3. Pastikan HTTPS aktif dan folder `writable/` dapat ditulis oleh pengguna web
   server, tetapi tidak dapat diakses langsung dari browser.
4. Jalankan `php spark ganisph:install` sekali, lalu login dan ganti password
   administrator awal.
5. Lindungi berkas database dan backup dengan izin filesystem yang tepat.
6. Backup `writable/database/ganisph.sqlite` secara berkala, selain fitur backup
   bawaan aplikasi.

Contoh konfigurasi Nginx (ringkas):

```nginx
server {
    listen 80;
    server_name ganisph.example.com;
    root /var/www/ganisph/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Pengembangan

```bash
# jalankan server pengembangan
php spark serve --host localhost --port 8080

# jalankan migrasi dan seeder secara manual
php spark migrate
php spark db:seed InitialSeeder

# periksa sintaks seluruh kode aplikasi
find app -name '*.php' -exec php -l {} \;
```

## Teknologi

- [CodeIgniter 4](https://codeigniter.com/)
- [AdminLTE 4](https://adminlte.io/) / Bootstrap 5
- [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/)
- [PhpWord](https://phpword.readthedocs.io/)
- SQLite
- Bootstrap Icons, OverlayScrollbars, Chart.js (aset lokal)

## Lisensi

Kode aplikasi ini dilisensikan dengan lisensi MIT. Lihat berkas [LICENSE](LICENSE).
Komponen pihak ketiga tetap mengikuti lisensi masing-masing.
