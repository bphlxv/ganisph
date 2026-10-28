<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= site_url('dashboard') ?>" class="brand-link">
            <img src="<?= base_url('assets/img/logo-mark.svg') ?>" alt="Logo GANISPH" class="brand-image">
            <span class="brand-text">
                <span class="brand-title">GANISPH</span>
                <span class="brand-subtitle">Penugasan &amp; Telaahan</span>
            </span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Navigasi utama">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">
                <li class="nav-header">UTAMA</li>
                <li class="nav-item">
                    <a href="<?= site_url('dashboard') ?>" class="nav-link <?= sidebar_active('dashboard') ?>">
                        <i class="nav-icon bi bi-grid-1x2-fill"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">GANISPH</li>
                <li class="nav-item">
                    <a href="<?= site_url('penugasan') ?>" class="nav-link <?= sidebar_active('penugasan', 'penugasan/*') ?>">
                        <i class="nav-icon bi bi-journal-check"></i>
                        <p>Penugasan GANISPH</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= site_url('daftar-penugasan') ?>" class="nav-link <?= sidebar_active('daftar-penugasan', 'daftar-penugasan/*') ?>">
                        <i class="nav-icon bi bi-table"></i>
                        <p>Daftar Penugasan</p>
                    </a>
                </li>
                <li class="nav-header">PENGATURAN</li>
                <li class="nav-item <?= sidebar_open('pengaturan', 'pengaturan/*') ?>">
                    <a href="#" class="nav-link <?= sidebar_active('pengaturan', 'pengaturan/*') ?>">
                        <i class="nav-icon bi bi-gear-fill"></i>
                        <p>
                            Pengaturan
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= site_url('pengaturan') ?>" class="nav-link <?= url_is('pengaturan') ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-key"></i>
                                <p>Ganti Password</p>
                            </a>
                        </li>
                        <?php if (is_admin()): ?>
                            <li class="nav-item">
                                <a href="<?= site_url('pengaturan/reset-password') ?>" class="nav-link <?= sidebar_active('pengaturan/reset-password') ?>">
                                    <i class="nav-icon bi bi-arrow-counterclockwise"></i>
                                    <p>Reset Password</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?= site_url('pengaturan/users') ?>" class="nav-link <?= sidebar_active('pengaturan/users', 'pengaturan/users/*') ?>">
                                    <i class="nav-icon bi bi-people"></i>
                                    <p>Data Pengguna</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <li class="nav-header">SESI</li>
                <li class="nav-item">
                    <form action="<?= site_url('logout') ?>" method="post" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="nav-link nav-link-button">
                            <i class="nav-icon bi bi-box-arrow-right"></i>
                            <p>Log out</p>
                        </button>
                    </form>
                </li>
            </ul>
        </nav>

        <div class="sidebar-foot">
            <div class="sidebar-foot-inner">
                <i class="bi bi-shield-lock"></i>
                <span>Akses aman &middot; <?= esc(ganisph_config()->appName) ?></span>
            </div>
        </div>
    </div>
</aside>
