<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardConsolidadoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!in_array('acceso_reporte_consolidado', session('permisos', []))) {
                return redirect('/')->with('error', 'No tienes permiso para acceder.');
            }
            return $next($request);
        });
    }

    private array $dayOrder = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];

    private function dayKey(string $dia): string
    {
        return str_replace(
            ['é','á','ó','ú','í','ñ','É','Á','Ó','Ú','Í'],
            ['e','a','o','u','i','n','e','a','o','u','i'],
            strtolower(trim($dia))
        );
    }

    private array $marcaLabel = [
        'MENTHA & CHOCOLATE' => 'MCH',
        'BLUES BY MILK'      => 'BBM',
        'EXIT'               => 'EXIT',
        'MILK'               => 'MILK',
        'FINA'               => 'FINA',
        'KORDA'              => 'KORDA',
        'JOIN'               => 'JOIN',
        'SIN MARCA'          => 'S/M',
    ];

    private function canalDimension(): string
    {
        return 'COALESCE(v_sucursal_3_1, t_txd)';
    }

    private function subcanalDimension(): string
    {
        return "COALESCE(v_sucursal_2_1, t_txd || ' - ' || marca)";
    }

    private function sssDimension(): string
    {
        return 'COALESCE(v_filtro_sss, filtro_sss2)';
    }

    private function localidadDimension(): string
    {
        return 'v_localidad';
    }

    private function applyCanalSubcanalLabel($r, array $ml): string
    {
        $s2 = $r->subcanal ?? '';
        if (($r->canal ?? '') === 'BOUTIQUES' && isset($ml[$r->marca])) {
            $s2 = 'BOUTIQUES ' . ($ml[$r->marca] ?? $r->marca);
        }
        return $s2;
    }

    /* ══════════════════════════════════════════════════════════
       INDEX — meses y marcas disponibles
    ══════════════════════════════════════════════════════════ */
    public function index(Request $request)
    {
        $today = Carbon::today();
        $ini   = $today->copy()->startOfYear()->toDateString();
        $db    = DB::connection('pgsql');

        $scopeAct = function ($q) {
            $q->where(function ($q2) {
                $q2->where('origen', 'VENTAS')->where('tipo_fila', 'ventas_act');
            })->orWhere(function ($q2) {
                $q2->where('origen', 'TXD')->where('tipo_fila', 'VENTA');
            });
        };

        $meses = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("mes, EXTRACT(MONTH FROM fecha)::int AS mes_n")
            ->where($scopeAct)
            ->whereBetween('fecha', [$ini, $today->toDateString()])
            ->groupBy(DB::raw("mes, EXTRACT(MONTH FROM fecha)"))
            ->orderBy(DB::raw("EXTRACT(MONTH FROM fecha)"))
            ->get()
            ->map(fn($r) => ['n' => (int)$r->mes_n, 'nom' => $r->mes])
            ->values()
            ->all();

        $marcas = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("DISTINCT marca")
            ->where($scopeAct)
            ->whereBetween('fecha', [$ini, $today->toDateString()])
            ->orderBy('marca')
            ->pluck('marca')
            ->all();

        return view('dashboard.reporte_consolidado', [
            'meses'  => $meses,
            'marcas' => $marcas,
            'today'  => $today->toDateString(),
            'mesIni' => $today->copy()->startOfMonth()->toDateString(),
            'mesFin' => $today->toDateString(),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
       PIVOT — datos planos para PivotTable.js (consolidado)
    ══════════════════════════════════════════════════════════ */
    public function pivot(Request $request): \Illuminate\Http\JsonResponse
    {
        $ini = $request->input('ini', Carbon::today()->startOfMonth()->toDateString());
        $fin = $request->input('fin', Carbon::today()->toDateString());
        $db  = DB::connection('pgsql');
        $ml  = $this->marcaLabel;

        $canal   = $this->canalDimension();
        $subcanal = $this->subcanalDimension();
        $sss     = $this->sssDimension();
        $loc     = $this->localidadDimension();

        $groupCols = "origen, mes, semana, dia_equivalente, dia_semana,
            {$canal}, {$subcanal},
            corner, marca, categoria, {$sss}, {$loc},
            linea_2, temporada";

        /* ── ACT (año actual) ── */
        $actRows = [];
        foreach ($db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                origen,
                mes, semana::text AS semana,
                dia_equivalente::text AS dia, dia_semana,
                {$canal} AS canal, {$subcanal} AS subcanal,
                corner AS tienda, marca, categoria, {$sss} AS filtro_sss, {$loc} AS localidad,
                linea_2 AS linea, temporada,
                SUM(vta_act)              AS vta26,
                SUM(vta_act - costo_act)  AS gm26,
                SUM(vta_unds_act)         AS unds26,
                SUM(nro_tickets)          AS tickets26,
                SUM(t_vta_soles_si_act)   AS vta_si26
            ")
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('origen', 'VENTAS')->where('tipo_fila', 'ventas_act');
                })->orWhere(function ($q2) {
                    $q2->where('origen', 'TXD')->where('tipo_fila', 'VENTA');
                });
            })
            ->whereBetween('fecha', [$ini, $fin])
            ->groupBy(DB::raw($groupCols))
            ->cursor() as $r) {
            $actRows[] = [
                'Origen'    => $r->origen     ?? '',
                'Mes'       => $r->mes        ?? '',
                'Semana'    => $r->semana     ?? '',
                'Día #'     => $r->dia        ?? '',
                'Día'       => $r->dia_semana ?? '',
                'Canal'     => $r->canal      ?? '',
                'Subcanal'  => $this->applyCanalSubcanalLabel($r, $ml),
                'Tienda'    => $r->tienda     ?? '',
                'Marca'     => $ml[$r->marca] ?? $r->marca ?? '',
                'Categoría' => $r->categoria  ?? '',
                'SSS'       => $r->filtro_sss ?? '',
                'Localidad' => $r->localidad  ?? '',
                'Lineas'    => $r->linea      ?? '',
                'Temporada' => $r->temporada  ?? '',
                'vta26'     => round((float)$r->vta26, 2),
                'gm26'      => round((float)$r->gm26,  2),
                'unds26'    => (int)$r->unds26,
                'tickets26' => (int)$r->tickets26,
                'vta_si26'  => round((float)($r->vta_si26 ?? 0), 2),
            ];
        }

        /* ── HST (histórico) ── */
        // Restricción de tiendas: solo se muestran tiendas que estuvieron abiertas
        // en el AÑO actual del período (aunque ya no tengan venta en el rango exacto).
        // Antes se restringía al mes del rango, lo que descartaba tiendas cerradas
        // recientemente (p.ej. MILK MP TRUJILLO) que sí vendieron este año.
        $iniYear = Carbon::parse($fin)->startOfYear()->toDateString();
        $finYear = Carbon::parse($fin)->endOfYear()->toDateString();

        $hstRows = [];
        foreach ($db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                origen,
                mes, semana::text AS semana,
                dia_equivalente::text AS dia, dia_semana,
                {$canal} AS canal, {$subcanal} AS subcanal,
                corner AS tienda, marca, categoria, {$sss} AS filtro_sss, {$loc} AS localidad,
                linea_2 AS linea, temporada,
                SUM(vta_hst)              AS vta25,
                SUM(vta_hst - costo_hst)  AS gm25,
                SUM(vta_unds_hst)         AS unds25,
                SUM(t_vta_soles_si_hst)   AS vta_si25
            ")
            ->where(function ($q) use ($ini, $fin, $iniYear, $finYear) {
                $q->where(function ($q2) use ($ini, $fin, $iniYear, $finYear) {
                    $q2->where('origen', 'VENTAS')
                        ->where('tipo_fila', 'ventas_hst')
                        ->whereIn('fecha', function ($q3) use ($ini, $fin) {
                            $q3->select('fecha')->from('pla_fechas_equivalentes')
                               ->whereBetween('fecha_equivalente', [$ini, $fin]);
                        })
                        ->whereIn('corner', function ($q4) use ($iniYear, $finYear) {
                            $q4->select('corner')
                               ->from('automatizacion_pla_reporte_consolidado')
                               ->where('origen', 'VENTAS')
                               ->where('tipo_fila', 'ventas_act')
                               ->whereBetween('fecha', [$iniYear, $finYear])
                               ->whereNotNull('corner');
                        });
                })->orWhere(function ($q2) use ($ini, $fin) {
                    $q2->where('origen', 'TXD')
                        ->where('tipo_fila', 'VENTA')
                        ->whereRaw("fecha BETWEEN (?::date - INTERVAL '1 year') AND (?::date - INTERVAL '1 year')", [$ini, $fin]);
                });
            })
            ->groupBy(DB::raw($groupCols))
            ->cursor() as $r) {
            $hstRows[] = [
                'Origen'    => $r->origen     ?? '',
                'Mes'       => $r->mes        ?? '',
                'Semana'    => $r->semana     ?? '',
                'Día #'     => $r->dia        ?? '',
                'Día'       => $r->dia_semana ?? '',
                'Canal'     => $r->canal      ?? '',
                'Subcanal'  => $this->applyCanalSubcanalLabel($r, $ml),
                'Tienda'    => $r->tienda     ?? '',
                'Marca'     => $ml[$r->marca] ?? $r->marca ?? '',
                'Categoría' => $r->categoria  ?? '',
                'SSS'       => $r->filtro_sss ?? '',
                'Localidad' => $r->localidad  ?? '',
                'Lineas'    => $r->linea      ?? '',
                'Temporada' => $r->temporada  ?? '',
                'vta25'     => round((float)$r->vta25, 2),
                'gm25'      => round((float)$r->gm25,  2),
                'unds25'    => (int)$r->unds25,
                'vta_si25'  => round((float)($r->vta_si25 ?? 0), 2),
            ];
        }

        /* ── METAS ── */
        $metasRows = [];
        foreach ($db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                origen,
                mes, semana::text AS semana,
                dia_equivalente::text AS dia, dia_semana,
                {$canal} AS canal, {$subcanal} AS subcanal,
                corner AS tienda, marca, categoria, {$sss} AS filtro_sss, {$loc} AS localidad,
                linea_2 AS linea, temporada,
                SUM(meta) AS meta_vta
            ")
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('origen', 'VENTAS')->where('tipo_fila', 'metas_std');
                })->orWhere(function ($q2) {
                    $q2->where('origen', 'TXD')->where('tipo_fila', 'META');
                });
            })
            ->whereBetween('fecha', [$ini, $fin])
            ->groupBy(DB::raw($groupCols))
            ->cursor() as $r) {
            $metasRows[] = [
                'Origen'    => $r->origen     ?? '',
                'Mes'       => $r->mes        ?? '',
                'Semana'    => $r->semana     ?? '',
                'Día #'     => $r->dia        ?? '',
                'Día'       => $r->dia_semana ?? '',
                'Canal'     => $r->canal      ?? '',
                'Subcanal'  => $this->applyCanalSubcanalLabel($r, $ml),
                'Tienda'    => $r->tienda     ?? '',
                'Marca'     => $ml[$r->marca] ?? $r->marca ?? '',
                'Categoría' => $r->categoria  ?? '',
                'SSS'       => $r->filtro_sss ?? '',
                'Localidad' => $r->localidad  ?? '',
                'Lineas'    => $r->linea      ?? '',
                'Temporada' => $r->temporada  ?? '',
                'meta_vta'  => round((float)$r->meta_vta, 2),
            ];
        }

        /* ── STOCK ── */
        $stockRows = [];
        // Semanas presentes en ventas_act para el rango solicitado
        $actSemanas = $db->table('automatizacion_pla_reporte_consolidado')
            ->where('origen', 'VENTAS')->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha', [$ini, $fin])
            ->distinct()->pluck('semana')->all();

        // Stock: usar fecha <= $fin (no lower bound) para que si el snapshot de
        // stock_act es del día anterior al rango (ej. Aug-31 vs Sep-01) igual aparezca.
        // Se restringe a las mismas semanas de ventas para no traer historia extra.
        $maxDatesByWeek = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw('semana, MAX(fecha) as max_fecha')
            ->where(function ($q) use ($ini, $fin) {
                $q->where(function ($q2) use ($fin) {
                    $q2->where('origen', 'VENTAS')->where('tipo_fila', 'stock_act')
                       ->where('fecha', '<=', $fin);
                })->orWhere(function ($q2) use ($ini, $fin) {
                    $q2->where('origen', 'TXD')->where('tipo_fila', 'VENTA')
                       ->whereBetween('fecha', [$ini, $fin]);
                });
            })
            ->when(!empty($actSemanas), fn ($q) => $q->whereIn('semana', $actSemanas))
            ->groupBy('semana')
            ->get();
        $stockDates = $maxDatesByWeek->pluck('max_fecha')->all();

        if (!empty($stockDates)) {
            $stockGroupCols = "origen, mes, semana,
                {$canal}, {$subcanal},
                corner, marca, categoria, {$sss}, {$loc},
                linea_2, temporada";

            foreach ($db->table('automatizacion_pla_reporte_consolidado')
                ->selectRaw("
                    origen,
                    mes, semana::text AS semana,
                    {$canal} AS canal, {$subcanal} AS subcanal,
                    corner AS tienda, marca, categoria, {$sss} AS filtro_sss, {$loc} AS localidad,
                    linea_2 AS linea, temporada,
                    SUM(inv_unds_act)  AS inv_unds_act,
                    SUM(inv_costo_act) AS inv_costo_act
                ")
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('origen', 'VENTAS')->where('tipo_fila', 'stock_act');
                    })->orWhere(function ($q2) {
                        $q2->where('origen', 'TXD')->where('tipo_fila', 'VENTA');
                    });
                })
                ->whereIn('fecha', $stockDates)
                ->groupBy(DB::raw($stockGroupCols))
                ->cursor() as $r) {
                $stockRows[] = [
                    'Origen'        => $r->origen      ?? '',
                    'Mes'           => $r->mes        ?? '',
                    'Semana'        => $r->semana     ?? '',
                    'Canal'         => $r->canal      ?? '',
                    'Subcanal'      => $this->applyCanalSubcanalLabel($r, $ml),
                    'Tienda'        => $r->tienda     ?? '',
                    'Marca'         => $ml[$r->marca] ?? $r->marca ?? '',
                    'Categoría'     => $r->categoria  ?? '',
                    'SSS'           => $r->filtro_sss ?? '',
                    'Localidad'     => $r->localidad  ?? '',
                    'Lineas'        => $r->linea      ?? '',
                    'Temporada'     => $r->temporada  ?? '',
                    'inv_unds_act'  => (int)$r->inv_unds_act,
                    'inv_costo_act' => round((float)$r->inv_costo_act, 2),
                ];
            }
        }

        return response()->json(['act' => $actRows, 'hst' => $hstRows, 'metas' => $metasRows, 'stock' => $stockRows]);
    }

    /* ══════════════════════════════════════════════════════════
       TABLA DÍA — pivot por dia_semana (consolidado)
    ══════════════════════════════════════════════════════════ */
    public function dia(Request $request): \Illuminate\Http\JsonResponse
    {
        $ini      = $request->input('ini', Carbon::today()->startOfMonth()->toDateString());
        $fin      = $request->input('fin', Carbon::today()->toDateString());
        $origenes = $request->input('origenes', []);
        $canales  = $request->input('canales', []);
        $marcas   = $request->input('marcas',  []);
        $db       = DB::connection('pgsql');

        $canal    = $this->canalDimension();
        $subcanal = $this->subcanalDimension();

        $scopeAct = function ($q) use ($origenes) {
            $q->where(function ($q2) use ($origenes) {
                $q2->where(function ($q3) { $q3->where('origen', 'VENTAS')->where('tipo_fila', 'ventas_act'); });
                $q2->orWhere(function ($q3) { $q3->where('origen', 'TXD')->where('tipo_fila', 'VENTA'); });
            });
            if (!empty($origenes)) {
                $q->whereIn('origen', $origenes);
            }
        };

        $vQuery = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                {$canal}, {$subcanal}, dia_semana,
                SUM(vta_act)     AS vta26,
                SUM(costo_act)   AS costo26
            ")
            ->where($scopeAct)
            ->whereBetween('fecha', [$ini, $fin]);

        if ($canales) $vQuery->whereIn(DB::raw("{$canal}"), $canales);
        if ($marcas)  $vQuery->whereIn('marca', $marcas);

        $ventas = $vQuery
            ->groupBy(DB::raw("{$canal}, {$subcanal}, dia_semana"))
            ->cursor();

        /* HST */
        $hst = [];
        $hQuery = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                {$canal}, {$subcanal}, dia_semana,
                SUM(vta_hst)     AS vta25,
                SUM(costo_hst)   AS costo25
            ")
            ->where(function ($q) use ($ini, $fin) {
                $q->where(function ($q2) use ($ini, $fin) {
                    $q2->where('origen', 'VENTAS')
                        ->where('tipo_fila', 'ventas_hst')
                        ->whereIn('fecha', function ($q3) use ($ini, $fin) {
                            $q3->select('fecha')->from('pla_fechas_equivalentes')
                               ->whereBetween('fecha_equivalente', [$ini, $fin]);
                        });
                })->orWhere(function ($q2) use ($ini, $fin) {
                    $q2->where('origen', 'TXD')
                        ->where('tipo_fila', 'VENTA')
                        ->whereRaw("fecha BETWEEN (?::date - INTERVAL '1 year') AND (?::date - INTERVAL '1 year')", [$ini, $fin]);
                });
            });

        if ($canales) $hQuery->whereIn(DB::raw("{$canal}"), $canales);
        if ($marcas)  $hQuery->whereIn('marca', $marcas);
        if (!empty($origenes)) $hQuery->whereIn('origen', $origenes);

        foreach ($hQuery->groupBy(DB::raw("{$canal}, {$subcanal}, dia_semana"))->cursor() as $r) {
            $hst[$r->subcanal . '|' . $this->dayKey($r->dia_semana ?? '')] = $r;
        }

        /* METAS */
        $metas = [];
        $scopeMetas = function ($q) {
            $q->where(function ($q2) {
                $q2->where('origen', 'VENTAS')->where('tipo_fila', 'metas_std');
            })->orWhere(function ($q2) {
                $q2->where('origen', 'TXD')->where('tipo_fila', 'META');
            });
        };

        $mQuery = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("{$subcanal} AS sucursal_2_1, SUM(meta) AS meta_vta, SUM(meta_contribucion) AS meta_contri")
            ->where($scopeMetas)
            ->whereBetween('fecha', [$ini, $fin]);

        if ($canales) $mQuery->whereIn(DB::raw("{$canal}"), $canales);
        if ($marcas)  $mQuery->whereIn('marca', $marcas);
        if (!empty($origenes)) $mQuery->whereIn('origen', $origenes);

        foreach ($mQuery->groupBy(DB::raw("{$subcanal}, {$canal}"))->cursor() as $r) {
            $metas[$r->sucursal_2_1] = $r;
        }

        /* Build nested structure */
        $data        = [];
        $daysPresent = [];

        foreach ($ventas as $r) {
            $s3   = $r->canal     ?? 'OTROS';
            $s2   = $r->subcanal  ?? 'SIN GRUPO';
            $dKey = $this->dayKey($r->dia_semana ?? '');
            $daysPresent[$dKey] = $r->dia_semana;

            $hKey = $s2 . '|' . $dKey;
            $h    = $hst[$hKey] ?? null;

            if (!isset($data[$s3][$s2][$dKey])) {
                $data[$s3][$s2][$dKey] = [0.0, 0.0, 0.0, 0.0];
            }
            $data[$s3][$s2][$dKey][0] += (float)$r->vta26;
            $data[$s3][$s2][$dKey][1] += $h ? (float)$h->vta25   : 0.0;
            $data[$s3][$s2][$dKey][2] += (float)$r->costo26;
            $data[$s3][$s2][$dKey][3] += $h ? (float)$h->costo25 : 0.0;
        }

        $dayOrderKeys = array_map([$this, 'dayKey'], $this->dayOrder);
        $sortedDays   = [];
        foreach ($dayOrderKeys as $k) {
            if (isset($daysPresent[$k])) $sortedDays[$k] = $daysPresent[$k];
        }

        $s3Order  = ['BOUTIQUES' => 0, 'OUTLETS' => 1, 'WEB' => 2, 'TIENDAS POR DEPARTAMENTO' => 3];
        $s3Groups = array_keys($data);
        usort($s3Groups, fn($a, $b) => ($s3Order[$a] ?? 9) - ($s3Order[$b] ?? 9));

        $rows = [];

        foreach ($s3Groups as $s3) {
            $s2list = array_keys($data[$s3]);
            sort($s2list);

            foreach ($s2list as $s2) {
                $meta  = isset($metas[$s2]) ? (float)$metas[$s2]->meta_vta   : 0.0;
                $metaC = isset($metas[$s2]) ? (float)$metas[$s2]->meta_contri : 0.0;
                $rows[] = $this->buildDiaRow($s2, $s3, $data[$s3][$s2], $meta, $metaC, $sortedDays, false, false);
            }

            $aggData  = $this->aggregateDayData(array_values($data[$s3]), $sortedDays);
            $aggMeta  = $aggMetaC = 0.0;
            foreach ($s2list as $s2) {
                $aggMeta  += isset($metas[$s2]) ? (float)$metas[$s2]->meta_vta   : 0.0;
                $aggMetaC += isset($metas[$s2]) ? (float)$metas[$s2]->meta_contri : 0.0;
            }
            $rows[] = $this->buildDiaRow("Total $s3", $s3, $aggData, $aggMeta, $aggMetaC, $sortedDays, true, false);
        }

        $allDayArrays = [];
        foreach ($data as $s3 => $s2map) {
            foreach ($s2map as $dayMap) {
                $allDayArrays[] = $dayMap;
            }
        }
        $allDayData = $this->aggregateDayData($allDayArrays, $sortedDays);
        $totalMeta = $totalMetaC = 0.0;
        foreach ($metas as $m) { $totalMeta += (float)$m->meta_vta; $totalMetaC += (float)$m->meta_contri; }
        $rows[] = $this->buildDiaRow('Total general', '', $allDayData, $totalMeta, $totalMetaC, $sortedDays, true, true);

        return response()->json(['rows' => $rows, 'days' => $sortedDays]);
    }

    private function aggregateDayData(array $s2DataList, array $sortedDays): array
    {
        $agg = [];
        foreach ($s2DataList as $dayMap) {
            foreach ($sortedDays as $dKey => $_) {
                if (!isset($dayMap[$dKey])) continue;
                if (!isset($agg[$dKey])) $agg[$dKey] = [0.0, 0.0, 0.0, 0.0];
                for ($i = 0; $i < 4; $i++) $agg[$dKey][$i] += $dayMap[$dKey][$i];
            }
        }
        return $agg;
    }

    private function buildDiaRow(string $label, string $s3, array $dayData, float $meta, float $metaC, array $sortedDays, bool $isSub, bool $isTotal): array
    {
        $row = ['label' => $label, 's3' => $s3, '_esSubtotal' => $isSub, '_esTotal' => $isTotal];

        $totVta26 = $totVta25 = $totCosto26 = $totCosto25 = 0.0;

        foreach ($sortedDays as $dKey => $_) {
            [$vta26, $vta25, $costo26, $costo25] = $dayData[$dKey] ?? [0.0, 0.0, 0.0, 0.0];
            $totVta26   += $vta26;
            $totVta25   += $vta25;
            $totCosto26 += $costo26;
            $totCosto25 += $costo25;
            $contrib26   = $vta26 - $costo26;

            $row[$dKey.'_vta26']  = round($vta26, 2);
            $row[$dKey.'_var']    = $vta25 > 0 ? round(($vta26-$vta25)/$vta25*100, 1) : null;
            $row[$dKey.'_gm26']   = $vta26 > 0 ? round($contrib26/$vta26*100, 1)       : null;
            $row[$dKey.'_gm25']   = $vta25 > 0 ? round(($vta25-$costo25)/$vta25*100,1) : null;
            $row[$dKey.'_cumpl']  = ($meta  > 0 && $vta26 > 0) ? round($vta26/$meta*100, 1)    : null;
            $row[$dKey.'_cumplc'] = ($metaC > 0 && $contrib26 != 0) ? round($contrib26/$metaC*100, 1) : null;
        }

        $tContrib26 = $totVta26 - $totCosto26;
        $row['total_vta26']  = round($totVta26, 2);
        $row['total_var']    = $totVta25 > 0 ? round(($totVta26-$totVta25)/$totVta25*100, 1) : null;
        $row['total_gm26']   = $totVta26 > 0 ? round($tContrib26/$totVta26*100, 1)            : null;
        $row['total_gm25']   = $totVta25 > 0 ? round(($totVta25-$totCosto25)/$totVta25*100,1) : null;
        $row['total_cumpl']  = ($meta  > 0 && $totVta26  > 0) ? round($totVta26/$meta*100, 1)     : null;
        $row['total_cumplc'] = ($metaC > 0 && $tContrib26 != 0) ? round($tContrib26/$metaC*100, 1)  : null;

        return $row;
    }

    /* ══════════════════════════════════════════════════════════
       TABLA DETALLE — columnas fijas, agrupado por canal (consolidado)
    ══════════════════════════════════════════════════════════ */
    public function detalle(Request $request): \Illuminate\Http\JsonResponse
    {
        $ini        = $request->input('ini', Carbon::today()->startOfMonth()->toDateString());
        $fin        = $request->input('fin', Carbon::today()->toDateString());
        $origenes   = $request->input('origenes', []);
        $canales    = $request->input('canales', []);
        $marcas     = $request->input('marcas',  []);
        $semanas    = $request->input('semanas', []);
        $dias       = $request->input('dias', []);
        $tiendas    = $request->input('tiendas', []);
        $categorias = $request->input('categorias', []);
        $sss        = $request->input('sss', []);
        $localidades= $request->input('localidades', []);
        $db         = DB::connection('pgsql');

        $canal    = $this->canalDimension();
        $subcanal = $this->subcanalDimension();
        $sssCol   = $this->sssDimension();
        $locCol   = $this->localidadDimension();

        $applyFilters = function ($q) use ($origenes, $canales, $marcas, $semanas, $dias, $tiendas, $categorias, $sss, $localidades, $canal, $subcanal, $sssCol, $locCol) {
            if ($origenes)    $q->whereIn('origen', $origenes);
            if ($canales)     $q->whereIn(DB::raw("{$canal}"), $canales);
            if ($marcas)      $q->whereIn('marca', $marcas);
            if ($semanas)     $q->whereIn('semana', $semanas);
            if ($dias)        $q->whereIn('dia_semana', $dias);
            if ($tiendas)     $q->whereIn('corner', $tiendas);
            if ($categorias)  $q->whereIn('categoria', $categorias);
            if ($sss)         $q->whereIn(DB::raw("{$sssCol}"), $sss);
            if ($localidades) $q->whereIn(DB::raw("{$locCol}"), $localidades);
            return $q;
        };

        $scopeAct = function ($q) {
            $q->where(function ($q2) {
                $q2->where('origen', 'VENTAS')->where('tipo_fila', 'ventas_act');
            })->orWhere(function ($q2) {
                $q2->where('origen', 'TXD')->where('tipo_fila', 'VENTA');
            });
        };

        /* ── ACT ── */
        $ventas = [];
        $vQuery = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                {$canal}, {$subcanal},
                SUM(vta_act)              AS vta26,
                SUM(costo_act)            AS costo26,
                SUM(vta_unds_act)         AS unds26,
                NULLIF(SUM(pvp * vta_unds_act), 0) AS pvp_total,
                SUM(t_vta_soles_si_act)   AS vta_si26
            ")
            ->where($scopeAct)
            ->whereBetween('fecha', [$ini, $fin]);

        $applyFilters($vQuery);

        foreach ($vQuery->groupBy(DB::raw("{$canal}, {$subcanal}"))->cursor() as $r) {
            $ventas[$r->subcanal] = $r;
        }

        /* ── HST ── */
        $ventasHst = [];
        $hQuery = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("
                {$canal}, {$subcanal},
                SUM(vta_hst)              AS vta25,
                SUM(costo_hst)            AS costo25,
                SUM(vta_unds_hst)         AS unds25,
                SUM(t_vta_soles_si_hst)   AS vta_si25
            ")
            ->where(function ($q) use ($ini, $fin) {
                $q->where(function ($q2) use ($ini, $fin) {
                    $q2->where('origen', 'VENTAS')
                        ->where('tipo_fila', 'ventas_hst')
                        ->whereIn('fecha', function ($q3) use ($ini, $fin) {
                            $q3->select('fecha')->from('pla_fechas_equivalentes')
                               ->whereBetween('fecha_equivalente', [$ini, $fin]);
                        });
                })->orWhere(function ($q2) use ($ini, $fin) {
                    $q2->where('origen', 'TXD')
                        ->where('tipo_fila', 'VENTA')
                        ->whereRaw("fecha BETWEEN (?::date - INTERVAL '1 year') AND (?::date - INTERVAL '1 year')", [$ini, $fin]);
                });
            });

        $applyFilters($hQuery);

        foreach ($hQuery->groupBy(DB::raw("{$canal}, {$subcanal}"))->cursor() as $r) {
            $ventasHst[$r->subcanal] = $r;
        }

        /* ── METAS ── */
        $metas = [];
        $scopeMetas = function ($q) {
            $q->where(function ($q2) {
                $q2->where('origen', 'VENTAS')->where('tipo_fila', 'metas_std');
            })->orWhere(function ($q2) {
                $q2->where('origen', 'TXD')->where('tipo_fila', 'META');
            });
        };

        $mQuery = $db->table('automatizacion_pla_reporte_consolidado')
            ->selectRaw("{$subcanal} AS sucursal_2_1, {$canal} AS sucursal_3_1, SUM(meta) AS meta_vta, SUM(meta_contribucion) AS meta_contri")
            ->where($scopeMetas)
            ->whereBetween('fecha', [$ini, $fin]);

        $applyFilters($mQuery);

        foreach ($mQuery->groupBy(DB::raw("{$subcanal}, {$canal}"))->cursor() as $r) {
            $metas[$r->sucursal_2_1] = $r;
        }

        $totalVta26 = array_sum(array_column($ventas, 'vta26'));
        $totalVta25 = array_sum(array_column($ventasHst, 'vta25'));

        $allS2 = array_unique(array_merge(array_keys($ventas), array_keys($metas)));

        $s3Order = ['BOUTIQUES' => 0, 'OUTLETS' => 1, 'WEB' => 2, 'TIENDAS POR DEPARTAMENTO' => 3];
        $byS3    = [];

        foreach ($allS2 as $s2) {
            $v  = $ventas[$s2]    ?? null;
            $h  = $ventasHst[$s2] ?? null;
            $m  = $metas[$s2]     ?? null;
            $s3 = $v->sucursal_3_1 ?? ($m->sucursal_3_1 ?? 'OTROS');
            $byS3[$s3][] = $this->buildDetalleRow($s2, $s3, $v, $h, $m, $totalVta26, $totalVta25, false, false);
        }

        $s3Groups = array_keys($byS3);
        usort($s3Groups, fn($a, $b) => ($s3Order[$a] ?? 9) - ($s3Order[$b] ?? 9));

        $rows = [];
        foreach ($s3Groups as $s3) {
            usort($byS3[$s3], fn($a, $b) => $b['vta26'] <=> $a['vta26']);
            foreach ($byS3[$s3] as $row) $rows[] = $row;
            $rows[] = $this->buildDetalleSubtotal("Total $s3", $s3, $byS3[$s3], $totalVta26, $totalVta25, false);
        }

        $allLeaf = array_merge(...array_values($byS3));
        $rows[] = $this->buildDetalleSubtotal('Total general', '', $allLeaf, $totalVta26, $totalVta25, true);

        return response()->json($rows);
    }

    private function buildDetalleRow(string $label, string $s3, $v, $h, $m, float $tv26, float $tv25, bool $isSub, bool $isTotal): array
    {
        $vta26   = $v ? (float)$v->vta26    : 0.0;
        $vta25   = $h ? (float)$h->vta25    : 0.0;
        $costo26 = $v ? (float)$v->costo26  : 0.0;
        $costo25 = $h ? (float)$h->costo25  : 0.0;
        $unds26  = $v ? (float)$v->unds26   : 0.0;
        $unds25  = $h ? (float)$h->unds25   : 0.0;
        $pvpT    = $v ? (float)($v->pvp_total ?? 0) : 0.0;
        $vtaSi26 = $v ? (float)($v->vta_si26 ?? 0) : 0.0;
        $vtaSi25 = $h ? (float)($h->vta_si25 ?? 0) : 0.0;
        $metaV   = $m ? (float)$m->meta_vta   : 0.0;
        $metaC   = $m ? (float)$m->meta_contri : 0.0;

        $contrib26 = $vta26 - $costo26;
        $contrib25 = $vta25 - $costo25;
        $pprom26   = $unds26 > 0 ? round($vta26 / $unds26, 2) : null;
        $pprom25   = $unds25 > 0 ? round($vta25 / $unds25, 2) : null;
        $dscto26   = $pvpT  > 0 ? round((1 - $vta26 / $pvpT) * 100, 1) : null;
        $gmMeta    = $metaV > 0 ? round($metaC / $metaV * 100, 1) : null;

        return [
            'label'        => $label,
            's3'           => $s3,
            '_esSubtotal'  => $isSub,
            '_esTotal'     => $isTotal,
            'vta26'        => round($vta26, 2),
            'vta25'        => round($vta25, 2),
            'var_vta'      => $vta25 > 0 ? round(($vta26-$vta25)/$vta25*100, 1)          : null,
            'part26'       => $tv26  > 0 ? round($vta26/$tv26*100, 1)                     : null,
            'part25'       => $tv25  > 0 ? round($vta25/$tv25*100, 1)                     : null,
            'meta_vta'     => round($metaV, 2),
            'cumpl_meta'   => $metaV > 0 ? round($vta26/$metaV*100, 1)                    : null,
            'gm26'         => $vta26 > 0 ? round($contrib26/$vta26*100, 1)                : null,
            'gm25'         => $vta25 > 0 ? round($contrib25/$vta25*100, 1)                : null,
            'gm_meta'      => $gmMeta,
            'contrib26'    => round($contrib26, 2),
            'contrib25'    => round($contrib25, 2),
            'var_contrib'  => $contrib25 != 0 ? round(($contrib26-$contrib25)/abs($contrib25)*100, 1) : null,
            'meta_contri'  => round($metaC, 2),
            'cumpl_contri' => $metaC != 0 ? round($contrib26/$metaC*100, 1)               : null,
            'unds26'       => (int)$unds26,
            'unds25'       => (int)$unds25,
            'var_unds'     => $unds25 > 0 ? round(($unds26-$unds25)/$unds25*100, 1)       : null,
            'pprom26'      => $pprom26,
            'pprom25'      => $pprom25,
            'var_pprom'    => ($pprom25 && $pprom25 > 0) ? round(($pprom26-$pprom25)/$pprom25*100, 1) : null,
            'dscto26'      => $dscto26,
            'vta_si26'     => round($vtaSi26, 2),
            'vta_si25'     => round($vtaSi25, 2),
        ];
    }

    private function buildDetalleSubtotal(string $label, string $s3, array $rows, float $tv26, float $tv25, bool $isTotal): array
    {
        $leaf = array_filter($rows, fn($r) => !$r['_esSubtotal']);

        $vta26    = array_sum(array_column($leaf, 'vta26'));
        $vta25    = array_sum(array_column($leaf, 'vta25'));
        $contrib26= array_sum(array_column($leaf, 'contrib26'));
        $contrib25= array_sum(array_column($leaf, 'contrib25'));
        $metaV    = array_sum(array_column($leaf, 'meta_vta'));
        $metaC    = array_sum(array_column($leaf, 'meta_contri'));
        $unds26   = array_sum(array_column($leaf, 'unds26'));
        $unds25   = array_sum(array_column($leaf, 'unds25'));
        $vtaSi26  = array_sum(array_column($leaf, 'vta_si26'));
        $vtaSi25  = array_sum(array_column($leaf, 'vta_si25'));
        $pprom26  = $unds26 > 0 ? round($vta26/$unds26, 2) : null;
        $pprom25  = $unds25 > 0 ? round($vta25/$unds25, 2) : null;

        return [
            'label'        => $label,
            's3'           => $s3,
            '_esSubtotal'  => true,
            '_esTotal'     => $isTotal,
            'vta26'        => round($vta26, 2),
            'vta25'        => round($vta25, 2),
            'var_vta'      => $vta25 > 0 ? round(($vta26-$vta25)/$vta25*100, 1)          : null,
            'part26'       => $tv26 > 0 ? round($vta26/$tv26*100, 1)                      : null,
            'part25'       => $tv25 > 0 ? round($vta25/$tv25*100, 1)                      : null,
            'meta_vta'     => round($metaV, 2),
            'cumpl_meta'   => $metaV > 0 ? round($vta26/$metaV*100, 1)                    : null,
            'gm26'         => $vta26 > 0 ? round($contrib26/$vta26*100, 1)                : null,
            'gm25'         => $vta25 > 0 ? round($contrib25/$vta25*100, 1)                : null,
            'gm_meta'      => $metaV > 0 ? round($metaC/$metaV*100, 1)                    : null,
            'contrib26'    => round($contrib26, 2),
            'contrib25'    => round($contrib25, 2),
            'var_contrib'  => $contrib25 != 0 ? round(($contrib26-$contrib25)/abs($contrib25)*100, 1) : null,
            'meta_contri'  => round($metaC, 2),
            'cumpl_contri' => $metaC != 0 ? round($contrib26/$metaC*100, 1)               : null,
            'unds26'       => (int)$unds26,
            'unds25'       => (int)$unds25,
            'var_unds'     => $unds25 > 0 ? round(($unds26-$unds25)/$unds25*100, 1)       : null,
            'pprom26'      => $pprom26,
            'pprom25'      => $pprom25,
            'var_pprom'    => ($pprom25 && $pprom25 > 0) ? round(($pprom26-$pprom25)/$pprom25*100, 1) : null,
            'dscto26'      => null,
            'vta_si26'     => round($vtaSi26, 2),
            'vta_si25'     => round($vtaSi25, 2),
        ];
    }
}
