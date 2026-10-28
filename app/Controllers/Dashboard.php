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
        $asn        = $penugasanModel->countByStatusGanisph('ASN');
        $nonAsn     = $penugasanModel->countByStatusGanisph('NON-ASN');
        $perusahaan = $penugasanModel->countPerusahaan();

        $recent = $penugasanModel->latest(5);

        return $this->render('dashboard/index', [
            'stats' => [
                'total'      => $total,
                'asn'        => $asn,
                'non_asn'    => $nonAsn,
                'perusahaan' => $perusahaan,
                'pengguna'   => is_admin() ? $userModel->countAllResults() : 0,
            ],
            'recent'           => $recent,
            'monthlyCounts'   => $penugasanModel->monthlyCounts(12),
            'kualifikasi'      => $penugasanModel->countByKualifikasi(6),
            'ketCounts'        => $penugasanModel->countByKet(),
        ], 'Dashboard');
    }
}
