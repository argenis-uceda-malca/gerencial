<?php

namespace App\Services\Txd;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use Carbon\Carbon;

class TxdFileParser
{
    /**
     * Parsea un archivo Oechsle (CSV) y devuelve Collection.
     * Detecta encoding y delimitador automáticamente.
     * PERIODO = split(' al ')[0]; columnas faltantes → NA.
     */
    public function parseOechsle($file): Collection
    {
        $path = $file->getRealPath();
        $content = file_get_contents($path);

        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_oech_' . uniqid() . '.' . $ext;
        file_put_contents($tmpPath, $content);
        $delimiter = $this->detectDelimiter($content);

        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);

        $rows = new Collection();
        $header = null;
        $rowIndex = 0;

        foreach ($reader->getSheetIterator()->current()->getRowIterator() as $row) {
            $cells = array_map(function ($cell) {
                return trim($cell->getValue() ?? '');
            }, $row->getCells());

            if ($rowIndex === 0) {
                $header = $cells;
                $rowIndex++;
                continue;
            }

            if (count($cells) !== count($header)) {
                continue;
            }

            $rowMap = array_combine($header, $cells);
            if ($rowMap === false) {
                continue;
            }

            $periodoRaw = $rowMap['PERIODO'] ?? '';
            $periodo = is_string($periodoRaw) ? explode(' al ', $periodoRaw)[0] : (string) $periodoRaw;

            $data = [
                'fecha'      => $periodo ?: ($rowMap['FECHA'] ?? 'NA'),
                'sku_txd'    => $rowMap['SKU'] ?? $rowMap['SKU_TXD'] ?? 'NA',
                'desc_sku'   => $rowMap['DESC_SKU'] ?? $rowMap['DESCRIPCION'] ?? 'NA',
                'marca'      => $rowMap['MARCA'] ?? 'NA',
                'cod_local'  => $rowMap['COD_LOCAL'] ?? $rowMap['CODIGO_LOCAL'] ?? 'NA',
                'desc_local' => $rowMap['DESC_LOCAL'] ?? $rowMap['DESC_SUCURSAL'] ?? $rowMap['NOMBRE_LOCAL'] ?? 'NA',
                'vta_act'    => $this->numericOrNull($rowMap['VTA_ACT'] ?? $rowMap['VENTA'] ?? null),
                'vta_unds'   => $this->numericOrNull($rowMap['VTA_UNDS'] ?? $rowMap['CANTIDAD'] ?? null),
                'stk_soles'  => $this->numericOrNull($rowMap['STK_SOLES'] ?? $rowMap['STOCK_SOLES'] ?? null),
                'stk_unds'   => $this->numericOrNull($rowMap['STK_UNDS'] ?? $rowMap['STOCK_UNDS'] ?? null),
            ];

            $rows->push((object) $data);
            $rowIndex++;
        }

        $reader->close();
        @unlink($tmpPath);

        Log::info('[TxdFileParser] Oechsle parseado', ['rows' => $rows->count()]);

        return $rows;
    }

    /**
     * Parsea Ripley (.xlsx, hoja "TD1").
     * Fecha %d-%m-%Y → %Y-%m-%d; Codigo Modelo/Variacion forzados a texto.
     */
    public function parseRipley($file): Collection
    {
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_ripley_' . uniqid() . '.' . $ext;
        copy($file->getRealPath(), $tmpPath);

        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);

        $sheet = null;
        foreach ($reader->getSheetIterator() as $sheetCandidate) {
            if (strtolower(trim($sheetCandidate->getName())) === 'td1') {
                $sheet = $sheetCandidate;
                break;
            }
        }
        if (!$sheet) {
            $sheet = $reader->getSheetIterator()->current();
        }

        $rows = new Collection();
        $header = null;
        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = array_map(function ($cell) {
                return trim($cell->getValue() ?? '');
            }, $row->getCells());

            if ($rowIndex === 0) {
                $header = $cells;
                $rowIndex++;
                continue;
            }

            if (empty(array_filter($cells, fn($c) => $c !== ''))) {
                continue;
            }

            if (count($cells) !== count($header)) {
                continue;
            }

            $rowMap = array_combine($header, $cells);
            if ($rowMap === false) {
                continue;
            }

            $fechaRaw = $rowMap['FECHA'] ?? $rowMap['Fecha'] ?? '';
            $fecha = $this->parseRipleyDate($fechaRaw);

            $data = [
                'fecha'         => $fecha,
                'marca'         => $rowMap['MARCA'] ?? $rowMap['Marca'] ?? 'NA',
                'temporada'     => $rowMap['TEMPORADA'] ?? $rowMap['Temporada'] ?? 'NA',
                'sucursal'      => $rowMap['SUCURSAL'] ?? $rowMap['Sucursal'] ?? 'NA',
                'costo_vta'     => $this->numericOrNull($rowMap['COSTO_VTA'] ?? $rowMap['Costo_Vta'] ?? null),
                'codigo_modelo' => (string) ($rowMap['CODIGO_MODELO'] ?? $rowMap['Codigo_Modelo'] ?? $rowMap['COD_MODELO'] ?? ''),
                'nombre_modelo' => $rowMap['NOMBRE_MODELO'] ?? $rowMap['Nombre_Modelo'] ?? 'NA',
                'rebate_act'    => $this->numericOrNull($rowMap['REBATE_ACT'] ?? $rowMap['Rebate_Act'] ?? null),
                'sku_txd'       => $rowMap['SKU_TXD'] ?? $rowMap['SKU'] ?? 'NA',
                'desc_sku'      => $rowMap['DESC_SKU'] ?? $rowMap['Desc_Sku'] ?? 'NA',
                'vta_soles'     => $this->numericOrNull($rowMap['VTA_SOLES'] ?? $rowMap['Vta_Soles'] ?? null),
                'vta_unds'      => $this->numericOrNull($rowMap['VTA_UNDS'] ?? $rowMap['Vta_Unds'] ?? null),
                'contr'         => $this->numericOrNull($rowMap['CONTR'] ?? $rowMap['Contr'] ?? null),
                'stock_soles'   => $this->numericOrNull($rowMap['STOCK_SOLES'] ?? $rowMap['Stock_Soles'] ?? null),
                'stock_unds'    => $this->numericOrNull($rowMap['STOCK_UNDS'] ?? $rowMap['Stock_Unds'] ?? null),
            ];

            $rows->push((object) $data);
            $rowIndex++;
        }

        $reader->close();
        @unlink($tmpPath);

        Log::info('[TxdFileParser] Ripley parseado', ['rows' => $rows->count()]);

        return $rows;
    }

    /**
     * Parsea Falabella Stock (.xlsx, hoja "Product details").
     * Selecciona SKU GSC, Descripcion, Temporada, Stock Disponible, Marca.
     * Agrupa por SKU+Desc, inserta sucursal='Tienda Virtual'.
     */
    public function parseFalabellaStock($file): Collection
    {
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_fbstock_' . uniqid() . '.' . $ext;
        copy($file->getRealPath(), $tmpPath);

        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);

        $sheet = null;
        foreach ($reader->getSheetIterator() as $sheetCandidate) {
            if (strtolower(trim($sheetCandidate->getName())) === 'product details') {
                $sheet = $sheetCandidate;
                break;
            }
        }
        if (!$sheet) {
            $sheet = $reader->getSheetIterator()->current();
        }

        $rows = new Collection();
        $header = null;
        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = array_map(function ($cell) {
                return trim($cell->getValue() ?? '');
            }, $row->getCells());

            if ($rowIndex === 0) {
                $header = $cells;
                $rowIndex++;
                continue;
            }

            if (empty(array_filter($cells, fn($c) => $c !== ''))) {
                continue;
            }

            if (count($cells) !== count($header)) {
                continue;
            }

            $rowMap = array_combine($header, $cells);
            if ($rowMap === false) {
                continue;
            }

            $sku = trim($rowMap['SKU GSC'] ?? $rowMap['SKU_GSC'] ?? $rowMap['SKU'] ?? '');
            $desc = trim($rowMap['DESCRIPCION'] ?? $rowMap['Descripcion'] ?? $rowMap['DESCRIPTION'] ?? '');

            if (!$sku && !$desc) {
                continue;
            }

            $stockRaw = $rowMap['STOCK DISPONIBLE'] ?? $rowMap['STOCK_DISPONIBLE'] ?? $rowMap['Stock_Disponible'] ?? 0;
            if (!is_numeric($stockRaw)) {
                $stockVal = filter_var($stockRaw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                $stockRaw = $stockVal !== false ? (int) $stockVal : 0;
            }

            $rows->push((object) [
                'sku_txd'      => $sku,
                'desc_hijo_txd' => $desc,
                'sucursal'     => 'Tienda Virtual',
                'temporada'    => $rowMap['TEMPORADA'] ?? $rowMap['Temporada'] ?? 'NA',
                'inv_unds_act' => (int) $stockRaw,
                'marca'        => $rowMap['MARCA'] ?? $rowMap['Marca'] ?? 'NA',
            ]);
            $rowIndex++;
        }

        $reader->close();
        @unlink($tmpPath);

        Log::info('[TxdFileParser] Falabella Stock parseado', ['rows' => $rows->count()]);

        return $rows;
    }

    /**
     * Parsea Falabella Ventas (.xlsx, hoja "Sheet 1", export Seller Center).
     * Extrae día real de Created at, pivotea a columnas día (0/1).
     * Agrupa por SKU+Desc+Precio. día calculado con format('l'), no posicional.
     * Paid Price × unidades = vta_soles (correctamente).
     */
    public function parseFalabellaVentas($file): Collection
    {
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_fbventas_' . uniqid() . '.' . $ext;
        copy($file->getRealPath(), $tmpPath);

        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);

        $sheet = null;
        foreach ($reader->getSheetIterator() as $sheetCandidate) {
            if (strtolower(trim($sheetCandidate->getName())) === 'sheet 1') {
                $sheet = $sheetCandidate;
                break;
            }
        }
        if (!$sheet) {
            $sheet = $reader->getSheetIterator()->current();
        }

        $diaMap = ['monday' => 'lunes', 'tuesday' => 'martes', 'wednesday' => 'miercoles',
                    'thursday' => 'jueves', 'friday' => 'viernes', 'saturday' => 'sabado', 'sunday' => 'domingo'];
        $agrupados = [];
        $nroLocal = 1;
        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $cells = array_map(function ($cell) {
                return trim($cell->getValue() ?? '');
            }, $row->getCells());

            if ($rowIndex === 0) {
                $header = $cells;
                $rowIndex++;
                continue;
            }

            if (empty(array_filter($cells, fn($c) => $c !== ''))) {
                continue;
            }

            if (count($cells) !== count($header)) {
                continue;
            }

            $rowMap = array_combine($header, $cells);
            if ($rowMap === false) {
                continue;
            }
            $rowMap = array_change_key_case($rowMap, CASE_LOWER);

            $sku = trim($rowMap['sku'] ?? '');
            $desc = trim($rowMap['desc_sku'] ?? $rowMap['descripcion'] ?? '');
            $precio = trim($rowMap['paid price'] ?? $rowMap['paid_price'] ?? $rowMap['precio'] ?? 0);
            $createdAt = trim($rowMap['created at'] ?? $rowMap['created_at'] ?? '');

            $unidades = 0;
            foreach ($header as $h) {
                $cl = strtolower(str_replace(' ', '_', trim($h)));
                if (strpos($cl, 'unidades') !== false || strpos($cl, 'qty') !== false ||
                    strpos($cl, 'quantity') !== false || strpos($cl, 'cantidad') !== false) {
                    $unidades = (int) ($rowMap[strtolower($h)] ?? 0);
                    break;
                }
            }
            if (!$unidades) {
                $unidades = (int) ($rowMap['unidades'] ?? $rowMap['unidades'] ?? $rowMap['cantidad'] ?? 0);
            }

            if (!$sku && !$desc) {
                continue;
            }

            try {
                $fecha = Carbon::parse($createdAt);
            } catch (\Throwable $e) {
                Log::warning('[TxdFileParser] No se pudo parsear fecha', ['date' => $createdAt]);
                continue;
            }

            $diaKey = $diaMap[strtolower($fecha->format('l'))] ?? 'lunes';
            $key = $sku . '|' . $desc . '|' . $precio;

            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'sku'        => $sku,
                    'desc_sku'   => $desc,
                    'sucursal'   => 'Tienda Virtual',
                    'lunes'      => 0, 'martes' => 0, 'miercoles' => 0, 'jueves' => 0,
                    'viernes'    => 0, 'sabado' => 0, 'domingo' => 0,
                    'vta_unds'   => 0,
                    'vta_soles'  => 0,
                    'nro_local'  => $nroLocal++,
                    'marca'      => '',
                    'skip'       => 0,
                ];
            }
            $agrupados[$key][$diaKey] += $unidades;
            $agrupados[$key]['vta_unds'] += $unidades;
            $agrupados[$key]['vta_soles'] += round((float) $precio * $unidades, 2);
        }

        $reader->close();
        @unlink($tmpPath);

        $rows = new Collection();

        foreach ($agrupados as $row) {
            $rows->push((object) $row);
        }

        Log::info('[TxdFileParser] Falabella Ventas parseado', ['rows' => $rows->count(), 'agrupados' => count($agrupados), 'header' => $header]);

        return $rows;
    }

    /**
     * Convierte un valor numérico string ('NA', '', o número) a null o float.
     */
    private function numericOrNull($value)
    {
        if ($value === null || $value === '' || strtoupper((string) $value) === 'NA') {
            return null;
        }
        $num = filter_var((string) $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if ($num === false || $num === '') {
            return null;
        }
        return (float) str_replace(',', '.', $num);
    }

    /**
     * Convierte fecha Ripley (%d-%m-%Y o formato Excel serial) a Y-m-d.
     */
    private function parseRipleyDate(string $fechaRaw): string
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $fechaRaw)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                $date = Carbon::createFromTimestamp(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $fechaRaw));
                return $date->format('Y-m-d');
            } catch (\Throwable) {
                return $fechaRaw;
            }
        }
    }

    /**
     * Detecta el delimitador CSV analizando el contenido.
     */
    private function detectDelimiter(string $content): string
    {
        $delimiters = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        $firstLine = explode("\n", $content, 2)[0];
        foreach ($delimiters as $d => &$count) {
            $count = substr_count($firstLine, $d);
        }
        unset($count);
        arsort($delimiters);
        $delimiter = key($delimiters);

        return $delimiters[$delimiter] > 0 ? $delimiter : ',';
    }
}