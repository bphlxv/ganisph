<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class Pengaturan extends BaseController
{
    public function index(): string
    {
        return $this->render('pengaturan/password', [], 'Ganti Password');
    }

    public function changePassword(): RedirectResponse
    {
        $config = ganisph_config();

        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[' . $config->minPasswordLength . ']|max_length[72]',
            'confirm_password' => 'required|matches[new_password]',
        ];

        $messages = [
            'current_password' => ['required' => 'Password saat ini wajib diisi.'],
            'new_password' => [
                'required'   => 'Password baru wajib diisi.',
                'min_length' => 'Password baru minimal ' . $config->minPasswordLength . ' karakter.',
                'max_length' => 'Password baru maksimal 72 karakter.',
            ],
            'confirm_password' => [
                'required' => 'Konfirmasi password baru wajib diisi.',
                'matches'  => 'Konfirmasi password tidak sama dengan password baru.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $userModel = model(UserModel::class);
        $user      = $userModel->find((int) auth_id());

        if ($user === null) {
            return redirect()->to(site_url('login'))->with('error', 'Sesi tidak valid.');
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');

        if (! password_verify($current, $user['password_hash'])) {
            return redirect()->back()->with('errors', ['current_password' => 'Password saat ini tidak sesuai.']);
        }

        if (password_verify($new, $user['password_hash'])) {
            return redirect()->back()->with('errors', ['new_password' => 'Password baru harus berbeda dari password saat ini.']);
        }

        if (! $userModel->updatePassword((int) $user['id'], $new)) {
            return redirect()->back()->with('error', 'Password gagal diperbarui.');
        }

        return redirect()->to(site_url('pengaturan'))->with('success', 'Password berhasil diperbarui. Gunakan password baru pada login berikutnya.');
    }

    public function resetPassword(): string
    {
        $users = model(UserModel::class)
            ->where('id !=', auth_id())
            ->orderBy('nama', 'ASC')
            ->findAll();

        return $this->render('pengaturan/reset_password', [
            'users'          => $users,
            'defaultPass'    => ganisph_config()->defaultPassword,
        ], 'Reset Password');
    }

    public function doResetPassword(int $id): RedirectResponse
    {
        $userModel = model(UserModel::class);
        $user      = $userModel->find($id);

        if ($user === null) {
            return redirect()->to(site_url('pengaturan/reset-password'))->with('error', 'Pengguna tidak ditemukan.');
        }

        if ($id === (int) auth_id()) {
            return redirect()->to(site_url('pengaturan/reset-password'))->with(
                'error',
                'Password akun yang sedang digunakan tidak dapat direset dari menu ini. Gunakan menu Ganti Password.'
            );
        }

        if (! $userModel->updatePassword($id, ganisph_config()->defaultPassword)) {
            return redirect()->to(site_url('pengaturan/reset-password'))->with('error', 'Password pengguna gagal direset.');
        }

        return redirect()->to(site_url('pengaturan/reset-password'))->with(
            'success',
            'Password ' . $user['nama'] . ' berhasil direset menjadi "' . ganisph_config()->defaultPassword . '".'
        );
    }

    public function users(): string
    {
        $userModel  = model(UserModel::class);
        $keyword    = trim((string) $this->request->getGet('q'));
        $adminCount = (new UserModel())->where('is_admin', 1)->countAllResults();
        $userCount  = (new UserModel())->where('is_admin', 0)->countAllResults();

        return $this->render('pengaturan/users/index', [
            'users'         => $userModel->search($keyword),
            'keyword'       => $keyword,
            'adminCount'    => $adminCount,
            'userCount'     => $userCount,
            'defaultPass'   => ganisph_config()->defaultPassword,
        ], 'Data Pengguna');
    }

    public function createUser(): string
    {
        return $this->render('pengaturan/users/form', [
            'user'   => null,
            'isEdit' => false,
        ], 'Tambah Pengguna');
    }

    public function storeUser(): RedirectResponse
    {
        $input = [
            'nama'     => trim((string) $this->request->getPost('nama')),
            'nip'      => trim((string) $this->request->getPost('nip')),
            'jabatan'  => trim((string) $this->request->getPost('jabatan')),
            'is_admin' => $this->request->getPost('is_admin') ? 1 : 0,
        ];

        $errors = $this->validateUserInput($input, null);
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $userModel = model(UserModel::class);
        $id        = $userModel->insert([
            'nama'          => $input['nama'],
            'nip'           => $input['nip'],
            'jabatan'       => $input['jabatan'],
            'is_admin'      => $input['is_admin'],
            'password_hash' => password_hash(ganisph_config()->defaultPassword, PASSWORD_DEFAULT),
        ], true);

        if ($id === false) {
            return redirect()->back()->withInput()->with('error', 'Pengguna gagal ditambahkan.');
        }

        return redirect()->to(site_url('pengaturan/users'))->with(
            'success',
            'Pengguna "' . $input['nama'] . '" berhasil ditambahkan dengan password awal ' . ganisph_config()->defaultPassword . '.'
        );
    }

    public function editUser(int $id): string
    {
        $user = model(UserModel::class)->find($id);
        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Pengguna tidak ditemukan.');
        }

        return $this->render('pengaturan/users/form', [
            'user'   => $user,
            'isEdit' => true,
        ], 'Edit Pengguna');
    }

    public function updateUser(int $id): RedirectResponse
    {
        $userModel = model(UserModel::class);
        $user      = $userModel->find($id);

        if ($user === null) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        $input = [
            'nama'     => trim((string) $this->request->getPost('nama')),
            'nip'      => trim((string) $this->request->getPost('nip')),
            'jabatan'  => trim((string) $this->request->getPost('jabatan')),
            'is_admin' => $this->request->getPost('is_admin') ? 1 : 0,
        ];

        $errors = $this->validateUserInput($input, $id);
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $wasAdmin = (int) $user['is_admin'] === 1;
        $isAdmin  = $input['is_admin'] === 1;

        if ($wasAdmin && ! $isAdmin && $userModel->adminCount($id) === 0) {
            return redirect()->back()->withInput()->with('errors', [
                'is_admin' => 'Minimal satu administrator harus tetap tersedia. Jadikan pengguna lain sebagai admin terlebih dahulu.',
            ]);
        }

        if (! $userModel->update($id, $input)) {
            return redirect()->back()->withInput()->with('error', 'Data pengguna gagal diperbarui.');
        }

        $identityChanged = $input['nama'] !== $user['nama']
            || $input['nip'] !== $user['nip']
            || $input['jabatan'] !== $user['jabatan'];

        if ($identityChanged) {
            $db = db_connect();
            $db->table('penugasan')
                ->where('user_id', $id)
                ->update([
                    'nama_pegawai' => $input['nama'],
                    'nip'          => $input['nip'],
                    'jabatan'      => $input['jabatan'],
                ]);

            try {
                (new \App\Libraries\PenugasanExcel())->refreshFromDatabase();
            } catch (\Throwable $e) {
                session()->setFlashdata('warning', 'Data pengguna tersimpan, tetapi file Excel gagal diperbarui: ' . $e->getMessage());
            }
        }

        if ($id === (int) auth_id() && ! $isAdmin) {
            return redirect()->to(site_url('dashboard'))->with(
                'success',
                'Data pengguna berhasil diperbarui. Hak administrator Anda telah dicabut.'
            );
        }

        return redirect()->to(site_url('pengaturan/users'))->with(
            'success',
            'Data pengguna "' . $input['nama'] . '" berhasil diperbarui.'
        );
    }

    public function deleteUser(int $id): RedirectResponse
    {
        $userModel = model(UserModel::class);
        $user      = $userModel->find($id);

        if ($user === null) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        if ($id === (int) auth_id()) {
            return redirect()->to(site_url('pengaturan/users'))->with(
                'error',
                'Anda tidak dapat menghapus akun yang sedang digunakan.'
            );
        }

        if ((int) $user['is_admin'] === 1 && $userModel->adminCount($id) === 0) {
            return redirect()->to(site_url('pengaturan/users'))->with(
                'error',
                'Pengguna ini adalah satu-satunya administrator dan tidak dapat dihapus.'
            );
        }

        if (! $userModel->delete($id)) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Pengguna gagal dihapus.');
        }

        return redirect()->to(site_url('pengaturan/users'))->with(
            'success',
            'Pengguna "' . $user['nama'] . '" berhasil dihapus. Riwayat penugasannya tetap tersimpan.'
        );
    }

    public function impersonate(int $id): RedirectResponse
    {
        if ($id === (int) auth_id()) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Anda sudah login sebagai pengguna ini.');
        }

        if (is_impersonating()) {
            return redirect()->to(site_url('dashboard'))->with(
                'error',
                'Hentikan mode "Login Sebagai" terlebih dahulu sebelum beralih ke pengguna lain.'
            );
        }

        $userModel = model(UserModel::class);
        $target    = $userModel->find($id);

        if ($target === null) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Pengguna tidak ditemukan.');
        }

        $admin = auth_user();
        $session = session();
        $session->regenerate(true);
        $session->set([
            'impersonator_id' => (int) $admin['id'],
            'impersonator'    => $admin,
            'user_id'         => (int) $target['id'],
            'auth_user'       => [
                'id'       => (int) $target['id'],
                'nama'     => $target['nama'],
                'nip'      => $target['nip'],
                'jabatan'  => $target['jabatan'],
                'is_admin' => (int) $target['is_admin'],
            ],
        ]);

        return redirect()->to(site_url('dashboard'))->with(
            'success',
            'Anda sekarang login sebagai ' . $target['nama'] . '. Gunakan tombol "Kembali ke Admin" untuk mengakhiri mode ini.'
        );
    }

    public function stopImpersonate(): RedirectResponse
    {
        $session = session();
        $adminId = $session->get('impersonator_id');

        if ($adminId === null) {
            return redirect()->to(site_url('dashboard'))->with('info', 'Anda tidak sedang berada dalam mode Login Sebagai.');
        }

        $admin = model(UserModel::class)->find((int) $adminId);
        if ($admin === null || (int) $admin['is_admin'] !== 1) {
            $session->remove(['impersonator_id', 'impersonator']);
            return redirect()->to(site_url('login'))->with('error', 'Akun administrator asal tidak ditemukan. Silakan login kembali.');
        }

        $session->regenerate(true);
        $session->set([
            'user_id'   => (int) $admin['id'],
            'auth_user' => [
                'id'       => (int) $admin['id'],
                'nama'     => $admin['nama'],
                'nip'      => $admin['nip'],
                'jabatan'  => $admin['jabatan'],
                'is_admin' => 1,
            ],
        ]);
        $session->remove(['impersonator_id', 'impersonator']);

        return redirect()->to(site_url('pengaturan/users'))->with(
            'success',
            'Mode Login Sebagai telah berakhir. Anda kembali sebagai ' . $admin['nama'] . '.'
        );
    }

    public function backup()
    {
        $directory = WRITEPATH . 'backups';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Folder backup tidak dapat dibuat.');
        }

        $this->cleanupOldBackups($directory);

        $fileName = 'Backup_Database_GANISPH_' . date('Ymd_His') . '.sqlite';
        $path     = $directory . DIRECTORY_SEPARATOR . $fileName;

        try {
            $db = db_connect();
            $db->query('PRAGMA wal_checkpoint(FULL)');
            $db->query('VACUUM INTO ' . $db->escape($path));
            @chmod($path, 0664);
        } catch (\Throwable $e) {
            return redirect()->to(site_url('pengaturan/users'))->with(
                'error',
                'Backup database gagal dibuat: ' . $e->getMessage()
            );
        }

        if (! is_file($path)) {
            return redirect()->to(site_url('pengaturan/users'))->with('error', 'Berkas backup tidak ditemukan setelah proses pembuatan.');
        }

        return $this->response->download($path, null)->setFileName($fileName);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, string>
     */
    private function validateUserInput(array $input, ?int $exceptId): array
    {
        $errors = [];

        if ($input['nama'] === '') {
            $errors['nama'] = 'Nama wajib diisi.';
        } elseif (mb_strlen($input['nama']) < 2 || mb_strlen($input['nama']) > 150) {
            $errors['nama'] = 'Nama harus terdiri dari 2 sampai 150 karakter.';
        }

        if ($input['nip'] === '') {
            $errors['nip'] = 'NIP wajib diisi.';
        } elseif (! ctype_digit($input['nip'])) {
            $errors['nip'] = 'NIP hanya boleh berisi angka.';
        } elseif (strlen($input['nip']) < 8 || strlen($input['nip']) > 30) {
            $errors['nip'] = 'NIP harus terdiri dari 8 sampai 30 digit.';
        } elseif (model(UserModel::class)->isNipTaken($input['nip'], $exceptId)) {
            $errors['nip'] = 'NIP sudah digunakan oleh pengguna lain.';
        }

        if ($input['jabatan'] === '') {
            $errors['jabatan'] = 'Jabatan wajib diisi.';
        } elseif (mb_strlen($input['jabatan']) > 200) {
            $errors['jabatan'] = 'Jabatan maksimal 200 karakter.';
        }

        return $errors;
    }

    private function cleanupOldBackups(string $directory): void
    {
        $threshold = time() - (7 * 86400);
        foreach (glob($directory . DIRECTORY_SEPARATOR . 'Backup_Database_GANISPH_*.sqlite') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }
}
