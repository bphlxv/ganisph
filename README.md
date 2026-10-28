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
- Dashboard dengan statistik ASN/NON-ASN, perusahaan unik, tren 12 bulan,
  sebaran kualifikasi, kategori KET., dan daftar penugasan terbaru.
- Form penugasan GANISPH dengan 21 kolom sesuai berkas Excel resmi, validasi
  server, serta penyimpanan ke database dan Excel.
- Halaman **Daftar Penugasan** terpisah dengan paginasi (default 10 baris per
  halaman), pengaturan jumlah tampil 10–200, pencarian, dan filter status/KET.
- Sinkronisasi Excel **dua arah** (database ↔ Excel) dengan penyelesaian konflik
  berdasarkan waktu perubahan berkas dan `updated_at` database.
- Unduh berkas Excel kapan saja.
- Pembuatan surat telaahan DOCX otomatis dari template resmi pengguna
  (`dokumen/form_telaahan.docx`) dan data database.
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
| Nama | Administrator |
| NIP | `[NIP administrator]` |
| Jabatan | Administrator |
| Password | `[password awal]` |
| Hak akses | Administrator |

> **Penting:** password `123` hanya untuk instalasi awal. Segera ganti melalui
> menu **Pengaturan → Ganti Password** setelah login pertama.

Perintah `php spark ganisph:install` akan:

1. memeriksa ekstensi PHP yang dibutuhkan;
2. membuat folder `writable/database`, `writable/excel`, `writable/templates`,
   `writable/telaahan`, `writable/tmp`, dan `writable/backups`;
3. menjalankan migrasi database SQLite;
4. membuat akun administrator awal bila tabel pengguna masih kosong;
5. mengimpor seluruh baris dari `dokumen/data_penugasan_ganisph.xlsx` bila tabel
   penugasan masih kosong;
6. membuat berkas Excel kerja dan template DOCX bila belum ada.

Perintah tersebut aman dijalankan ulang dan tidak menghapus data yang sudah ada.
Untuk mengimpor ulang data sumber secara paksa (mengosongkan tabel penugasan),
gunakan:

```bash
php spark ganisph:import --replace
```

## Struktur Data

| Berkas/Folder | Keterangan |
| --- | --- |
| `dokumen/data_penugasan_ganisph.xlsx` | Berkas sumber data pengguna (sheet `2025`, 125 baris) |
| `dokumen/form_telaahan.docx` | Template sumber surat telaahan (mail merge Word) |
| `writable/database/ganisph.sqlite` | Database utama SQLite |
| `writable/excel/penugasan_ganisph.xlsx` | Berkas Excel kerja yang dibaca dan ditulis aplikasi |
| `writable/templates/telaahan_penugasan.docx` | Template telaahan hasil konversi placeholder `${...}` |
| `writable/telaahan/` | Dokumen DOCX hasil unduhan sementara |
| `writable/tmp/` | Folder sementara PhpWord |
| `writable/backups/` | Cadangan database yang dibuat administrator |
| `writable/session/` | Sesi login berbasis file |

Folder `writable/` tidak disimpan ke Git (hanya `.gitkeep`). Pastikan web server
memiliki izin tulis ke seluruh isi folder `writable/`, termasuk `writable/tmp`.

Folder `dokumen/` juga tidak disertakan dalam repositori publik karena berisi
data pengguna dan template bertanda tangan. Sediakan kedua berkas tersebut
secara lokal sebelum menjalankan instalasi atau impor.

## Alur Kerja Penugasan

1. Buka **GANISPH → Penugasan GANISPH**.
2. Isi 21 kolom data sesuai berkas Excel resmi: perusahaan dan E-NTT, tanggal,
   nama GANISPH, nomor register, masa berlaku, kualifikasi (panjang dan
   singkatan), kabupaten/kota, provinsi, jangka waktu penugasan, jenis izin
   (panjang dan singkatan), pemberi izin, nomor izin, tanggal izin, kapasitas
   izin, status GANISPH, KET., serta kolom tambahan Nilai T dan Catatan U.
3. Tekan **Simpan & Tulis ke Excel**. Data langsung disimpan ke database dan
   berkas Excel kerja di server, lalu Anda diarahkan ke **Daftar Penugasan**.
4. Buka **GANISPH → Daftar Penugasan** untuk melihat seluruh data. Daftar
   menampilkan maksimal 10 penugasan per halaman; gunakan tombol halaman atau
   isi kolom **Tampilkan … per halaman** (10–200) untuk mengubah jumlah baris.
5. Gunakan **Download Excel** untuk mengambil salinan berkas dan **Sinkron
   Excel** untuk membaca perubahan manual.
6. Untuk perubahan manual, sunting berkas **kerja** di
   `writable/excel/penugasan_ganisph.xlsx` (atau letakkan kembali salinan yang
   sudah diedit pada path tersebut), simpan, lalu tekan **Sinkron Excel**.
7. Gunakan tombol hapus pada daftar untuk menghapus penugasan dari database
   dan Excel.

### Aturan sinkronisasi Excel

- Urutan dan nama kolom **A–U tidak boleh diubah**. Kolom `V` berisi `ID` yang
  ditambahkan aplikasi sebagai kunci sinkronisasi.
- Kolom G/H dan L/M sengaja memiliki nama yang sama, persis seperti berkas
  sumber. Kolom T dan U juga mempertahankan nama `NULL` dari berkas sumber.
- Baris tanpa `ID` dianggap data baru dan akan ditambahkan ke database.
- Kolom `TANGGAL` (C) dan `TANGGAL` izin (P) ditulis dengan format Excel
  `dd mmmm yyyy`. Saat impor, aplikasi juga menerima format `YYYY-MM-DD`,
  `dd/mm/yyyy`, dan nama bulan Indonesia.
- `STATUS GANISPH` hanya boleh berisi `ASN` atau `NON-ASN`.
- `KET.` hanya boleh berisi `B1`, `B2`, atau `B3`.
- Jika isi baris berbeda, berkas Excel dipakai bila waktu modifikasi berkas lebih
  baru daripada kolom `updated_at` baris database. Jika tidak, database dianggap
  lebih baru dan nilainya ditulis ke Excel.
- Baris database yang tidak ada di Excel, atau yang menang konflik, ditulis
  ulang ke Excel. Menghapus baris dari Excel **tidak menghapus** data database.
- Setelah sinkronisasi, seluruh isi database ditulis ulang ke Excel agar kedua
  sisi identik. Sheet **Petunjuk** berisi ringkasan aturan ini.
- Kolom R dan S memiliki dropdown validasi di Excel.

## Surat Telaahan DOCX

Tombol **Download Surat** pada halaman **Daftar Penugasan** membuat dokumen
telaahan untuk setiap penugasan. Dokumen dibuat dari template sumber
`dokumen/form_telaahan.docx`, lalu disimpan sebagai template kerja di
`writable/templates/telaahan_penugasan.docx`.

Template sumber memakai mail merge Word dengan tampilan `«FIELD»`. Saat
instalasi, aplikasi melepas struktur mail merge dan mengubahnya menjadi
placeholder `${FIELD}` yang dapat diisi PhpWord. Blok tanda tangan pada template
bersifat tetap dan tidak diubah.

Pemetaan 18 field template ke kolom Excel:

| Field DOCX | Kolom |
| --- | --- |
| `PERUSAHAAN_` | A Perusahaan |
| `ENTT` | B E-NTT |
| `TANGGAL` | C Tanggal |
| `NAMA` | D Nama |
| `NO_REG` | E No. Reg |
| `MASA_BERLAKU` | F Masa Berlaku |
| `KUALIFIKASI1` | G Kualifikasi (panjang) |
| `KUALIFIKASI` | H Kualifikasi (singkat) |
| `KABKOTA` | I Kab./Kota |
| `PROVINSI` | J Provinsi |
| `JANGKA_WAKTU_PENUGASAN` | K Jangka Waktu Penugasan |
| `JENIS_IZIN1` | L Jenis Izin (panjang) |
| `JENIS_IZIN` | M Jenis Izin (singkat) |
| `PEMBERI_IZIN` | N Pemberi Izin |
| `NO_IZIN` | O No Izin |
| `TANGGAL1` | P Tanggal Izin |
| `KAPASITAS_IZIN` | Q Kapasitas Izin |
| `STATUS_GANISPH` | R Status GANISPH |

Dokumen hasil unduhan disimpan sementara di `writable/telaahan/` dan dibersihkan
otomatis setelah 24 jam. Jika template sumber diperbarui, aplikasi akan
mengonversinya ulang saat dokumen berikutnya dibuat.

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

   `CI_ENVIRONMENT = production` juga mematikan debug toolbar dan halaman error
   rinci. Bila hanya toolbar yang ingin disembunyikan saat development, hapus
   `'toolbar'` dari daftar `required` bagian `after` di
   `app/Config/Filters.php`.

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
