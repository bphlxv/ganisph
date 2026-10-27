<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 */
abstract class BaseController extends Controller
{
    /**
     * Helpers loaded on every controller.
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'ganisph'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Render a view inside the main AdminLTE layout.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], string $title = ''): string
    {
        $data['pageTitle'] = $title;
        $data['content']   = view($view, $data);

        return view('layouts/main', $data);
    }
}
