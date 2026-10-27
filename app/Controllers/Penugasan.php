<?php

namespace App\Controllers;

use App\Libraries\PenugasanExcel;
use App\Libraries\TelaahanDocx;
use App\Models\PenugasanModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Penugasan extends BaseController
{
    public function index()
    {
        return $this->renderIndex();
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
        $model      = model(PenugasanModel::class);
        $penugasan  = $model->find($id);
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
            return redirect()->to(site_url('penugasan'))->with('error', 'Data penugasan tidak ditemukan.');
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
            return redirect()->to(site_url('penugasan'))->with(
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
            return redirect()->to(site_url('penugasan'))->with(
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

        return redirect()->to(site_url('penugasan'))
            ->with($type, $message)
            ->with('sync_notes', array_slice($notes, 0, 12));
    }

    public function telaahan()
    {
        $model      = model(PenugasanModel::class);
        $userModel  = model(UserModel::class);
        $keyword    = trim((string) $this->request->getGet('q'));
        $status     = trim((string) $this->request->getGet('status'));

        $official = $userModel->where('is_admin', 1)->orderBy('id', 'ASC')->first();
        $rows     = $model->search($keyword, $status);

        return $this->render('penugasan/telaahan', [
            'rows'     => $rows,
            'keyword'  => $keyword,
            'status'   => $status,
            'official' => $official,
            'total'    => count($rows),
        ], 'Telaahan Penugasan');
    }

    public function downloadTelaahan(int $id)
    {
        $penugasan = model(PenugasanModel::class)->find($id);
        if ($penugasan === null) {
            return redirect()->to(site_url('telaahan'))->with('error', 'Data penugasan tidak ditemukan.');
        }

        $official = model(UserModel::class)->where('is_admin', 1)->orderBy('id', 'ASC')->first();
        $docx     = new TelaahanDocx();

        try {
            $this->cleanupOldTelaahan();
            $path = $docx->render($penugasan, $official);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('telaahan'))->with(
                'error',
                'Surat telaahan gagal dibuat: ' . $e->getMessage()
            );
        }

        return $this->response->download($path, null)->setFileName(basename($path));
    }

    private function renderIndex(?array $editData = null): string
    {
        $model     = model(PenugasanModel::class);
        $userModel = model(UserModel::class);
        $keyword   = trim((string) $this->request->getGet('q'));
        $status    = trim((string) $this->request->getGet('status'));
        $excel     = new PenugasanExcel();
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

        $selectedUserId = old('user_id', $editData['user_id'] ?? auth_id());

        return $this->render('penugasan/index', [
            'rows'           => $model->search($keyword, $status),
            'users'          => $userModel->orderBy('nama', 'ASC')->findAll(),
            'editData'       => $editData,
            'keyword'        => $keyword,
            'status'         => $status,
            'excelInfo'      => $excelInfo,
            'selectedUserId' => (int) $selectedUserId,
        ], $editData === null ? 'Penugasan GANISPH' : 'Edit Penugasan GANISPH');
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function validateInput()
    {
        $rules = [
            'user_id'         => 'required|is_natural_no_zero',
            'nomor_surat'     => 'required|max_length[150]',
            'tanggal_surat'   => 'required|valid_date[Y-m-d]',
            'nama_kegiatan'   => 'required|max_length[255]',
            'peran'           => 'required|max_length[120]',
            'penyelenggara'   => 'required|max_length[200]',
            'lokasi'          => 'required|max_length[200]',
            'tanggal_mulai'   => 'required|valid_date[Y-m-d]',
            'tanggal_selesai' => 'required|valid_date[Y-m-d]',
            'status'          => 'required|in_list[Aktif,Selesai,Dibatalkan]',
            'keterangan'      => 'permit_empty|max_length[5000]',
        ];

        $messages = [
            'user_id' => [
                'required'            => 'Pegawai yang ditugaskan wajib dipilih.',
                'is_natural_no_zero'  => 'Pegawai yang dipilih tidak valid.',
            ],
            'nomor_surat' => [
                'required'   => 'Nomor surat tugas wajib diisi.',
                'max_length' => 'Nomor surat tugas maksimal 150 karakter.',
            ],
            'tanggal_surat' => [
                'required'   => 'Tanggal surat tugas wajib diisi.',
                'valid_date' => 'Format tanggal surat tugas tidak valid.',
            ],
            'nama_kegiatan' => [
                'required'   => 'Nama kegiatan wajib diisi.',
                'max_length' => 'Nama kegiatan maksimal 255 karakter.',
            ],
            'peran' => [
                'required'   => 'Peran dalam penugasan wajib diisi.',
                'max_length' => 'Peran maksimal 120 karakter.',
            ],
            'penyelenggara' => [
                'required'   => 'Penyelenggara wajib diisi.',
                'max_length' => 'Penyelenggara maksimal 200 karakter.',
            ],
            'lokasi' => [
                'required'   => 'Lokasi wajib diisi.',
                'max_length' => 'Lokasi maksimal 200 karakter.',
            ],
            'tanggal_mulai' => [
                'required'   => 'Tanggal mulai wajib diisi.',
                'valid_date' => 'Format tanggal mulai tidak valid.',
            ],
            'tanggal_selesai' => [
                'required'   => 'Tanggal selesai wajib diisi.',
                'valid_date' => 'Format tanggal selesai tidak valid.',
            ],
            'status' => [
                'required' => 'Status penugasan wajib dipilih.',
                'in_list'  => 'Status penugasan tidak valid.',
            ],
            'keterangan' => [
                'max_length' => 'Keterangan maksimal 5.000 karakter.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = model(UserModel::class)->find((int) $this->request->getPost('user_id'));
        if ($user === null) {
            return redirect()->back()->withInput()->with('errors', [
                'user_id' => 'Pegawai yang dipilih tidak ditemukan pada Data Pengguna.',
            ]);
        }

        $tanggalMulai   = (string) $this->request->getPost('tanggal_mulai');
        $tanggalSelesai = (string) $this->request->getPost('tanggal_selesai');
        if ($tanggalSelesai < $tanggalMulai) {
            return redirect()->back()->withInput()->with('errors', [
                'tanggal_selesai' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            ]);
        }

        return [
            'user_id'         => (int) $user['id'],
            'nama_pegawai'    => $user['nama'],
            'nip'             => $user['nip'],
            'jabatan'         => $user['jabatan'],
            'nomor_surat'     => trim((string) $this->request->getPost('nomor_surat')),
            'tanggal_surat'   => (string) $this->request->getPost('tanggal_surat'),
            'nama_kegiatan'   => trim((string) $this->request->getPost('nama_kegiatan')),
            'peran'           => trim((string) $this->request->getPost('peran')),
            'penyelenggara'   => trim((string) $this->request->getPost('penyelenggara')),
            'lokasi'          => trim((string) $this->request->getPost('lokasi')),
            'tanggal_mulai'   => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'status'          => (string) $this->request->getPost('status'),
            'keterangan'      => trim((string) $this->request->getPost('keterangan')),
        ];
    }

    private function afterWrite(string $successMessage, string $redirectTo = 'penugasan'): RedirectResponse
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
