<?php
$hasFilter = $keyword !== '' || $status !== '' || $ket !== '';

$pageUrl = static function (int $targetPage) use ($keyword, $status, $ket, $perPage): string {
    $query = array_filter([
        'q'        => $keyword,
        'status'   => $status,
        'ket'      => $ket,
        'per_page' => $perPage,
        'page'     => $targetPage,
    ], static fn (mixed $value): bool => $value !== '' && $value !== null);

    return site_url('daftar-penugasan') . '?' . http_build_query($query);
};

$paginationStart = max(1, $page - 2);
$paginationEnd   = min($totalPages, $page + 2);
$resetUrl        = site_url('daftar-penugasan') . ($perPage !== 10 ? '?per_page=' . $perPage : '');
?>
<?= $this->include('penugasan/_excel_toolbar') ?>

<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h3 class="card-title mb-0"><i class="bi bi-table me-2 text-primary"></i>Daftar Penugasan</h3>
                <div class="card-subtitle">
                    <?= number_format($total, 0, ',', '.') ?> data ditemukan
                    <?php if ($hasFilter): ?>
                        &middot; filter aktif
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?= site_url('penugasan') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Tambah Penugasan
            </a>
        </div>
    </div>
    <div class="card-body border-bottom py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <form action="<?= site_url('daftar-penugasan') ?>" method="get" class="table-filter">
                <input type="hidden" name="per_page" value="<?= $perPage ?>">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control"
                           placeholder="Cari nama, No. Reg, perusahaan, No. Izin..."
                           value="<?= esc($keyword) ?>">
                    <select name="status" class="form-select" aria-label="Filter status GANISPH">
                        <option value="">Semua status</option>
                        <?php foreach ($statusOptions as $option): ?>
                            <option value="<?= esc($option, 'attr') ?>" <?= $status === $option ? 'selected' : '' ?>>
                                <?= esc($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ket" class="form-select" aria-label="Filter KET.">
                        <option value="">Semua KET.</option>
                        <?php foreach ($ketOptions as $option): ?>
                            <option value="<?= esc($option, 'attr') ?>" <?= $ket === $option ? 'selected' : '' ?>>
                                <?= esc($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Cari</button>
                    <?php if ($hasFilter): ?>
                        <a href="<?= esc($resetUrl, 'attr') ?>" class="btn btn-outline-secondary" title="Reset filter">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <form action="<?= site_url('daftar-penugasan') ?>" method="get" class="per-page-form">
                <?php foreach (['q' => $keyword, 'status' => $status, 'ket' => $ket] as $name => $value): ?>
                    <?php if ($value !== ''): ?>
                        <input type="hidden" name="<?= esc($name, 'attr') ?>" value="<?= esc($value, 'attr') ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <label for="per_page" class="mb-0">Tampilkan</label>
                <input type="number" id="per_page" name="per_page" class="form-control form-control-sm"
                       min="10" max="200" step="1" value="<?= $perPage ?>" inputmode="numeric">
                <span>per halaman</span>
                <button type="submit" class="btn btn-outline-primary btn-sm">Terapkan</button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if ($rows === []): ?>
            <div class="empty-state">
                <?php if ($hasFilter): ?>
                    <span class="empty-icon"><i class="bi bi-search"></i></span>
                    <h4>Tidak ada data yang cocok</h4>
                    <p>Ubah kata kunci atau reset filter untuk melihat seluruh daftar penugasan.</p>
                    <a href="<?= esc($resetUrl, 'attr') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                    </a>
                <?php else: ?>
                    <span class="empty-icon"><i class="bi bi-inbox"></i></span>
                    <h4>Belum ada data penugasan</h4>
                    <p>Tambahkan data baru melalui form Penugasan GANISPH.</p>
                    <a href="<?= site_url('penugasan') ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Penugasan
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-scroll-top" data-scroll-top data-scroll-target="daftarTableScroll">
                <div class="table-scroll-top-inner"></div>
            </div>
            <div class="table-responsive" id="daftarTableScroll">
                <table class="table table-hover align-middle app-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 72px;">No</th>
                            <th>GANISPH</th>
                            <th>Perusahaan</th>
                            <th>Kualifikasi / Izin</th>
                            <th>Jangka Waktu</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr>
                                <td class="text-center text-body-secondary"><?= $from + $index ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($row['nama'])) ?>">
                                            <?= esc(user_initials($row['nama'])) ?>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="fw-semibold text-truncate"><?= esc($row['nama']) ?></div>
                                            <small class="text-body-secondary d-block text-truncate">
                                                <i class="bi bi-hash"></i><?= esc($row['no_reg']) ?>
                                            </small>
                                            <small class="text-body-secondary d-block text-truncate">
                                                <?= esc($row['kabkota']) ?>, <?= esc($row['provinsi']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-main"><?= esc($row['perusahaan']) ?></div>
                                    <small class="text-body-secondary d-block">E-NTT: <?= esc($row['entt']) ?></small>
                                    <small class="text-body-secondary d-block">
                                        <i class="bi bi-calendar3 me-1"></i><?= esc(tanggal_singkat($row['tanggal'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= esc($row['kualifikasi_singkat']) ?></div>
                                    <small class="text-body-secondary d-block text-truncate" style="max-width: 260px;">
                                        <?= esc($row['kualifikasi']) ?>
                                    </small>
                                    <?php if (! empty($row['jenis_izin_singkat']) || ! empty($row['no_izin'])): ?>
                                        <small class="text-body-secondary d-block text-truncate" style="max-width: 260px;">
                                            <i class="bi bi-file-earmark-text me-1"></i>
                                            <?= esc($row['jenis_izin_singkat'] ?: $row['jenis_izin']) ?>
                                            <?php if (! empty($row['no_izin'])): ?>
                                                &middot; <?= esc($row['no_izin']) ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-medium"><?= esc($row['jangka_waktu']) ?></div>
                                    <small class="text-body-secondary d-block">Masa berlaku: <?= esc($row['masa_berlaku']) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1 align-items-start">
                                        <?= status_badge($row['status_ganisph']) ?>
                                        <?= ket_badge($row['ket']) ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a href="<?= site_url('telaahan/' . (int) $row['id'] . '/docx') ?>"
                                           class="btn btn-sm btn-icon btn-light text-success" title="Download telaahan DOCX">
                                            <i class="bi bi-file-earmark-word"></i>
                                        </a>
                                        <a href="<?= site_url('penugasan/' . (int) $row['id'] . '/edit') ?>"
                                           class="btn btn-sm btn-icon btn-light text-primary" title="Edit penugasan">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="<?= site_url('penugasan/' . (int) $row['id'] . '/delete') ?>"
                                              method="post" class="m-0"
                                              data-confirm="Hapus data &quot;<?= esc($row['nama'], 'attr') ?>&quot; dari <?= esc($row['perusahaan'], 'attr') ?>? Data pada database dan Excel juga akan dihapus.">
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
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="small text-body-secondary">
                <?php if ($total > 0): ?>
                    Menampilkan <strong><?= number_format($from, 0, ',', '.') ?></strong>&ndash;<strong><?= number_format($to, 0, ',', '.') ?></strong>
                    dari <strong><?= number_format($total, 0, ',', '.') ?></strong> penugasan
                <?php else: ?>
                    Tidak ada data untuk ditampilkan
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Navigasi halaman daftar penugasan">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item<?= $page <= 1 ? ' disabled' : '' ?>">
                            <?php if ($page <= 1): ?>
                                <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= esc($pageUrl($page - 1), 'attr') ?>" aria-label="Halaman sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                        </li>

                        <?php if ($paginationStart > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= esc($pageUrl(1), 'attr') ?>">1</a>
                            </li>
                            <?php if ($paginationStart > 2): ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $paginationStart; $pageNumber <= $paginationEnd; $pageNumber++): ?>
                            <li class="page-item<?= $pageNumber === $page ? ' active' : '' ?>">
                                <?php if ($pageNumber === $page): ?>
                                    <span class="page-link" aria-current="page"><?= $pageNumber ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?= esc($pageUrl($pageNumber), 'attr') ?>"><?= $pageNumber ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>

                        <?php if ($paginationEnd < $totalPages): ?>
                            <?php if ($paginationEnd < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= esc($pageUrl($totalPages), 'attr') ?>"><?= $totalPages ?></a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item<?= $page >= $totalPages ? ' disabled' : '' ?>">
                            <?php if ($page >= $totalPages): ?>
                                <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= esc($pageUrl($page + 1), 'attr') ?>" aria-label="Halaman berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
