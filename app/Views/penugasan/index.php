<?php
$errors       = session('errors') ?? [];
$isEdit       = $editData !== null;
$formAction   = $isEdit ? site_url('penugasan/' . (int) $editData['id']) : site_url('penugasan');
$selectedUser = null;
foreach ($users as $candidate) {
    if ((int) $candidate['id'] === (int) $selectedUserId) {
        $selectedUser = $candidate;
        break;
    }
}
if ($selectedUser === null && $isEdit) {
    $selectedUser = [
        'nama'    => $editData['nama_pegawai'],
        'nip'     => $editData['nip'],
        'jabatan' => $editData['jabatan'],
    ];
}
$statusOptions = \App\Models\PenugasanModel::STATUSES;
?>
<div class="toolbar-card card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="toolbar-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                <div>
                    <div class="fw-semibold">Database &amp; Excel tersinkron</div>
                    <div class="small text-body-secondary">
                        <?php if ($excelInfo['exists'] && $excelInfo['modified'] !== null): ?>
                            File Excel terakhir diperbarui <?= esc(tanggal_id(date('Y-m-d H:i:s', $excelInfo['modified']), true)) ?>
                            <?php if ($excelInfo['size']): ?>
                                &middot; <?= esc(number_format(($excelInfo['size'] ?? 0) / 1024, 1, ',', '.')) ?> KB
                            <?php endif; ?>
                        <?php else: ?>
                            File Excel akan dibuat otomatis saat data disimpan.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <form action="<?= site_url('penugasan/sync') ?>" method="post" class="m-0" data-loading-form>
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline-warning" data-loading-button>
                        <i class="bi bi-arrow-repeat me-1"></i>Sinkron Excel
                    </button>
                </form>
                <a href="<?= site_url('penugasan/download') ?>" class="btn btn-outline-success">
                    <i class="bi bi-download me-1"></i>Download Excel
                </a>
            </div>
        </div>
        <?php if ($excelInfo['error'] !== null): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2 small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i><?= esc($excelInfo['error']) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

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

<div class="card mb-4 form-card" id="form-penugasan">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h3 class="card-title mb-0">
                <i class="bi bi-pencil-square me-2 text-primary"></i>
                <?= $isEdit ? 'Edit Penugasan' : 'Form Penugasan GANISPH' ?>
            </h3>
            <div class="card-subtitle">
                <?= $isEdit ? 'Perbarui data penugasan yang sudah tersimpan.' : 'Isi data penugasan dengan lengkap dan benar.' ?>
            </div>
        </div>
        <?php if ($isEdit): ?>
            <span class="badge badge-soft-warning rounded-pill"><i class="bi bi-pencil me-1"></i>Mode Edit</span>
        <?php else: ?>
            <span class="badge badge-soft-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i>Data Baru</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form action="<?= $formAction ?>" method="post" novalidate data-loading-form>
            <?= csrf_field() ?>

            <div class="form-section">
                <div class="form-section-heading">
                    <span class="form-section-number">1</span>
                    <div>
                        <h4>Data Pegawai</h4>
                        <p>Pilih pegawai yang menerima penugasan.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label for="user_id" class="form-label">Nama Pegawai <span class="text-danger">*</span></label>
                        <select name="user_id" id="user_id" class="form-select<?= field_invalid($errors, 'user_id') ?>" required>
                            <option value="">Pilih pegawai...</option>
                            <?php foreach ($users as $userOption): ?>
                                <option
                                    value="<?= (int) $userOption['id'] ?>"
                                    data-nip="<?= esc($userOption['nip'], 'attr') ?>"
                                    data-jabatan="<?= esc($userOption['jabatan'], 'attr') ?>"
                                    <?= (int) old('user_id', $selectedUserId) === (int) $userOption['id'] ? 'selected' : '' ?>
                                >
                                    <?= esc($userOption['nama']) ?> &middot; <?= esc($userOption['nip']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'user_id') ?>
                        <?php if ($isEdit && $editData['user_id'] === null): ?>
                            <div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Pegawai asal sudah dihapus. Pilih pegawai pengganti.</div>
                        <?php else: ?>
                            <div class="form-text">Data pengguna dikelola pada menu Data Pengguna (khusus admin).</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="pegawai_nip" class="form-label">NIP</label>
                        <input type="text" id="pegawai_nip" class="form-control bg-body-tertiary" value="<?= esc($selectedUser['nip'] ?? '') ?>" readonly placeholder="Otomatis">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="pegawai_jabatan" class="form-label">Jabatan</label>
                        <input type="text" id="pegawai_jabatan" class="form-control bg-body-tertiary" value="<?= esc($selectedUser['jabatan'] ?? '') ?>" readonly placeholder="Otomatis">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading">
                    <span class="form-section-number">2</span>
                    <div>
                        <h4>Surat Tugas &amp; Kegiatan</h4>
                        <p>Informasi dasar surat tugas dan kegiatan yang dilaksanakan.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nomor_surat" class="form-label">Nomor Surat Tugas <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="nomor_surat"
                            id="nomor_surat"
                            class="form-control<?= field_invalid($errors, 'nomor_surat') ?>"
                            value="<?= esc(old('nomor_surat', $editData['nomor_surat'] ?? '')) ?>"
                            placeholder="Contoh: 800.1.11.1/1234/2026"
                            maxlength="150"
                            required
                        >
                        <?= field_error($errors, 'nomor_surat') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_surat" class="form-label">Tanggal Surat Tugas <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            name="tanggal_surat"
                            id="tanggal_surat"
                            class="form-control<?= field_invalid($errors, 'tanggal_surat') ?>"
                            value="<?= esc(old('tanggal_surat', $editData['tanggal_surat'] ?? date('Y-m-d'))) ?>"
                            required
                        >
                        <?= field_error($errors, 'tanggal_surat') ?>
                    </div>
                    <div class="col-12">
                        <label for="nama_kegiatan" class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="nama_kegiatan"
                            id="nama_kegiatan"
                            class="form-control<?= field_invalid($errors, 'nama_kegiatan') ?>"
                            value="<?= esc(old('nama_kegiatan', $editData['nama_kegiatan'] ?? '')) ?>"
                            placeholder="Contoh: Bimbingan Teknis Pengelolaan Penugasan GANISPH"
                            maxlength="255"
                            required
                        >
                        <?= field_error($errors, 'nama_kegiatan') ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-heading">
                    <span class="form-section-number">3</span>
                    <div>
                        <h4>Pelaksanaan</h4>
                        <p>Peran, penyelenggara, lokasi, dan waktu pelaksanaan penugasan.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="peran" class="form-label">Peran dalam Penugasan <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="peran"
                            id="peran"
                            class="form-control<?= field_invalid($errors, 'peran') ?>"
                            value="<?= esc(old('peran', $editData['peran'] ?? '')) ?>"
                            placeholder="Contoh: Peserta / Narasumber / Pendamping"
                            maxlength="120"
                            required
                        >
                        <?= field_error($errors, 'peran') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="penyelenggara" class="form-label">Penyelenggara <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="penyelenggara"
                            id="penyelenggara"
                            class="form-control<?= field_invalid($errors, 'penyelenggara') ?>"
                            value="<?= esc(old('penyelenggara', $editData['penyelenggara'] ?? '')) ?>"
                            placeholder="Contoh: Badan Kepegawaian Negara"
                            maxlength="200"
                            required
                        >
                        <?= field_error($errors, 'penyelenggara') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="lokasi" class="form-label">Lokasi <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="lokasi"
                            id="lokasi"
                            class="form-control<?= field_invalid($errors, 'lokasi') ?>"
                            value="<?= esc(old('lokasi', $editData['lokasi'] ?? '')) ?>"
                            placeholder="Contoh: Hotel Grand Kecubung, Palangka Raya"
                            maxlength="200"
                            required
                        >
                        <?= field_error($errors, 'lokasi') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select<?= field_invalid($errors, 'status') ?>" required>
                            <?php foreach ($statusOptions as $option): ?>
                                <option value="<?= esc($option, 'attr') ?>" <?= old('status', $editData['status'] ?? 'Aktif') === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'status') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            name="tanggal_mulai"
                            id="tanggal_mulai"
                            class="form-control<?= field_invalid($errors, 'tanggal_mulai') ?>"
                            value="<?= esc(old('tanggal_mulai', $editData['tanggal_mulai'] ?? date('Y-m-d'))) ?>"
                            required
                        >
                        <?= field_error($errors, 'tanggal_mulai') ?>
                    </div>
                    <div class="col-md-6">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            name="tanggal_selesai"
                            id="tanggal_selesai"
                            class="form-control<?= field_invalid($errors, 'tanggal_selesai') ?>"
                            value="<?= esc(old('tanggal_selesai', $editData['tanggal_selesai'] ?? date('Y-m-d'))) ?>"
                            required
                        >
                        <?= field_error($errors, 'tanggal_selesai') ?>
                    </div>
                    <div class="col-12">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea
                            name="keterangan"
                            id="keterangan"
                            class="form-control<?= field_invalid($errors, 'keterangan') ?>"
                            rows="3"
                            maxlength="5000"
                            placeholder="Catatan tambahan mengenai penugasan (opsional)"
                            data-char-count="#keteranganCount"
                        ><?= esc(old('keterangan', $editData['keterangan'] ?? '')) ?></textarea>
                        <div class="d-flex justify-content-between">
                            <?= field_error($errors, 'keterangan') ?>
                            <div class="form-text ms-auto"><span id="keteranganCount">0</span>/5000</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <?php if ($isEdit): ?>
                    <a href="<?= site_url('penugasan') ?>" class="btn btn-light">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </a>
                <?php endif; ?>
                <a href="#daftar-penugasan" class="btn btn-outline-secondary">
                    <i class="bi bi-table me-1"></i>Lihat Data
                </a>
                <button type="submit" class="btn btn-primary btn-save" data-loading-button>
                    <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Simpan &amp; Tulis ke Excel' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card" id="daftar-penugasan">
    <div class="card-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h3 class="card-title mb-0"><i class="bi bi-table me-2 text-primary"></i>Daftar Penugasan</h3>
                <div class="card-subtitle"><?= number_format(count($rows), 0, ',', '.') ?> data ditemukan</div>
            </div>
            <form action="<?= site_url('penugasan') ?>" method="get" class="table-filter">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cari nama, NIP, kegiatan..." value="<?= esc($keyword) ?>">
                    <select name="status" class="form-select" aria-label="Filter status">
                        <option value="">Semua status</option>
                        <?php foreach ($statusOptions as $option): ?>
                            <option value="<?= esc($option, 'attr') ?>" <?= $status === $option ? 'selected' : '' ?>><?= esc($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if ($keyword !== '' || $status !== ''): ?>
                        <a href="<?= site_url('penugasan') ?>" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($rows === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-inbox"></i></span>
                <h4>Belum ada data penugasan</h4>
                <p>Tambahkan data baru melalui form di atas, atau ubah kata kunci pencarian Anda.</p>
                <a href="#form-penugasan" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Tambah Penugasan</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle app-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 52px;">No</th>
                            <th>Pegawai</th>
                            <th>Surat Tugas / Kegiatan</th>
                            <th>Pelaksanaan</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td class="text-center text-body-secondary"><?= $index + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($row['nama_pegawai'])) ?>">
                                            <?= esc(user_initials($row['nama_pegawai'])) ?>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate"><?= esc($row['nama_pegawai']) ?></div>
                                            <small class="text-body-secondary d-block text-truncate"><?= esc($row['nip']) ?></small>
                                            <small class="text-body-secondary d-block text-truncate"><?= esc($row['jabatan']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-main"><?= esc($row['nama_kegiatan']) ?></div>
                                    <small class="text-body-secondary d-block"><i class="bi bi-hash"></i><?= esc($row['nomor_surat']) ?> &middot; <?= esc(tanggal_singkat($row['tanggal_surat'])) ?></small>
                                    <small class="text-body-secondary d-block"><i class="bi bi-person-badge me-1"></i><?= esc($row['peran']) ?></small>
                                </td>
                                <td>
                                    <div class="small fw-medium text-nowrap"><?= esc(tanggal_singkat($row['tanggal_mulai'])) ?> &ndash; <?= esc(tanggal_singkat($row['tanggal_selesai'])) ?></div>
                                    <small class="text-body-secondary d-block"><i class="bi bi-geo-alt me-1"></i><?= esc($row['lokasi']) ?></small>
                                    <small class="text-body-secondary d-block"><i class="bi bi-building me-1"></i><?= esc($row['penyelenggara']) ?></small>
                                </td>
                                <td><?= status_badge($row['status']) ?></td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="<?= site_url('telaahan/' . (int) $row['id'] . '/docx') ?>" class="btn btn-sm btn-icon btn-light text-success" title="Download telaahan DOCX">
                                            <i class="bi bi-file-earmark-word"></i>
                                        </a>
                                        <a href="<?= site_url('penugasan/' . (int) $row['id'] . '/edit') ?>" class="btn btn-sm btn-icon btn-light text-primary" title="Edit penugasan">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="<?= site_url('penugasan/' . (int) $row['id'] . '/delete') ?>" method="post" class="m-0" data-confirm="Hapus penugasan &quot;<?= esc($row['nama_kegiatan'], 'attr') ?>&quot; untuk <?= esc($row['nama_pegawai'], 'attr') ?>? Data pada database dan Excel juga akan dihapus.">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Hapus penugasan">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-body-secondary">
            <span><i class="bi bi-info-circle me-1"></i>Perubahan data langsung disimpan ke database dan file Excel.</span>
            <span>Gunakan tombol <strong>Sinkron Excel</strong> untuk membaca perubahan manual dari file Excel.</span>
        </div>
    </div>
</div>
