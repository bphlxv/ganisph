<?php
$currentUser = auth_user();
$hour        = (int) date('H');
$greeting    = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));

$chartLabels = [];
$chartValues = [];
foreach ($monthlyCounts as $month => $count) {
    $timestamp     = strtotime($month . '-01') ?: time();
    $chartLabels[] = nama_bulan((int) date('n', $timestamp), true) . ' ' . date('y', $timestamp);
    $chartValues[] = (int) $count;
}
?>
<div class="hero-card card mb-4">
    <div class="card-body">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="hero-eyebrow"><i class="bi bi-stars"></i> Dashboard GANISPH</span>
                <h2 class="hero-title"><?= esc($greeting) ?>, <?= esc($currentUser['nama'] ?? 'Pengguna') ?>.</h2>
                <p class="hero-text">
                    Kelola penugasan, pantau agenda, dan terbitkan telaahan dalam satu alur kerja.
                    Data penugasan tersimpan aman di database dan tercermin pada berkas Excel.
                </p>
                <div class="hero-actions">
                    <a href="<?= site_url('penugasan') ?>" class="btn btn-light hero-btn">
                        <i class="bi bi-plus-lg me-2"></i>Buat Penugasan
                    </a>
                    <a href="<?= site_url('telaahan') ?>" class="btn btn-outline-light hero-btn">
                        <i class="bi bi-file-earmark-text me-2"></i>Telaahan Penugasan
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-flex justify-content-lg-end">
                <div class="hero-today">
                    <span class="hero-today-icon"><i class="bi bi-calendar3"></i></span>
                    <div>
                        <small>Hari ini</small>
                        <strong><?= esc(tanggal_id(date('Y-m-d'))) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label">Total Penugasan</div>
                        <div class="stat-value"><?= number_format((int) $stats['total'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-primary"><i class="bi bi-journal-check"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-collection"></i> Seluruh data tercatat</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label">Penugasan Aktif</div>
                        <div class="stat-value"><?= number_format((int) $stats['aktif'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-cyan"><i class="bi bi-activity"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-broadcast"></i> Sedang berjalan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label">Selesai</div>
                        <div class="stat-value"><?= number_format((int) $stats['selesai'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-flag"></i> Telah dilaksanakan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label">Dibatalkan</div>
                        <div class="stat-value"><?= number_format((int) $stats['dibatalkan'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-amber"><i class="bi bi-slash-circle"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-x-circle"></i> Tidak dilanjutkan</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="card-title mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Tren Penugasan</h3>
                    <div class="card-subtitle">Jumlah surat tugas per bulan</div>
                </div>
                <span class="badge badge-soft-primary rounded-pill">6 bulan terakhir</span>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="monthlyChart" aria-label="Grafik jumlah penugasan per bulan" role="img"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-lightning-charge me-2 text-primary"></i>Akses Cepat</h3>
                <div class="card-subtitle">Menu utama GANISPH</div>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="<?= site_url('penugasan') ?>" class="quick-link">
                    <span class="quick-icon quick-icon-blue"><i class="bi bi-journal-plus"></i></span>
                    <span class="quick-copy">
                        <strong>Penugasan GANISPH</strong>
                        <small>Input, unduh, dan sinkronkan Excel</small>
                    </span>
                    <i class="bi bi-arrow-right quick-arrow"></i>
                </a>
                <a href="<?= site_url('telaahan') ?>" class="quick-link">
                    <span class="quick-icon quick-icon-cyan"><i class="bi bi-file-earmark-word"></i></span>
                    <span class="quick-copy">
                        <strong>Telaahan Penugasan</strong>
                        <small>Unduh surat telaahan format DOCX</small>
                    </span>
                    <i class="bi bi-arrow-right quick-arrow"></i>
                </a>
                <?php if (is_admin()): ?>
                    <a href="<?= site_url('pengaturan/users') ?>" class="quick-link">
                        <span class="quick-icon quick-icon-violet"><i class="bi bi-people"></i></span>
                        <span class="quick-copy">
                            <strong>Data Pengguna</strong>
                            <small><?= number_format((int) ($stats['pengguna'] ?? 0), 0, ',', '.') ?> pengguna terdaftar</small>
                        </span>
                        <i class="bi bi-arrow-right quick-arrow"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= site_url('pengaturan') ?>" class="quick-link">
                        <span class="quick-icon quick-icon-violet"><i class="bi bi-key"></i></span>
                        <span class="quick-copy">
                            <strong>Pengaturan Akun</strong>
                            <small>Perbarui password Anda</small>
                        </span>
                        <i class="bi bi-arrow-right quick-arrow"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="card-title mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Penugasan Terbaru</h3>
                    <div class="card-subtitle">Data yang baru ditambahkan</div>
                </div>
                <a href="<?= site_url('penugasan') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    Lihat semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if ($recent === []): ?>
                    <div class="empty-state">
                        <span class="empty-icon"><i class="bi bi-journal-x"></i></span>
                        <h4>Belum ada penugasan</h4>
                        <p>Mulai dengan menambahkan data penugasan GANISPH pertama Anda.</p>
                        <a href="<?= site_url('penugasan') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Penugasan
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle app-table mb-0">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th>Kegiatan</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($row['nama_pegawai'])) ?>">
                                                    <?= esc(user_initials($row['nama_pegawai'])) ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-truncate"><?= esc($row['nama_pegawai']) ?></div>
                                                    <small class="text-body-secondary"><?= esc($row['nip']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cell-main text-truncate" title="<?= esc($row['nama_kegiatan']) ?>"><?= esc($row['nama_kegiatan']) ?></div>
                                            <small class="text-body-secondary"><?= esc($row['nomor_surat']) ?></small>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="small fw-medium"><?= esc(tanggal_singkat($row['tanggal_mulai'])) ?></div>
                                            <small class="text-body-secondary">s.d. <?= esc(tanggal_singkat($row['tanggal_selesai'])) ?></small>
                                        </td>
                                        <td><?= status_badge($row['status']) ?></td>
                                        <td class="text-end">
                                            <a href="<?= site_url('penugasan/' . (int) $row['id'] . '/edit') ?>" class="btn btn-sm btn-icon btn-light" title="Edit penugasan">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="card-title mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>Agenda Terdekat</h3>
                    <div class="card-subtitle">Penugasan aktif yang akan datang</div>
                </div>
                <span class="badge badge-soft-info rounded-pill"><?= count($upcoming) ?></span>
            </div>
            <div class="card-body">
                <?php if ($upcoming === []): ?>
                    <div class="empty-state empty-compact">
                        <span class="empty-icon"><i class="bi bi-calendar2-week"></i></span>
                        <h4>Tidak ada agenda</h4>
                        <p>Belum ada penugasan aktif yang terjadwal.</p>
                    </div>
                <?php else: ?>
                    <div class="agenda-list">
                        <?php foreach ($upcoming as $item): ?>
                            <?php $start = strtotime($item['tanggal_mulai']) ?: time(); ?>
                            <a href="<?= site_url('penugasan/' . (int) $item['id'] . '/edit') ?>" class="agenda-item">
                                <span class="agenda-date">
                                    <strong><?= date('d', $start) ?></strong>
                                    <small><?= esc(nama_bulan((int) date('n', $start), true)) ?></small>
                                </span>
                                <span class="agenda-copy">
                                    <strong class="text-truncate"><?= esc($item['nama_kegiatan']) ?></strong>
                                    <small><i class="bi bi-person me-1"></i><?= esc($item['nama_pegawai']) ?></small>
                                    <small><i class="bi bi-geo-alt me-1"></i><?= esc($item['lokasi']) ?></small>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($upcoming !== []): ?>
                <div class="card-footer bg-transparent text-center">
                    <a href="<?= site_url('penugasan') ?>" class="small link-primary text-decoration-none">
                        Lihat seluruh penugasan <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.ganisphMonthlyChart = <?= json_encode([
        'labels' => $chartLabels,
        'values' => $chartValues,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
