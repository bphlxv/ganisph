<?php
$currentId = (int) auth_id();
$showSearch = $keyword !== '';
?>
<div class="card mb-4">
    <div class="card-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h3 class="card-title mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>Data Pengguna</h3>
                <div class="card-subtitle">Kelola akun admin dan user aplikasi GANISPH.</div>
            </div>
            <form action="<?= site_url('pengaturan/users') ?>" method="get" class="table-filter">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cari nama, NIP, jabatan..." value="<?= esc($keyword) ?>">
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if ($showSearch): ?>
                        <a href="<?= site_url('pengaturan/users') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($users === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-person-x"></i></span>
                <h4>Pengguna tidak ditemukan</h4>
                <p><?= $showSearch ? 'Tidak ada pengguna yang sesuai dengan pencarian Anda.' : 'Belum ada pengguna yang terdaftar.' ?></p>
                <?php if ($showSearch): ?>
                    <a href="<?= site_url('pengaturan/users') ?>" class="btn btn-outline-secondary btn-sm">Reset pencarian</a>
                <?php else: ?>
                    <a href="<?= site_url('pengaturan/users/create') ?>" class="btn btn-primary btn-sm">Tambah Pengguna</a>
                <?php endif; ?>
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
                            <th class="text-end" style="min-width: 250px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $isSelf = (int) $user['id'] === $currentId; ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($user['nama'])) ?>"><?= esc(user_initials($user['nama'])) ?></span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate">
                                                <?= esc($user['nama']) ?>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge badge-soft-info ms-1">Akun Anda</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-body-secondary">Dibuat <?= esc(tanggal_id($user['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <span class="font-monospace small"><?= esc($user['nip']) ?></span>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-body-secondary copy-btn" data-copy="<?= esc($user['nip'], 'attr') ?>" title="Salin NIP">
                                            <i class="bi bi-copy"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-body-secondary"><?= esc($user['jabatan']) ?></td>
                                <td>
                                    <?php if ((int) $user['is_admin'] === 1): ?>
                                        <span class="badge badge-soft-primary"><i class="bi bi-shield-check me-1"></i>Administrator</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-secondary"><i class="bi bi-person me-1"></i>User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="<?= site_url('pengaturan/users/' . (int) $user['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Edit data dan hak akses">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <?php if (! $isSelf): ?>
                                            <form action="<?= site_url('pengaturan/users/' . (int) $user['id'] . '/impersonate') ?>" method="post" class="m-0" data-confirm="Login sebagai <?= esc($user['nama'], 'attr') ?>? Anda akan melihat aplikasi dengan hak akses pengguna tersebut.">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Login sebagai pengguna ini">
                                                    <i class="bi bi-box-arrow-in-right"></i>
                                                </button>
                                            </form>
                                            <form action="<?= site_url('pengaturan/users/' . (int) $user['id'] . '/delete') ?>" method="post" class="m-0" data-confirm="Hapus pengguna <?= esc($user['nama'], 'attr') ?>? Riwayat penugasannya tetap tersimpan, tetapi akun ini tidak dapat login lagi.">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus pengguna">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Tidak dapat login sebagai akun sendiri">
                                                <i class="bi bi-box-arrow-in-right"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Tidak dapat menghapus akun sendiri">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
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
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="small text-body-secondary">
                <span class="badge badge-soft-primary me-1"><?= number_format((int) $adminCount, 0, ',', '.') ?> admin</span>
                <span class="badge badge-soft-secondary me-2"><?= number_format((int) ($userCount ?? 0), 0, ',', '.') ?> user</span>
                Password awal pengguna baru: <strong><?= esc($defaultPass) ?></strong>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= site_url('pengaturan/users/create') ?>" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i>Tambah User
                </a>
                <a href="<?= site_url('pengaturan/backup') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-database-down me-1"></i>Backup Database
                </a>
            </div>
        </div>
        <div class="small text-body-secondary mt-2">
            <i class="bi bi-info-circle me-1"></i>Akun administrator utama tidak dapat dihapus jika hanya tersisa satu admin. Password pengguna baru dapat direset melalui menu Reset Password.
        </div>
    </div>
</div>
