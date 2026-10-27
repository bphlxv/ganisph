<?php $currentUser = auth_user(); ?>
<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link nav-icon-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Buka atau tutup menu">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-lg-none">
                <a class="nav-link mobile-brand" href="<?= site_url('dashboard') ?>">
                    <img src="<?= base_url('assets/img/logo-mark.svg') ?>" alt="" width="26" height="26">
                    <span>GANISPH</span>
                </a>
            </li>
            <li class="nav-item d-none d-md-flex align-items-center ms-2">
                <span class="header-context d-none d-xl-inline-flex align-items-center gap-2">
                    <i class="bi bi-shield-check"></i>
                    <span><?= esc($pageTitle ?? 'Dashboard') ?></span>
                </span>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center gap-1">
            <?php if (is_impersonating()): ?>
                <li class="nav-item d-none d-md-block">
                    <span class="badge badge-soft-warning rounded-pill impersonation-chip">
                        <i class="bi bi-person-badge me-1"></i>Mode Login Sebagai
                    </span>
                </li>
            <?php endif; ?>

            <li class="nav-item">
                <button type="button" class="nav-link nav-icon-link theme-toggle" data-theme-toggle aria-label="Ganti mode terang atau gelap" title="Ganti mode tampilan">
                    <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
                    <i class="bi bi-sun-fill theme-icon-light"></i>
                </button>
            </li>

            <?php if ($currentUser !== null): ?>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link user-toggle dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-circle avatar-sm avatar-tone-<?= esc(avatar_tone($currentUser['nama'])) ?>">
                            <?= esc(user_initials($currentUser['nama'])) ?>
                        </span>
                        <span class="d-none d-xl-inline user-toggle-name"><?= esc($currentUser['nama']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow">
                        <li class="dropdown-user-head">
                            <span class="avatar-circle avatar-lg avatar-tone-<?= esc(avatar_tone($currentUser['nama'])) ?>">
                                <?= esc(user_initials($currentUser['nama'])) ?>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate"><?= esc($currentUser['nama']) ?></div>
                                <div class="small text-body-secondary text-truncate"><?= esc($currentUser['jabatan']) ?></div>
                                <div class="small text-body-secondary">NIP <?= esc($currentUser['nip']) ?></div>
                                <?php if ((int) $currentUser['is_admin'] === 1): ?>
                                    <span class="badge badge-soft-primary mt-2">Administrator</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= site_url('pengaturan') ?>">
                                <i class="bi bi-key me-2"></i>Ganti Password
                            </a>
                        </li>
                        <?php if (is_admin()): ?>
                            <li>
                                <a class="dropdown-item" href="<?= site_url('pengaturan/users') ?>">
                                    <i class="bi bi-people me-2"></i>Data Pengguna
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="<?= site_url('logout') ?>" method="post" class="m-0">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Log out
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
