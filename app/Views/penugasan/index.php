<?php
$errors     = session('errors') ?? [];
$isEdit     = $editData !== null;
$formAction = $isEdit ? site_url('penugasan/' . (int) $editData['id']) : site_url('penugasan');

$field = static function (string $name, mixed $default = '') use ($editData): string {
    $value = old($name, $editData[$name] ?? $default);

    return $value === null ? '' : (string) $value;
};

$selectedStatus = $field('status_ganisph', 'NON-ASN');
$selectedKet    = $field('ket', 'B1');
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger app-alert" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-octagon-fill alert-icon"></i>
            <div>
                <div class="fw-semibold">Periksa kembali isian Anda</div>
                <ul class="small mb-0 mt-1 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->include('penugasan/_excel_toolbar') ?>

<div class="card mb-4 form-card" id="form-penugasan">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="card-title mb-0">
                <i class="bi bi-pencil-square me-2 text-primary"></i>
                <?= $isEdit ? 'Edit Penugasan' : 'Form Penugasan GANISPH' ?>
            </h3>
            <?php if ($isEdit): ?>
                <div class="card-subtitle">Perbarui data penugasan yang sudah tersimpan.</div>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <a href="<?= site_url('daftar-penugasan') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-table me-1"></i>Daftar Penugasan
            </a>
            <?php if ($isEdit): ?>
                <span class="badge badge-soft-warning rounded-pill"><i class="bi bi-pencil me-1"></i>Mode Edit</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form action="<?= $formAction ?>" method="post" novalidate data-loading-form>
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section-heading">
                    <span class="form-section-number">1</span>
                    <div>
                        <h4>Data GANISPH &amp; Perusahaan</h4>
                        <p>Identitas tenaga GANISPH, perusahaan, dan lokasi penugasan.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="perusahaan" class="form-label">Perusahaan <span class="text-danger">*</span></label>
                        <input type="text" name="perusahaan" id="perusahaan"
                               class="form-control<?= field_invalid($errors, 'perusahaan') ?>"
                               value="<?= esc($field('perusahaan')) ?>" maxlength="255" required>
                        <?= field_error($errors, 'perusahaan') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="entt" class="form-label">E-NTT <span class="text-danger">*</span></label>
                        <input type="text" name="entt" id="entt"
                               class="form-control<?= field_invalid($errors, 'entt') ?>"
                               value="<?= esc($field('entt')) ?>" maxlength="100" required>
                        <?= field_error($errors, 'entt') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="nama" class="form-label">Nama GANISPH <span class="text-danger">*</span></label>
                        <input type="text" name="nama" id="nama"
                               class="form-control<?= field_invalid($errors, 'nama') ?>"
                               value="<?= esc($field('nama')) ?>" maxlength="150" required>
                        <?= field_error($errors, 'nama') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="no_reg" class="form-label">No. Reg <span class="text-danger">*</span></label>
                        <input type="text" name="no_reg" id="no_reg"
                               class="form-control<?= field_invalid($errors, 'no_reg') ?>"
                               value="<?= esc($field('no_reg')) ?>" maxlength="100" required>
                        <?= field_error($errors, 'no_reg') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" id="tanggal"
                               class="form-control<?= field_invalid($errors, 'tanggal') ?>"
                               value="<?= esc($field('tanggal', date('Y-m-d'))) ?>" required>
                        <?= field_error($errors, 'tanggal') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="masa_berlaku" class="form-label">Masa Berlaku <span class="text-danger">*</span></label>
                        <input type="text" name="masa_berlaku" id="masa_berlaku"
                               class="form-control<?= field_invalid($errors, 'masa_berlaku') ?>"
                               value="<?= esc($field('masa_berlaku')) ?>"
                               placeholder="Contoh: 30 Agustus 2023 s.d. 30 Agustus 2028" maxlength="150" required>
                        <?= field_error($errors, 'masa_berlaku') ?>
                    </div>
                    <div class="col-md-3">
                        <label for="kabkota" class="form-label">Kab./Kota <span class="text-danger">*</span></label>
                        <input type="text" name="kabkota" id="kabkota"
                               class="form-control<?= field_invalid($errors, 'kabkota') ?>"
                               value="<?= esc($field('kabkota')) ?>" maxlength="150" required>
                        <?= field_error($errors, 'kabkota') ?>
                    </div>
                    <div class="col-md-3">
                        <label for="provinsi" class="form-label">Provinsi <span class="text-danger">*</span></label>
                        <input type="text" name="provinsi" id="provinsi"
                               class="form-control<?= field_invalid($errors, 'provinsi') ?>"
                               value="<?= esc($field('provinsi')) ?>" maxlength="150" required>
                        <?= field_error($errors, 'provinsi') ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading">
                    <span class="form-section-number">2</span>
                    <div>
                        <h4>Kualifikasi &amp; Izin</h4>
                        <p>Data kualifikasi GANISPH dan izin perusahaan.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="kualifikasi" class="form-label">Kualifikasi <span class="text-danger">*</span></label>
                        <input type="text" name="kualifikasi" id="kualifikasi"
                               class="form-control<?= field_invalid($errors, 'kualifikasi') ?>"
                               value="<?= esc($field('kualifikasi')) ?>"
                               placeholder="Contoh: PENGUJIAN KAYU BULAT" maxlength="255" required>
                        <?= field_error($errors, 'kualifikasi') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="kualifikasi_singkat" class="form-label">Singkatan Kualifikasi <span class="text-danger">*</span></label>
                        <input type="text" name="kualifikasi_singkat" id="kualifikasi_singkat"
                               class="form-control<?= field_invalid($errors, 'kualifikasi_singkat') ?>"
                               value="<?= esc($field('kualifikasi_singkat')) ?>"
                               placeholder="Contoh: PKB" maxlength="50" required>
                        <?= field_error($errors, 'kualifikasi_singkat') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="jenis_izin" class="form-label">Jenis Izin</label>
                        <input type="text" name="jenis_izin" id="jenis_izin"
                               class="form-control<?= field_invalid($errors, 'jenis_izin') ?>"
                               value="<?= esc($field('jenis_izin')) ?>"
                               placeholder="Contoh: PERIZINAN BERUSAHA BERBASIS RISIKO" maxlength="255">
                        <?= field_error($errors, 'jenis_izin') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="jenis_izin_singkat" class="form-label">Singkatan Jenis Izin</label>
                        <input type="text" name="jenis_izin_singkat" id="jenis_izin_singkat"
                               class="form-control<?= field_invalid($errors, 'jenis_izin_singkat') ?>"
                               value="<?= esc($field('jenis_izin_singkat')) ?>"
                               placeholder="Contoh: NIB" maxlength="50">
                        <?= field_error($errors, 'jenis_izin_singkat') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="pemberi_izin" class="form-label">Pemberi Izin</label>
                        <input type="text" name="pemberi_izin" id="pemberi_izin"
                               class="form-control<?= field_invalid($errors, 'pemberi_izin') ?>"
                               value="<?= esc($field('pemberi_izin')) ?>" maxlength="255">
                        <?= field_error($errors, 'pemberi_izin') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="no_izin" class="form-label">No. Izin</label>
                        <input type="text" name="no_izin" id="no_izin"
                               class="form-control<?= field_invalid($errors, 'no_izin') ?>"
                               value="<?= esc($field('no_izin')) ?>" maxlength="255">
                        <?= field_error($errors, 'no_izin') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="tanggal_izin" class="form-label">Tanggal Izin</label>
                        <input type="date" name="tanggal_izin" id="tanggal_izin"
                               class="form-control<?= field_invalid($errors, 'tanggal_izin') ?>"
                               value="<?= esc($field('tanggal_izin')) ?>">
                        <?= field_error($errors, 'tanggal_izin') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="kapasitas_izin" class="form-label">Kapasitas Izin</label>
                        <input type="text" name="kapasitas_izin" id="kapasitas_izin"
                               class="form-control<?= field_invalid($errors, 'kapasitas_izin') ?>"
                               value="<?= esc($field('kapasitas_izin')) ?>"
                               placeholder="Contoh: 480,13 atau Risiko Rendah" maxlength="255">
                        <?= field_error($errors, 'kapasitas_izin') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="jangka_waktu" class="form-label">Jangka Waktu Penugasan <span class="text-danger">*</span></label>
                        <input type="text" name="jangka_waktu" id="jangka_waktu"
                               class="form-control<?= field_invalid($errors, 'jangka_waktu') ?>"
                               value="<?= esc($field('jangka_waktu')) ?>"
                               placeholder="Contoh: 21 Desember 2024 s.d. 31 Desember 2027" maxlength="150" required>
                        <?= field_error($errors, 'jangka_waktu') ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading">
                    <span class="form-section-number">3</span>
                    <div>
                        <h4>Status &amp; Catatan</h4>
                        <p>Status GANISPH, kategori KET., dan catatan tambahan.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="status_ganisph" class="form-label">Status GANISPH <span class="text-danger">*</span></label>
                        <select name="status_ganisph" id="status_ganisph"
                                class="form-select<?= field_invalid($errors, 'status_ganisph') ?>" required>
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?= esc($option, 'attr') ?>" <?= $selectedStatus === $option ? 'selected' : '' ?>>
                                    <?= esc($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'status_ganisph') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="ket" class="form-label">KET. <span class="text-danger">*</span></label>
                        <select name="ket" id="ket"
                                class="form-select<?= field_invalid($errors, 'ket') ?>" required>
                            <?php foreach ($ketOptions as $option): ?>
                                <option value="<?= esc($option, 'attr') ?>" <?= $selectedKet === $option ? 'selected' : '' ?>>
                                    <?= esc($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'ket') ?>
                    </div>
                    <div class="col-md-4">
                        <label for="nilai_t" class="form-label">Nilai T</label>
                        <input type="text" name="nilai_t" id="nilai_t"
                               class="form-control<?= field_invalid($errors, 'nilai_t') ?>"
                               value="<?= esc($field('nilai_t')) ?>" maxlength="100"
                               placeholder="Kolom tambahan pada file Excel">
                        <?= field_error($errors, 'nilai_t') ?>
                    </div>
                    <div class="col-12">
                        <label for="catatan_u" class="form-label">Catatan U</label>
                        <textarea name="catatan_u" id="catatan_u"
                                  class="form-control<?= field_invalid($errors, 'catatan_u') ?>"
                                  rows="3" maxlength="5000"
                                  placeholder="Kolom tambahan pada file Excel (opsional)"><?= esc($field('catatan_u')) ?></textarea>
                        <?= field_error($errors, 'catatan_u') ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <?php if ($isEdit): ?>
                    <a href="<?= site_url('daftar-penugasan') ?>" class="btn btn-light">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </a>
                <?php endif; ?>
                <a href="<?= site_url('daftar-penugasan') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-table me-1"></i>Lihat Data
                </a>
                <button type="submit" class="btn btn-primary btn-save" data-loading-button>
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan &amp; Tulis ke Excel' ?>
                </button>
            </div>
        </form>
    </div>
</div>
