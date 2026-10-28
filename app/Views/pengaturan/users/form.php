<?php
$errors  = session('errors') ?? [];
$isEdit  = $isEdit ?? false;
$isSelf  = $isEdit && (int) $user['id'] === (int) auth_id();
$formAction = $isEdit ? site_url('pengaturan/users/' . (int) $user['id']) : site_url('pengaturan/users');
$isAdminValue = old('is_admin', $isEdit ? (int) $user['is_admin'] : 0);
?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h3 class="card-title mb-0">
                            <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?> me-2 text-primary"></i>
                            <?= $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?>
                        </h3>
                        <div class="card-subtitle">
                            <?= $isEdit ? 'Perbarui identitas dan hak akses pengguna.' : 'Lengkapi data pengguna. Password awal otomatis dibuat.' ?>
                        </div>
                    </div>
                    <?php if ($isSelf): ?>
                        <span class="badge badge-soft-info rounded-pill">Akun Anda</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger app-alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill alert-icon"></i>
                            <div>
                                <div class="fw-semibold">Periksa kembali data pengguna</div>
                                <ul class="small mb-0 mt-1 ps-3">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= esc($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= $formAction ?>" method="post" novalidate data-loading-form>
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="nama"
                                id="nama"
                                class="form-control<?= field_invalid($errors, 'nama') ?>"
                                value="<?= esc(old('nama', $user['nama'] ?? '')) ?>"
                                placeholder="Contoh: Supriadi"
                                maxlength="150"
                                required
                            >
                            <?= field_error($errors, 'nama') ?>
                        </div>
                        <div class="col-md-5">
                            <label for="nip" class="form-label">NIP <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="nip"
                                id="nip"
                                class="form-control font-monospace<?= field_invalid($errors, 'nip') ?>"
                                value="<?= esc(old('nip', $user['nip'] ?? '')) ?>"
                                placeholder="18 digit NIP"
                                inputmode="numeric"
                                maxlength="30"
                                autocomplete="off"
                                required
                            >
                            <?= field_error($errors, 'nip') ?>
                            <div class="form-text">Gunakan NIP yang valid dan belum terdaftar.</div>
                        </div>
                        <div class="col-12">
                            <label for="jabatan" class="form-label">Jabatan <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="jabatan"
                                id="jabatan"
                                class="form-control<?= field_invalid($errors, 'jabatan') ?>"
                                value="<?= esc(old('jabatan', $user['jabatan'] ?? '')) ?>"
                                placeholder="Contoh: Pranata SDM Aparatur Terampil"
                                maxlength="200"
                                required
                            >
                            <?= field_error($errors, 'jabatan') ?>
                        </div>

                        <div class="col-12">
                            <div class="role-switch">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_admin" name="is_admin" value="1" <?= (int) $isAdminValue === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_admin">
                                        <span class="fw-semibold">Jadikan Administrator</span>
                                        <span class="d-block small text-body-secondary">Administrator dapat mengelola pengguna, mereset password, dan mencadangkan database.</span>
                                    </label>
                                </div>
                                <?= field_error($errors, 'is_admin') ?>
                            </div>
                            <?php if ($isSelf): ?>
                                <div class="form-text text-warning mt-2">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Mencabut hak admin dari akun sendiri hanya dapat dilakukan jika masih ada administrator lain.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-4 small">
                        <i class="bi bi-key me-1"></i>
                        <?php if ($isEdit): ?>
                            Password tidak diubah melalui form ini. Gunakan menu <a href="<?= site_url('pengaturan/reset-password') ?>">Reset Password</a> untuk mengembalikan password ke <?= esc(ganisph_config()->defaultPassword) ?>.
                        <?php else: ?>
                            Password awal pengguna baru adalah <strong><?= esc(ganisph_config()->defaultPassword) ?></strong>. Pengguna dapat menggantinya melalui menu Ganti Password.
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary" data-loading-button>
                            <i class="bi bi-save me-1"></i><?= $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' ?>
                        </button>
                        <a href="<?= site_url('pengaturan/users') ?>" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card tips-card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="tips-icon"><i class="bi bi-info-circle"></i></span>
                    <h4 class="mb-0">Ketentuan Data</h4>
                </div>
                <ul class="tips-list">
                    <li>Nama, NIP, dan jabatan wajib diisi.</li>
                    <li>NIP harus unik dan hanya berisi angka.</li>
                    <li>Akun pengguna dipakai untuk login dan pencatatan pembuat data penugasan.</li>
                    <li>Centang administrator untuk memberi akses penuh.</li>
                    <li>Minimal satu administrator harus selalu tersedia.</li>
                </ul>
            </div>
        </div>

        <?php if ($isEdit): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0"><i class="bi bi-person-vcard me-2 text-primary"></i>Ringkasan Akun</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-circle avatar-md avatar-tone-<?= esc(avatar_tone($user['nama'])) ?>"><?= esc(user_initials($user['nama'])) ?></span>
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate"><?= esc($user['nama']) ?></div>
                            <div class="small text-body-secondary">NIP <?= esc($user['nip']) ?></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-3">
                        <span class="small text-body-secondary">Hak akses saat ini</span>
                        <?php if ((int) $user['is_admin'] === 1): ?>
                            <span class="badge badge-soft-primary">Administrator</span>
                        <?php else: ?>
                            <span class="badge badge-soft-secondary">User</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
