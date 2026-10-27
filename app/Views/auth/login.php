<?php
$errors = session('errors') ?? [];
$loginError = session()->getFlashdata('error');
?>
<!doctype html>
<html lang="id" data-bs-theme="light" data-lte-color-mode="off">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= esc(ganisph_config()->appDescription) ?>">
    <meta name="color-scheme" content="light dark">
    <title>Login &middot; <?= esc(ganisph_config()->appName) ?></title>

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
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/adminlte/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= esc(ganisph_config()->version) ?>">
</head>
<body class="auth-page">
<button type="button" class="auth-theme-toggle" data-theme-toggle aria-label="Ganti mode terang atau gelap" title="Ganti mode tampilan">
    <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
    <i class="bi bi-sun-fill theme-icon-light"></i>
</button>

<main class="auth-shell">
    <div class="auth-card">
        <section class="auth-brand-panel">
            <div class="auth-brand-top">
                <div class="auth-logo">
                    <img src="<?= base_url('assets/img/logo-mark.svg') ?>" alt="Logo GANISPH" width="58" height="58">
                    <div>
                        <div class="auth-brand-name">GANISPH</div>
                        <div class="auth-brand-sub">Sistem Informasi Penugasan &amp; Telaahan</div>
                    </div>
                </div>

                <h1 class="auth-headline">Kelola penugasan lebih tertata, telaahan lebih cepat.</h1>
                <p class="auth-lead">
                    Satu tempat untuk mencatat penugasan, menyinkronkan data Excel, dan menerbitkan surat telaahan
                    dalam format Word secara otomatis.
                </p>

                <ul class="auth-features">
                    <li>
                        <span class="auth-feature-icon"><i class="bi bi-file-earmark-spreadsheet"></i></span>
                        <span>Sinkronisasi database dan Excel dua arah</span>
                    </li>
                    <li>
                        <span class="auth-feature-icon"><i class="bi bi-file-earmark-word"></i></span>
                        <span>Telaahan DOCX terisi otomatis dari data penugasan</span>
                    </li>
                    <li>
                        <span class="auth-feature-icon"><i class="bi bi-shield-check"></i></span>
                        <span>Akses berbasis peran admin dan user</span>
                    </li>
                </ul>
            </div>

            <div class="auth-brand-bottom">
                <span class="auth-brand-badge"><i class="bi bi-database-check"></i> SQLite &middot; Backup mandiri</span>
                <span class="auth-brand-badge"><i class="bi bi-moon-stars"></i> Mode gelap</span>
            </div>
        </section>

        <section class="auth-form-panel">
            <div class="auth-form-inner">
                <div class="auth-welcome-icon">
                    <i class="bi bi-person-lock"></i>
                </div>
                <h2>Selamat Datang</h2>
                <p class="auth-form-lead">Masuk menggunakan NIP dan password akun Anda.</p>

                <?php if ($loginError !== null): ?>
                    <div class="alert alert-danger app-alert" role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-octagon-fill alert-icon"></i>
                            <div><?= esc($loginError) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('success') !== null): ?>
                    <div class="alert alert-success app-alert" role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill alert-icon"></i>
                            <div><?= esc(session()->getFlashdata('success')) ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= site_url('login') ?>" method="post" class="auth-form" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nip" class="form-label">NIP</label>
                        <div class="auth-input-group">
                            <span class="auth-input-icon"><i class="bi bi-person-vcard"></i></span>
                            <input
                                type="text"
                                class="form-control<?= field_invalid($errors, 'nip') ?>"
                                id="nip"
                                name="nip"
                                value="<?= esc(old('nip')) ?>"
                                placeholder="Masukkan NIP"
                                inputmode="numeric"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </div>
                        <?php if (isset($errors['nip'])): ?>
                            <div class="invalid-feedback d-block"><?= esc($errors['nip']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="auth-input-group">
                            <span class="auth-input-icon"><i class="bi bi-lock"></i></span>
                            <input
                                type="password"
                                class="form-control<?= field_invalid($errors, 'password') ?>"
                                id="password"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                            <button type="button" class="auth-input-action" data-password-toggle="#password" aria-label="Tampilkan atau sembunyikan password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback d-block"><?= esc($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Dashboard
                    </button>
                </form>

                <div class="auth-help">
                    <i class="bi bi-info-circle"></i>
                    <span>Lupa password? Hubungi administrator untuk melakukan reset password.</span>
                </div>

                <div class="auth-form-footer">
                    <span>&copy; <?= date('Y') ?> GANISPH</span>
                    <span>Versi <?= esc(ganisph_config()->version) ?></span>
                </div>
            </div>
        </section>
    </div>

    <p class="auth-copyright">
        Sistem Informasi Penugasan dan Telaahan GANISPH
        <span class="d-none d-sm-inline">&middot;</span>
        <span class="d-block d-sm-inline">Akses hanya untuk pengguna yang berwenang.</span>
    </p>
</main>

<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>?v=<?= esc(ganisph_config()->version) ?>"></script>
</body>
</html>
