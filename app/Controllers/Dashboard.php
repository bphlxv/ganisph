<?php

namespace App\Controllers;

use App\Models\PenugasanModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $penugasanModel = model(PenugasanModel::class);
        $userModel      = model(UserModel::class);

        $total      = $penugasanModel->countAllResults();
        $aktif      = $penugasanModel->countByStatus('Aktif');
        $selesai    = $penugasanModel->countByStatus('Selesai');
        $dibatalkan = $penugasanModel->countByStatus('Dibatalkan');

        $upcoming = $penugasanModel
            ->where('status', 'Aktif')
            ->where('tanggal_selesai >=', date('Y-m-d'))
            ->orderBy('tanggal_mulai', 'ASC')
            ->findAll(4);

        return $this->render('dashboard/index', [
            'stats' => [
                'total'      => $total,
                'aktif'      => $aktif,
                'selesai'    => $selesai,
                'dibatalkan' => $dibatalkan,
                'pengguna'   => is_admin() ? $userModel->countAllResults() : null,
            ],
            'recent'         => $penugasanModel->latest(5),
            'upcoming'       => $upcoming,
            'monthlyCounts'  => $penugasanModel->monthlyCounts(6),
            'useChart'       => true,
            'pageSubtitle'   => 'Ringkasan penugasan, agenda, dan aktivitas GANISPH.',
        ], 'Dashboard');
    }
}
