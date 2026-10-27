<?php if (is_impersonating()): ?>
    <?php $originalAdmin = impersonator_user(); $activeUser = auth_user(); ?>
    <div class="impersonation-banner">
        <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="impersonation-icon"><i class="bi bi-person-badge"></i></span>
                <div>
                    <div class="fw-semibold">Mode Login Sebagai</div>
                    <div class="small">
                        Anda melihat aplikasi sebagai <strong><?= esc($activeUser['nama'] ?? '-') ?></strong>
                        <?php if ($originalAdmin !== null): ?>
                            &middot; administrator asal: <?= esc($originalAdmin['nama']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <form action="<?= site_url('impersonasi/stop') ?>" method="post" class="m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-light impersonation-return">
                    <i class="bi bi-arrow-left-circle me-1"></i>Kembali ke Admin
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>
