<?php

use Config\Ganisph;

if (! function_exists('ganisph_config')) {
    function ganisph_config(): Ganisph
    {
        return config(Ganisph::class);
    }
}

if (! function_exists('auth_user')) {
    /**
     * Data pengguna yang sedang login (di-refresh oleh AuthFilter).
     */
    function auth_user(): ?array
    {
        $user = session('auth_user');

        return is_array($user) ? $user : null;
    }
}

if (! function_exists('auth_id')) {
    function auth_id(): ?int
    {
        $id = session('user_id');

        return $id !== null ? (int) $id : null;
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return (int) (auth_user()['is_admin'] ?? 0) === 1;
    }
}

if (! function_exists('is_impersonating')) {
    function is_impersonating(): bool
    {
        return session('impersonator_id') !== null;
    }
}

if (! function_exists('impersonator_user')) {
    function impersonator_user(): ?array
    {
        $user = session('impersonator');

        return is_array($user) ? $user : null;
    }
}

if (! function_exists('user_initials')) {
    function user_initials(?string $name, int $limit = 2): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            if (mb_strlen($initials) >= $limit) {
                break;
            }
        }

        return $initials !== '' ? $initials : mb_strtoupper(mb_substr($name, 0, 1));
    }
}

if (! function_exists('avatar_tone')) {
    function avatar_tone(?string $seed): string
    {
        $tones = ['blue', 'teal', 'violet', 'amber', 'rose', 'green'];
        $index = abs((int) crc32((string) $seed)) % count($tones);

        return $tones[$index];
    }
}

if (! function_exists('nama_bulan')) {
    function nama_bulan(int $month, bool $short = false): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $name = $months[$month] ?? '-';

        return $short ? mb_substr($name, 0, 3) : $name;
    }
}

if (! function_exists('tanggal_id')) {
    /**
     * Format tanggal dalam bahasa Indonesia tanpa bergantung pada locale server.
     */
    function tanggal_id(?string $date, bool $withTime = false): string
    {
        if ($date === null || trim($date) === '' || $date === '0000-00-00') {
            return '-';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return (string) $date;
        }

        $text = date('j', $timestamp) . ' ' . nama_bulan((int) date('n', $timestamp)) . ' ' . date('Y', $timestamp);

        if ($withTime) {
            $text .= ', ' . date('H:i', $timestamp);
        }

        return $text;
    }
}

if (! function_exists('tanggal_singkat')) {
    function tanggal_singkat(?string $date): string
    {
        if ($date === null || trim($date) === '') {
            return '-';
        }

        $timestamp = strtotime($date);

        return $timestamp === false ? (string) $date : date('d/m/Y', $timestamp);
    }
}

if (! function_exists('periode_penugasan')) {
    function periode_penugasan(?string $start, ?string $end): string
    {
        if (empty($start) && empty($end)) {
            return '-';
        }

        if (empty($end) || $start === $end) {
            return tanggal_id($start);
        }

        return tanggal_id($start) . ' s.d. ' . tanggal_id($end);
    }
}

if (! function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'selesai'  => '<span class="badge rounded-pill badge-soft-success"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>',
            'dibatalkan' => '<span class="badge rounded-pill badge-soft-danger"><i class="bi bi-x-circle-fill me-1"></i>Dibatalkan</span>',
            'draft'    => '<span class="badge rounded-pill badge-soft-secondary"><i class="bi bi-pencil-fill me-1"></i>Draft</span>',
            default    => '<span class="badge rounded-pill badge-soft-primary"><i class="bi bi-activity me-1"></i>Aktif</span>',
        };
    }
}

if (! function_exists('sidebar_active')) {
    /**
     * Menghasilkan class "active" jika URL saat ini cocok dengan pola.
     */
    function sidebar_active(string ...$patterns): string
    {
        foreach ($patterns as $pattern) {
            if (url_is($pattern)) {
                return 'active';
            }
        }

        return '';
    }
}

if (! function_exists('sidebar_open')) {
    function sidebar_open(string ...$patterns): string
    {
        return sidebar_active(...$patterns) !== '' ? 'menu-open' : '';
    }
}

if (! function_exists('field_error')) {
    function field_error(array $errors, string $field): string
    {
        return isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . esc($errors[$field]) . '</div>' : '';
    }
}

if (! function_exists('field_invalid')) {
    function field_invalid(array $errors, string $field): string
    {
        return isset($errors[$field]) ? ' is-invalid' : '';
    }
}

if (! function_exists('error_icon')) {
    function error_icon(string $type): string
    {
        return match ($type) {
            'success' => 'bi-check-circle-fill',
            'error'   => 'bi-exclamation-octagon-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            default   => 'bi-info-circle-fill',
        };
    }
}
