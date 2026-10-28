<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', static function () {
    return session('user_id') !== null
        ? redirect()->to(site_url('dashboard'))
        : redirect()->to(site_url('login'));
});

$routes->get('login', 'Auth::index', ['filter' => 'guest']);
$routes->post('login', 'Auth::attempt', ['filter' => 'guest']);

$routes->group('', ['filter' => 'auth'], static function ($routes): void {
    $routes->post('logout', 'Auth::logout');

    $routes->get('dashboard', 'Dashboard::index');

    $routes->get('penugasan', 'Penugasan::index');
    $routes->get('daftar-penugasan', 'Penugasan::daftar');
    $routes->post('penugasan', 'Penugasan::save');
    $routes->post('penugasan/sync', 'Penugasan::sync');
    $routes->get('penugasan/download', 'Penugasan::downloadExcel');
    $routes->get('penugasan/(:num)/edit', 'Penugasan::edit/$1');
    $routes->post('penugasan/(:num)', 'Penugasan::update/$1');
    $routes->post('penugasan/(:num)/delete', 'Penugasan::delete/$1');

    $routes->get('telaahan/(:num)/docx', 'Penugasan::downloadTelaahan/$1');

    $routes->get('pengaturan', 'Pengaturan::index');
    $routes->post('pengaturan/password', 'Pengaturan::changePassword');
    $routes->post('impersonasi/stop', 'Pengaturan::stopImpersonate');

    $routes->group('pengaturan', ['filter' => 'admin'], static function ($routes): void {
        $routes->get('reset-password', 'Pengaturan::resetPassword');
        $routes->post('reset-password/(:num)', 'Pengaturan::doResetPassword/$1');

        $routes->get('users', 'Pengaturan::users');
        $routes->get('users/create', 'Pengaturan::createUser');
        $routes->post('users', 'Pengaturan::storeUser');
        $routes->get('users/(:num)/edit', 'Pengaturan::editUser/$1');
        $routes->post('users/(:num)', 'Pengaturan::updateUser/$1');
        $routes->post('users/(:num)/delete', 'Pengaturan::deleteUser/$1');
        $routes->post('users/(:num)/impersonate', 'Pengaturan::impersonate/$1');

        $routes->get('backup', 'Pengaturan::backup');
    });
});
