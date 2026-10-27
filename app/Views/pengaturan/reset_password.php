<?php $defaultPass = $defaultPass ?? ganisph_config()->defaultPassword; ?>
<div class="alert alert-warning app-alert">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
        <div>
            <div class="fw-semibold">Reset password ke password awal</div>
            <div>Password pengguna akan diubah menjadi <strong><?= esc($defaultPass) ?></strong>. Sampaikan password baru tersebut kepada pengguna dan minta segera menggantinya melalui menu Ganti Password.</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-0"><i class="bi bi-arrow-counterclockwise me-2 text-primary"></i>Reset Password Pengguna</h3>
                <div class="card-subtitle">Pilih pengguna yang akan direset passwordnya.</div>
            </div>
            <span class="badge badge-soft-secondary rounded-pill"><?= number_format(count($users), 0, ',', '.') ?> pengguna</span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($users === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-people"></i></span>
                <h4>Belum ada pengguna lain</h4>
                <p>Tambahkan pengguna terlebih dahulu melalui menu Data Pengguna.</p>
                <a href="<?= site_url('pengaturan/users/create') ?>" class="btn btn-primary btn-sm">Tambah Pengguna</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle app-table mb-0">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>NIP</th>
                            <th>Jabatan</th>
                            <th>Hak Akses</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($user['nama'])) ?>"><?= esc(user_initials($user['nama'])) ?></span>
                                        <div class="fw-semibold"><?= esc($user['nama']) ?></div>
                                    </div>
                                </td>
                                <td><span class="font-monospace small"><?= esc($user['nip']) ?></span></td>
                                <td class="text-body-secondary"><?= esc($user['jabatan']) ?></td>
                                <td>
                                    <?php if ((int) $user['is_admin'] === 1): ?>
                                        <span class="badge badge-soft-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-secondary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <form action="<?= site_url('pengaturan/reset-password/' . (int) $user['id']) ?>" method="post" class="m-0 d-inline-block" data-confirm="Reset password <?= esc($user['nama'], 'attr') ?> menjadi &quot;<?= esc($defaultPass, 'attr') ?>&quot;?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset ke <?= esc($defaultPass) ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent">
        <div class="small text-body-secondary">
            <i class="bi bi-info-circle me-1"></i>Akun Anda tidak ditampilkan di sini. Gunakan menu <a href="<?= site_url('pengaturan') ?>">Ganti Password</a> untuk mengubah password akun sendiri.
        </div>
    </div>
</div>
