<?php $statusOptions = \App\Models\PenugasanModel::STATUSES; ?>
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="info-banner h-100">
            <span class="info-banner-icon"><i class="bi bi-file-earmark-word"></i></span>
            <div>
                <h4>Surat telaahan siap diterbitkan</h4>
                <p>Setiap surat dibuat dari template resmi GANISPH dan diisi otomatis dari data penugasan pada database. Unduh dalam format DOCX untuk diedit atau dicetak.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card signer-card h-100">
            <div class="card-body">
                <div class="signer-label"><i class="bi bi-pen"></i> Pejabat penandatangan</div>
                <?php if ($official !== null): ?>
                    <div class="signer-name"><?= esc($official['nama']) ?></div>
                    <div class="signer-role"><?= esc($official['jabatan']) ?></div>
                    <div class="signer-nip">NIP <?= esc($official['nip']) ?></div>
                <?php else: ?>
                    <div class="signer-name">Belum ada administrator</div>
                    <div class="signer-role">Tambahkan administrator pada Data Pengguna.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <h3 class="card-title mb-0"><i class="bi bi-collection me-2 text-primary"></i>Daftar Telaahan</h3>
                <div class="card-subtitle"><?= number_format($total, 0, ',', '.') ?> penugasan tersedia</div>
            </div>
            <form action="<?= site_url('telaahan') ?>" method="get" class="table-filter">
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
                        <a href="<?= site_url('telaahan') ?>" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body">
        <?php if ($rows === []): ?>
            <div class="empty-state">
                <span class="empty-icon"><i class="bi bi-file-earmark-x"></i></span>
                <h4>Belum ada telaahan</h4>
                <p>Data telaahan akan muncul setelah penugasan GANISPH ditambahkan.</p>
                <a href="<?= site_url('penugasan') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Tambah Penugasan</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($rows as $row): ?>
                    <div class="col-xl-6">
                        <article class="telaahan-card h-100">
                            <div class="telaahan-top">
                                <span class="doc-chip"><i class="bi bi-file-earmark-word"></i> Telaahan</span>
                                <?= status_badge($row['status']) ?>
                            </div>
                            <h3 class="telaahan-title"><?= esc($row['nama_kegiatan']) ?></h3>
                            <div class="telaahan-number">
                                <i class="bi bi-hash"></i><?= esc($row['nomor_surat']) ?>
                                <span class="dot-separator"></span>
                                <?= esc(tanggal_id($row['tanggal_surat'])) ?>
                            </div>

                            <div class="telaahan-person">
                                <span class="avatar-circle avatar-md avatar-tone-<?= esc(avatar_tone($row['nama_pegawai'])) ?>">
                                    <?= esc(user_initials($row['nama_pegawai'])) ?>
                                </span>
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?= esc($row['nama_pegawai']) ?></div>
                                    <div class="small text-body-secondary text-truncate"><?= esc($row['jabatan']) ?></div>
                                    <div class="small text-body-secondary">NIP <?= esc($row['nip']) ?></div>
                                </div>
                            </div>

                            <div class="telaahan-detail-grid">
                                <div>
                                    <span><i class="bi bi-person-badge"></i>Peran</span>
                                    <strong><?= esc($row['peran']) ?></strong>
                                </div>
                                <div>
                                    <span><i class="bi bi-calendar-range"></i>Pelaksanaan</span>
                                    <strong><?= esc(periode_penugasan($row['tanggal_mulai'], $row['tanggal_selesai'])) ?></strong>
                                </div>
                                <div>
                                    <span><i class="bi bi-geo-alt"></i>Lokasi</span>
                                    <strong><?= esc($row['lokasi']) ?></strong>
                                </div>
                                <div>
                                    <span><i class="bi bi-building"></i>Penyelenggara</span>
                                    <strong><?= esc($row['penyelenggara']) ?></strong>
                                </div>
                            </div>

                            <?php if (! empty($row['keterangan'])): ?>
                                <div class="telaahan-note">
                                    <i class="bi bi-chat-left-text"></i>
                                    <span><?= esc($row['keterangan']) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="telaahan-footer">
                                <span class="text-body-secondary small">
                                    <i class="bi bi-file-earmark-check me-1"></i>Template DOCX resmi
                                </span>
                                <a href="<?= site_url('telaahan/' . (int) $row['id'] . '/docx') ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-download me-1"></i>Download Surat
                                </a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent">
        <div class="small text-body-secondary">
            <i class="bi bi-info-circle me-1"></i>
            Surat telaahan menggunakan pejabat penandatangan administrator utama. Perbarui data administrator melalui menu Data Pengguna.
        </div>
    </div>
</div>
