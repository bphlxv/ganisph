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

$maxKualifikasi = 1;
foreach ($kualifikasi as $item) {
    $maxKualifikasi = max($maxKualifikasi, (int) $item['jumlah']);
}
?>
<div class="hero-card card mb-4">
    <div class="card-body">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="hero-eyebrow"><i class="bi bi-stars"></i> Dashboard GANISPH</span>
                <h2 class="hero-title"><?= esc($greeting) ?>, <?= esc($currentUser['nama'] ?? 'Pengguna') ?>.</h2>
                <p class="hero-text">
                    Pantau data penugasan GANISPH, kualifikasi, dan status ASN/NON-ASN.
                    Data tersimpan di database dan tercermin pada berkas Excel resmi.
                </p>
                <div class="hero-actions">
                    <a href="<?= site_url('penugasan') ?>" class="btn btn-outline-light hero-btn">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Penugasan
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
                        <div class="stat-label">GANISPH ASN</div>
                        <div class="stat-value"><?= number_format((int) $stats['asn'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-cyan"><i class="bi bi-person-badge"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-check2-circle"></i> Berstatus ASN</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label">GANISPH NON-ASN</div>
                        <div class="stat-value"><?= number_format((int) $stats['non_asn'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-green"><i class="bi bi-person-workspace"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-people"></i> Berstatus NON-ASN</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="stat-label">Perusahaan</div>
                        <div class="stat-value"><?= number_format((int) $stats['perusahaan'], 0, ',', '.') ?></div>
                    </div>
                    <span class="stat-icon stat-icon-amber"><i class="bi bi-building"></i></span>
                </div>
                <div class="stat-foot"><i class="bi bi-briefcase"></i> Perusahaan unik</div>
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
                    <div class="card-subtitle">Jumlah data berdasarkan tanggal formulir</div>
                </div>
                <span class="badge badge-soft-primary rounded-pill">12 bulan terakhir data</span>
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
                        <small>Tambah dan perbarui data penugasan</small>
                    </span>
                    <i class="bi bi-arrow-right quick-arrow"></i>
                </a>
                <a href="<?= site_url('daftar-penugasan') ?>" class="quick-link">
                    <span class="quick-icon quick-icon-green"><i class="bi bi-table"></i></span>
                    <span class="quick-copy">
                        <strong>Daftar Penugasan</strong>
                        <small>Lihat daftar dengan paginasi dan filter</small>
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
                    <div class="card-subtitle">Berdasarkan tanggal formulir terbaru</div>
                </div>
                <a href="<?= site_url('daftar-penugasan') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                    Lihat semua <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if ($recent === []): ?>
                    <div class="empty-state">
                        <span class="empty-icon"><i class="bi bi-journal-x"></i></span>
                        <h4>Belum ada penugasan</h4>
                        <p>Mulai dengan menambahkan data penugasan GANISPH pertama Anda.</p>
                        <a href="<?= site_url('penugasan') ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Penugasan
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle app-table mb-0">
                            <thead>
                                <tr>
                                    <th>GANISPH</th>
                                    <th>Perusahaan</th>
                                    <th>Kualifikasi</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($row['nama'])) ?>">
                                                    <?= esc(user_initials($row['nama'])) ?>
                                                </span>
                                                <div class="min-w-0">
                                                    <div class="fw-semibold text-truncate"><?= esc($row['nama']) ?></div>
                                                    <small class="text-body-secondary">No. Reg <?= esc($row['no_reg']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="cell-main text-truncate" title="<?= esc($row['perusahaan']) ?>"><?= esc($row['perusahaan']) ?></div>
                                            <small class="text-body-secondary">E-NTT <?= esc($row['entt']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?= esc($row['kualifikasi_singkat']) ?></div>
                                            <small class="text-body-secondary"><?= esc($row['kabkota']) ?></small>
                                        </td>
                                        <td class="text-nowrap"><?= esc(tanggal_singkat($row['tanggal'])) ?></td>
                                        <td>
                                            <div class="d-flex flex-column gap-1 align-items-start">
                                                <?= status_badge($row['status_ganisph']) ?>
                                                <?= ket_badge($row['ket']) ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= site_url('penugasan/' . (int) $row['id'] . '/edit') ?>"
                                               class="btn btn-sm btn-icon btn-light" title="Edit penugasan">
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
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="bi bi-pie-chart me-2 text-primary"></i>Sebaran Kualifikasi</h3>
                <div class="card-subtitle">Enam kualifikasi terbanyak</div>
            </div>
            <div class="card-body">
                <?php if ($kualifikasi === []): ?>
                    <div class="empty-state empty-compact">
                        <span class="empty-icon"><i class="bi bi-bar-chart"></i></span>
                        <h4>Belum ada data</h4>
                        <p>Sebaran kualifikasi akan tampil setelah data tersedia.</p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($kualifikasi as $item): ?>
                            <?php $percent = (int) round(((int) $item['jumlah'] / $maxKualifikasi) * 100); ?>
                            <div>
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <strong><?= esc($item['kualifikasi_singkat']) ?></strong>
                                    <span class="text-body-secondary"><?= number_format((int) $item['jumlah'], 0, ',', '.') ?></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar"
                                         style="width: <?= $percent ?>%"
                                         aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr class="my-4">

                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold small">Kategori KET.</span>
                    <span class="text-body-secondary small">Jumlah</span>
                </div>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($ketCounts as $ket => $count): ?>
                        <div class="d-flex align-items-center justify-content-between">
                            <?= ket_badge($ket) ?>
                            <span class="small fw-medium"><?= number_format((int) $count, 0, ',', '.') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.ganisphMonthlyChart = <?= json_encode([
        'labels' => $chartLabels,
        'values' => $chartValues,
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
</script>
