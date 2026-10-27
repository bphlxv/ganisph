<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function index(): string
    {
        return view('auth/login', [
            'pageTitle' => 'Login',
        ]);
    }

    public function attempt()
    {
        $rules = [
            'nip'      => 'required|numeric',
            'password' => 'required',
        ];

        $messages = [
            'nip' => [
                'required' => 'NIP wajib diisi.',
                'numeric'  => 'NIP hanya boleh berisi angka.',
            ],
            'password' => [
                'required' => 'Password wajib diisi.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $nip      = trim((string) $this->request->getPost('nip'));
        $password = (string) $this->request->getPost('password');
        $key      = 'login-' . md5($nip . '|' . $this->request->getIPAddress());

        if (! service('throttler')->check($key, 5, MINUTE)) {
            return redirect()->back()->withInput()->with(
                'error',
                'Terlalu banyak percobaan login. Silakan tunggu sekitar satu menit dan coba lagi.'
            );
        }

        $userModel = model(UserModel::class);
        $user      = $userModel->findByNip($nip);

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return redirect()->back()->withInput()->with(
                'error',
                'NIP atau password tidak sesuai. Periksa kembali data login Anda.'
            );
        }

        $session = session();
        $session->regenerate(true);
        $session->set([
            'user_id'   => (int) $user['id'],
            'auth_user' => [
                'id'       => (int) $user['id'],
                'nama'     => $user['nama'],
                'nip'      => $user['nip'],
                'jabatan'  => $user['jabatan'],
                'is_admin' => (int) $user['is_admin'],
            ],
            'login_at'  => date('Y-m-d H:i:s'),
        ]);

        $redirectTo = $session->get('redirect_url');
        $session->remove('redirect_url');

        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
            $redirectTo = site_url('dashboard');
        }

        session()->setFlashdata('success', 'Selamat datang, ' . $user['nama'] . '.');

        return redirect()->to($redirectTo);
    }

    public function logout()
    {
        $session = session();
        $session->regenerate(true);
        $session->remove([
            'user_id',
            'auth_user',
            'login_at',
            'redirect_url',
            'impersonator_id',
            'impersonator',
        ]);
        $session->setFlashdata('success', 'Anda telah keluar dari aplikasi.');

        return redirect()->to(site_url('login'));
    }
}
