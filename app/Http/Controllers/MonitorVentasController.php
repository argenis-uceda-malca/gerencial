<?php

namespace App\Http\Controllers;

use App\Services\MonitoreoVentasService;
use Illuminate\Http\Request;

class MonitorVentasController extends Controller
{
    /** @var MonitoreoVentasService */
    private $monitor;

    public function __construct(MonitoreoVentasService $monitor)
    {
        parent::__construct();
        $this->monitor = $monitor;
    }

    public function index(Request $request)
    {
        $data = $this->monitor->analizar();
        return view('monitor.ventas', $data);
    }
}
