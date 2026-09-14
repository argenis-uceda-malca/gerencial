<?php

namespace App\Http\Controllers;

use App\Services\MonitoreoVentasService;
use Illuminate\Http\Request;

class MonitorVentasController extends Controller
{
    public function __construct(private MonitoreoVentasService $monitor) {}

    public function index(Request $request)
    {
        $data = $this->monitor->analizar();
        return view('monitor.ventas', $data);
    }
}
