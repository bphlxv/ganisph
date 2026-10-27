<?php
$flashMap = [
    'success' => ['class' => 'success', 'title' => 'Berhasil'],
    'error'   => ['class' => 'danger', 'title' => 'Terjadi Kesalahan'],
    'warning' => ['class' => 'warning', 'title' => 'Perhatian'],
    'info'    => ['class' => 'info', 'title' => 'Informasi'],
];
$flashMessages = [];
foreach (array_keys($flashMap) as $key) {
    $flashMessages[$key] = session()->getFlashdata($key);
}
$syncNotes = session()->getFlashdata('sync_notes');
?>
<?php foreach ($flashMap as $key => $meta): ?>
    <?php if ($flashMessages[$key] !== null): ?>
        <div class="alert alert-<?= $meta['class'] ?> alert-dismissible fade show app-alert" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi <?= error_icon($key) ?> alert-icon"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= esc($meta['title']) ?></div>
                    <div><?= esc($flashMessages[$key]) ?></div>
                    <?php if ($key === 'warning' && is_array($syncNotes) && $syncNotes !== []): ?>
                        <ul class="small mb-0 mt-2 ps-3">
                            <?php foreach ($syncNotes as $note): ?>
                                <li><?= esc($note) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (is_array($syncNotes) && $syncNotes !== [] && $flashMessages['warning'] === null): ?>
    <div class="alert alert-info alert-dismissible fade show app-alert" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-arrow-repeat alert-icon"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold">Rincian sinkronisasi Excel</div>
                <ul class="small mb-0 mt-1 ps-3">
                    <?php foreach ($syncNotes as $note): ?>
                        <li><?= esc($note) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    </div>
<?php endif; ?>
