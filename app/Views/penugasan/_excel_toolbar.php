<?php
$excelInfo ??= [
    'exists'   => false,
    'modified' => null,
    'size'     => null,
    'error'    => null,
];
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
                            File Excel akan dibuat otomatis dari data sumber atau database.
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
