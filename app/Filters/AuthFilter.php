<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId === null) {
            if ($request->getMethod() === 'GET') {
                $path  = $request->getUri()->getPath();
                $query = $request->getUri()->getQuery();
                $session->set('redirect_url', $path . ($query !== '' ? '?' . $query : ''));
            }

            return redirect()->to(site_url('login'))->with('error', 'Silakan login terlebih dahulu.');
        }

        $userModel = model(UserModel::class);
        $user      = $userModel->find((int) $userId);

        if ($user === null) {
            $session->remove(['user_id', 'auth_user', 'impersonator_id', 'impersonator']);

            return redirect()->to(site_url('login'))->with('error', 'Sesi Anda tidak valid. Silakan login kembali.');
        }

        $session->set('auth_user', [
            'id'       => (int) $user['id'],
            'nama'     => $user['nama'],
            'nip'      => $user['nip'],
            'jabatan'  => $user['jabatan'],
            'is_admin' => (int) $user['is_admin'],
        ]);

        $impersonatorId = $session->get('impersonator_id');
        if ($impersonatorId !== null) {
            $impersonator = $userModel->find((int) $impersonatorId);

            if ($impersonator === null || (int) $impersonator['is_admin'] !== 1) {
                $session->remove(['impersonator_id', 'impersonator']);
            } else {
                $session->set('impersonator', [
                    'id'       => (int) $impersonator['id'],
                    'nama'     => $impersonator['nama'],
                    'nip'      => $impersonator['nip'],
                    'jabatan'  => $impersonator['jabatan'],
                    'is_admin' => 1,
                ]);
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
