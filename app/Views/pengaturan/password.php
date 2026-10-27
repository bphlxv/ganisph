<?php
$errors     = session('errors') ?? [];
$currentUser = auth_user();
?>
<div class="row g-4">
    <div class="col-lg-7 col-xl-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-key me-2 text-primary"></i>Form Ganti Password</h3>
                <div class="card-subtitle">Gunakan password yang kuat dan tidak mudah ditebak.</div>
            </div>
            <div class="card-body">
                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-octagon-fill me-1"></i>Periksa kembali isian password Anda.
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('pengaturan/password') ?>" method="post" data-loading-form novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control<?= field_invalid($errors, 'current_password') ?>" placeholder="Masukkan password saat ini" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-password-toggle="#current_password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                        <?= field_error($errors, 'current_password') ?>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control<?= field_invalid($errors, 'new_password') ?>" placeholder="Minimal <?= (int) ganisph_config()->minPasswordLength ?> karakter" autocomplete="new-password" data-password-strength="#passwordStrength" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-password-toggle="#new_password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="password-strength mt-2" id="passwordStrength" data-visible="false">
                            <div class="password-strength-bar"><span></span></div>
                            <div class="small text-body-secondary mt-1">Kekuatan password: <strong data-strength-label>-</strong></div>
                        </div>
                        <?= field_error($errors, 'new_password') ?>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control<?= field_invalid($errors, 'confirm_password') ?>" placeholder="Ulangi password baru" autocomplete="new-password" required>
                            <button type="button" class="btn btn-outline-secondary password-toggle" data-password-toggle="#confirm_password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                        <?= field_error($errors, 'confirm_password') ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary" data-loading-button>
                            <i class="bi bi-shield-check me-1"></i>Simpan Password Baru
                        </button>
                        <a href="<?= site_url('dashboard') ?>" class="btn btn-light">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 col-xl-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-person-circle me-2 text-primary"></i>Informasi Akun</h3>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar-circle avatar-lg avatar-tone-<?= esc(avatar_tone($currentUser['nama'] ?? '')) ?>">
                        <?= esc(user_initials($currentUser['nama'] ?? '')) ?>
                    </span>
                    <div class="min-w-0">
                        <div class="fw-semibold fs-5 text-truncate"><?= esc($currentUser['nama'] ?? '-') ?></div>
                        <div class="text-body-secondary"><?= esc($currentUser['jabatan'] ?? '-') ?></div>
                        <div class="small text-body-secondary">NIP <?= esc($currentUser['nip'] ?? '-') ?></div>
                    </div>
                </div>
                <hr>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-body-secondary small">Hak akses</span>
                    <?php if (is_admin()): ?>
                        <span class="badge badge-soft-primary"><i class="bi bi-shield-check me-1"></i>Administrator</span>
                    <?php else: ?>
                        <span class="badge badge-soft-secondary"><i class="bi bi-person me-1"></i>Pengguna</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card tips-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="tips-icon"><i class="bi bi-lightbulb"></i></span>
                    <h4 class="mb-0">Tips Keamanan Password</h4>
                </div>
                <ul class="tips-list">
                    <li>Gunakan minimal <?= (int) ganisph_config()->minPasswordLength ?> karakter.</li>
                    <li>Kombinasikan huruf besar, huruf kecil, angka, dan simbol.</li>
                    <li>Hindari menggunakan NIP, nama, atau tanggal lahir.</li>
                    <li>Jangan bagikan password kepada siapa pun.</li>
                    <li>Ganti password secara berkala.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
