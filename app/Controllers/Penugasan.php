<?php

namespace App\Controllers;

use App\Libraries\PenugasanExcel;
use App\Libraries\TelaahanDocx;
use App\Models\PenugasanModel;
use CodeIgniter\HTTP\RedirectResponse;

class Penugasan extends BaseController
{
    public function index()
    {
        return $this->renderIndex();
    }

    public function daftar()
    {
        $model   = model(PenugasanModel::class);
        $keyword = trim((string) $this->request->getGet('q'));
        $status  = trim((string) $this->request->getGet('status'));
        $ket     = trim((string) $this->request->getGet('ket'));

        $perPageInput = $this->request->getGet('per_page');
        $perPage      = is_numeric($perPageInput) ? (int) $perPageInput : 10;
        $perPage      = max(10, min(200, $perPage));

        $allRows    = $model->search($keyword, $status, $ket);
        $total      = count($allRows);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $pageInput = $this->request->getGet('page');
        $page      = is_numeric($pageInput) ? (int) $pageInput : 1;
        $page      = max(1, min($totalPages, $page));

        $offset = ($page - 1) * $perPage;
        $rows   = array_slice($allRows, $offset, $perPage);

        return $this->render('penugasan/daftar', [
            'rows'          => $rows,
            'keyword'       => $keyword,
            'status'        => $status,
            'ket'           => $ket,
            'statusOptions' => PenugasanModel::STATUS_GANISPH,
            'ketOptions'    => PenugasanModel::KET_OPTIONS,
            'excelInfo'     => $this->excelInfo(),
            'total'         => $total,
            'page'          => $page,
            'perPage'       => $perPage,
            'totalPages'    => $totalPages,
            'from'          => $total === 0 ? 0 : $offset + 1,
            'to'            => min($offset + $perPage, $total),
        ], 'Daftar Penugasan');
    }

    public function edit(int $id)
    {
        $penugasan = model(PenugasanModel::class)->find($id);
        if ($penugasan === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Data penugasan tidak ditemukan.');
        }

        return $this->renderIndex($penugasan);
    }

    public function save(): RedirectResponse
    {
        $result = $this->validateInput();
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $model = model(PenugasanModel::class);
        $data  = $result;
        $data['created_by'] = auth_id();

        $id = $model->insert($data, true);
        if ($id === false) {
            return redirect()->back()->withInput()->with('error', 'Data penugasan gagal disimpan.');
        }

        return $this->afterWrite('Data penugasan berhasil disimpan ke database dan Excel.');
    }

    public function update(int $id): RedirectResponse
    {
        $model     = model(PenugasanModel::class);
        $penugasan = $model->find($id);
        if ($penugasan === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Data penugasan tidak ditemukan.');
        }

        $result = $this->validateInput();
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $data = $result;
        $data['created_by'] = $penugasan['created_by'] ?? auth_id();

        if ($model->update($id, $data) === false) {
            return redirect()->back()->withInput()->with('error', 'Perubahan data penugasan gagal disimpan.');
        }

        return $this->afterWrite('Data penugasan berhasil diperbarui pada database dan Excel.');
    }

    public function delete(int $id): RedirectResponse
    {
        $model = model(PenugasanModel::class);
        $row   = $model->find($id);

        if ($row === null) {
            return redirect()->to(site_url('daftar-penugasan'))->with('error', 'Data penugasan tidak ditemukan.');
        }

        $model->delete($id);

        return $this->afterWrite('Data penugasan berhasil dihapus dari database dan Excel.');
    }

    public function downloadExcel()
    {
        $excel = new PenugasanExcel();
        $model = model(PenugasanModel::class);

        try {
            $excel->export($model->orderBy('id', 'ASC')->findAll());
        } catch (\Throwable $e) {
            return redirect()->to(site_url('daftar-penugasan'))->with(
                'error',
                'File Excel gagal dibuat: ' . $e->getMessage()
            );
        }

        $fileName = 'Penugasan_GANISPH_' . date('Ymd_His') . '.xlsx';

        return $this->response->download($excel->path(), null)->setFileName($fileName);
    }

    public function sync(): RedirectResponse
    {
        $excel = new PenugasanExcel();

        try {
            $summary = $excel->sync((int) auth_id());
        } catch (\Throwable $e) {
            return redirect()->to(site_url('daftar-penugasan'))->with(
                'error',
                'Sinkronisasi gagal: ' . $e->getMessage()
            );
        }

        $notes = $summary['notes'];
        if ($summary['imported'] === 0 && $summary['exported'] === 0 && $summary['conflicts'] === 0) {
            $message = 'Sinkronisasi selesai. Database dan Excel sudah identik.';
            $type    = 'info';
        } else {
            $message = sprintf(
                'Sinkronisasi selesai: %d data diimpor dari Excel, %d data dari database diperbarui ke Excel, %d baris dilewati, %d konflik diselesaikan.',
                $summary['imported'],
                $summary['exported'],
                $summary['skipped'],
                $summary['conflicts']
            );
            $type = $summary['skipped'] > 0 ? 'warning' : 'success';
        }

        return redirect()->to(site_url('daftar-penugasan'))
            ->with($type, $message)
            ->with('sync_notes', array_slice($notes, 0, 12));
    }

    public function downloadTelaahan(int $id)
    {
        $penugasan = model(PenugasanModel::class)->find($id);
        if ($penugasan === null) {
            return redirect()->to(site_url('daftar-penugasan'))->with('error', 'Data penugasan tidak ditemukan.');
        }

        $docx = new TelaahanDocx();

        try {
            $this->cleanupOldTelaahan();
            $path = $docx->render($penugasan);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('daftar-penugasan'))->with(
                'error',
                'Surat telaahan gagal dibuat: ' . $e->getMessage()
            );
        }

        return $this->response->download($path, null)->setFileName(basename($path));
    }

    private function renderIndex(?array $editData = null): string
    {
        return $this->render('penugasan/index', [
            'editData'      => $editData,
            'statusOptions' => PenugasanModel::STATUS_GANISPH,
            'ketOptions'    => PenugasanModel::KET_OPTIONS,
            'excelInfo'     => $this->excelInfo(),
        ], $editData === null ? 'Penugasan GANISPH' : 'Edit Penugasan GANISPH');
    }

    /**
     * @return array{exists: bool, modified: int|null, size: int|null, error: string|null}
     */
    private function excelInfo(): array
    {
        $excel = new PenugasanExcel();

        $excelInfo = [
            'exists'   => $excel->exists(),
            'modified' => null,
            'size'     => null,
            'error'    => null,
        ];

        try {
            $excel->ensureFile();
            $excelInfo['exists']   = true;
            $excelInfo['modified'] = filemtime($excel->path()) ?: null;
            $excelInfo['size']     = filesize($excel->path()) ?: null;
        } catch (\Throwable $e) {
            $excelInfo['error'] = $e->getMessage();
        }

        return $excelInfo;
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function validateInput()
    {
        $rules = [
            'perusahaan'          => 'required|max_length[255]',
            'entt'                => 'required|max_length[100]',
            'tanggal'             => 'required|valid_date[Y-m-d]',
            'nama'                => 'required|max_length[150]',
            'no_reg'              => 'required|max_length[100]',
            'masa_berlaku'        => 'required|max_length[150]',
            'kualifikasi'         => 'required|max_length[255]',
            'kualifikasi_singkat' => 'required|max_length[50]',
            'kabkota'             => 'required|max_length[150]',
            'provinsi'            => 'required|max_length[150]',
            'jangka_waktu'        => 'required|max_length[150]',
            'jenis_izin'          => 'permit_empty|max_length[255]',
            'jenis_izin_singkat'  => 'permit_empty|max_length[50]',
            'pemberi_izin'        => 'permit_empty|max_length[255]',
            'no_izin'             => 'permit_empty|max_length[255]',
            'tanggal_izin'        => 'permit_empty|valid_date[Y-m-d]',
            'kapasitas_izin'      => 'permit_empty|max_length[255]',
            'status_ganisph'      => 'required|in_list[ASN,NON-ASN]',
            'ket'                 => 'required|in_list[B1,B2,B3]',
            'nilai_t'             => 'permit_empty|max_length[100]',
            'catatan_u'           => 'permit_empty|max_length[5000]',
        ];

        $messages = [
            'perusahaan'           => ['required' => 'Nama perusahaan wajib diisi.', 'max_length' => 'Nama perusahaan maksimal 255 karakter.'],
            'entt'                 => ['required' => 'Nomor E-NTT wajib diisi.', 'max_length' => 'Nomor E-NTT maksimal 100 karakter.'],
            'tanggal'              => ['required' => 'Tanggal formulir wajib diisi.', 'valid_date' => 'Format tanggal formulir tidak valid.'],
            'nama'                 => ['required' => 'Nama GANISPH wajib diisi.', 'max_length' => 'Nama GANISPH maksimal 150 karakter.'],
            'no_reg'               => ['required' => 'Nomor register wajib diisi.', 'max_length' => 'Nomor register maksimal 100 karakter.'],
            'masa_berlaku'         => ['required' => 'Masa berlaku wajib diisi.', 'max_length' => 'Masa berlaku maksimal 150 karakter.'],
            'kualifikasi'          => ['required' => 'Kualifikasi wajib diisi.', 'max_length' => 'Kualifikasi maksimal 255 karakter.'],
            'kualifikasi_singkat'  => ['required' => 'Singkatan kualifikasi wajib diisi.', 'max_length' => 'Singkatan kualifikasi maksimal 50 karakter.'],
            'kabkota'              => ['required' => 'Kabupaten/kota wajib diisi.', 'max_length' => 'Kabupaten/kota maksimal 150 karakter.'],
            'provinsi'             => ['required' => 'Provinsi wajib diisi.', 'max_length' => 'Provinsi maksimal 150 karakter.'],
            'jangka_waktu'         => ['required' => 'Jangka waktu penugasan wajib diisi.', 'max_length' => 'Jangka waktu maksimal 150 karakter.'],
            'jenis_izin'           => ['max_length' => 'Jenis izin maksimal 255 karakter.'],
            'jenis_izin_singkat'   => ['max_length' => 'Singkatan jenis izin maksimal 50 karakter.'],
            'pemberi_izin'         => ['max_length' => 'Pemberi izin maksimal 255 karakter.'],
            'no_izin'              => ['max_length' => 'Nomor izin maksimal 255 karakter.'],
            'tanggal_izin'         => ['valid_date' => 'Format tanggal izin tidak valid.'],
            'kapasitas_izin'       => ['max_length' => 'Kapasitas izin maksimal 255 karakter.'],
            'status_ganisph'       => ['required' => 'Status GANISPH wajib dipilih.', 'in_list' => 'Status GANISPH tidak valid.'],
            'ket'                  => ['required' => 'KET. wajib dipilih.', 'in_list' => 'KET. tidak valid.'],
            'nilai_t'              => ['max_length' => 'Nilai T maksimal 100 karakter.'],
            'catatan_u'            => ['max_length' => 'Catatan U maksimal 5.000 karakter.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $optional = static function (string $value): ?string {
            $value = trim($value);

            return $value === '' ? null : $value;
        };

        return [
            'perusahaan'          => trim((string) $this->request->getPost('perusahaan')),
            'entt'                => trim((string) $this->request->getPost('entt')),
            'tanggal'             => (string) $this->request->getPost('tanggal'),
            'nama'                => trim((string) $this->request->getPost('nama')),
            'no_reg'              => trim((string) $this->request->getPost('no_reg')),
            'masa_berlaku'        => trim((string) $this->request->getPost('masa_berlaku')),
            'kualifikasi'         => trim((string) $this->request->getPost('kualifikasi')),
            'kualifikasi_singkat' => trim((string) $this->request->getPost('kualifikasi_singkat')),
            'kabkota'             => trim((string) $this->request->getPost('kabkota')),
            'provinsi'            => trim((string) $this->request->getPost('provinsi')),
            'jangka_waktu'        => trim((string) $this->request->getPost('jangka_waktu')),
            'jenis_izin'          => $optional((string) $this->request->getPost('jenis_izin')),
            'jenis_izin_singkat'  => $optional((string) $this->request->getPost('jenis_izin_singkat')),
            'pemberi_izin'        => $optional((string) $this->request->getPost('pemberi_izin')),
            'no_izin'             => $optional((string) $this->request->getPost('no_izin')),
            'tanggal_izin'        => $optional((string) $this->request->getPost('tanggal_izin')),
            'kapasitas_izin'      => $optional((string) $this->request->getPost('kapasitas_izin')),
            'status_ganisph'      => (string) $this->request->getPost('status_ganisph'),
            'ket'                 => (string) $this->request->getPost('ket'),
            'nilai_t'             => $optional((string) $this->request->getPost('nilai_t')),
            'catatan_u'           => $optional((string) $this->request->getPost('catatan_u')),
        ];
    }

    private function afterWrite(string $successMessage, string $redirectTo = 'daftar-penugasan'): RedirectResponse
    {
        $excel = new PenugasanExcel();

        try {
            $excel->refreshFromDatabase();
        } catch (\Throwable $e) {
            return redirect()->to(site_url($redirectTo))
                ->with('warning', $successMessage . ' Namun file Excel gagal diperbarui: ' . $e->getMessage())
                ->with('info', 'Gunakan tombol "Sinkron Excel" setelah masalah penyimpanan file diperbaiki.');
        }

        return redirect()->to(site_url($redirectTo))->with('success', $successMessage);
    }

    private function cleanupOldTelaahan(): void
    {
        $directory = WRITEPATH . 'telaahan';
        if (! is_dir($directory)) {
            return;
        }

        $threshold = time() - 86400;
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.docx') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }
}
