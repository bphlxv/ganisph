<!doctype html>
<html lang="id" data-bs-theme="light" data-lte-color-mode="off">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(ganisph_config()->appDescription) ?>">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0b2f5b" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#071321" media="(prefers-color-scheme: dark)">
    <title><?= esc($pageTitle ?? 'Dashboard') ?> &middot; <?= esc(ganisph_config()->appName) ?></title>

    <script>
        (() => {
            try {
                const stored = localStorage.getItem('ganisph-theme');
                const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                const theme = stored === 'dark' || stored === 'light' ? stored : preferred;
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.documentElement.style.colorScheme = theme;
            } catch (error) {
                document.documentElement.setAttribute('data-bs-theme', 'light');
            }
        })();
    </script>

    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/adminlte/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= esc(ganisph_config()->version) ?>">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <?= $this->include('partials/navbar') ?>
    <?= $this->include('partials/sidebar') ?>

    <main class="app-main">
        <?= $this->include('partials/impersonation') ?>

        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center g-2">
                    <div class="col-sm-7">
                        <h1 class="page-heading mb-1"><?= esc($pageTitle ?? 'Dashboard') ?></h1>
                        <p class="page-subheading mb-0"><?= esc($pageSubtitle ?? ganisph_config()->appDescription) ?></p>
                    </div>
                    <div class="col-sm-5">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 justify-content-sm-end">
                                <li class="breadcrumb-item"><a href="<?= site_url('dashboard') ?>">Dashboard</a></li>
                                <?php if (($pageTitle ?? '') !== 'Dashboard'): ?>
                                    <?php if (! empty($breadcrumbParent)): ?>
                                        <li class="breadcrumb-item"><a href="<?= esc($breadcrumbParentUrl ?? '#') ?>"><?= esc($breadcrumbParent) ?></a></li>
                                    <?php endif; ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?= esc($pageTitle ?? '') ?></li>
                                <?php endif; ?>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?= $this->include('partials/flash') ?>
                <?= $content ?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">Versi <?= esc(ganisph_config()->version) ?></div>
        <strong>GANISPH</strong> &copy; <?= date('Y') ?> &middot; Sistem Informasi Penugasan dan Telaahan
    </footer>
</div>

<?= $this->include('partials/confirm_modal') ?>

<script src="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/adminlte/adminlte.min.js') ?>"></script>
<?php if (! empty($useChart)): ?>
    <script src="<?= base_url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/app.js') ?>?v=<?= esc(ganisph_config()->version) ?>"></script>
</body>
</html>
