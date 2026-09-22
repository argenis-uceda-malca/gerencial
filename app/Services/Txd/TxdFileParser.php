<?php

namespace App\Services\Txd;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use Carbon\Carbon;

class TxdFileParser
{
    const MAP_OECHSLE = [
        'periodo' => 'fecha',
        'cod_oechsle' => 'sku_txd',
        'descripcion_producto' => 'desc_sku',
        'marca' => 'marca',
        'cod_local' => 'cod_local',
        'descripcion_local' => 'desc_local',
        'vta_periodo_s' => 'vta_act',
        'vta_periodo_unid' => 'vta_unds',
        'inventario_s' => 'stk_soles',
        'inventario_unid' => 'stk_unds',
    ];

    const MAP_RIPLEY = [
        'fecha' => 'fecha',
        'marca' => 'marca',
        'temporada' => 'temporada',
        'sucursal' => 'sucursal',
        'suma de costo venta actual' => 'costo_vta',
        'codigo modelo' => 'codigo_modelo',
        'nombre modelo' => 'nombre_modelo',
        'suma de rebates actual' => 'rebate_act',
        'codigo variacion' => 'sku_txd',
        'nombre variacion' => 'desc_sku',
        'suma de venta s/.' => 'vta_soles',
        'suma de venta unid.' => 'vta_unds',
        'suma de contr. s/.' => 'contr',
        'suma de stock s/.' => 'stock_soles',
        'suma de stock und.' => 'stock_unds',
    ];

    const MAP_FB_STOCK = [
        'sku gsc' => 'sku_txd',
        'sku_gsc' => 'sku_txd',
        'sku' => 'sku_txd',
        'descripción' => 'desc_hijo_txd',
        'descripcion' => 'desc_hijo_txd',
        'description' => 'desc_hijo_txd',
        'temporada' => 'temporada',
        'stock disponible' => 'inv_unds_act',
        'stock_disponible' => 'inv_unds_act',
        'marca' => 'marca',
    ];

    const MAP_FB_VENTAS = [
        'falabella sku' => 'sku',
        'sku' => 'sku',
        'descripción' => 'desc_sku',
        'descripcion' => 'desc_sku',
        'desc_sku' => 'desc_sku',
        'paid price' => 'precio',
        'paid_price' => 'precio',
        'precio' => 'precio',
        'created at' => 'created_at',
        'created_at' => 'created_at',
        'marca' => 'marca',
    ];

    public function parseOechsle($file): Collection
    {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        $enc = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($enc && $enc !== 'UTF-8') $content = mb_convert_encoding($content, 'UTF-8', $enc);
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_oech_' . uniqid() . '.' . $ext;
        file_put_contents($tmpPath, $content);
        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);
        $rows = new Collection();
        $mapCol = null;
        foreach ($reader->getSheetIterator()->current()->getRowIterator() as $row) {
            $vals = $row->toArray();
            if ($mapCol === null) {
                $mapCol = [];
                foreach ($vals as $i => $col) {
                    $k = preg_replace('/\s+/', ' ', strtolower(trim((string) $col)));
                    if (isset(self::MAP_OECHSLE[$k])) $mapCol[$i] = self::MAP_OECHSLE[$k];
                    else { $k2 = str_replace(' ', '_', $k); if (isset(self::MAP_OECHSLE[$k2])) $mapCol[$i] = self::MAP_OECHSLE[$k2]; }
                }
                continue;
            }
            if (empty(array_filter($vals, function ($c) { return trim((string) $c) !== ''; }))) continue;
            $data = [];
            foreach ($mapCol as $i => $dbCol) {
                $val = $vals[$i] ?? null;
                if ($val instanceof \DateTimeInterface) $val = $val->format('Y-m-d');
                $val = is_string($val) ? trim($val) : $val;
                if ($dbCol === 'fecha' && $val) { $p = explode(' al ', (string) $val); $val = date('Y-m-d', strtotime(str_replace('/', '-', trim($p[0])))); }
                if ($val === '' || $val === null || strtoupper((string) $val) === 'NA') $data[$dbCol] = null;
                else $data[$dbCol] = $val;
            }
            if (empty(array_filter($data, function ($v) { return $v !== null && $v !== ''; }))) continue;
            foreach (['vta_act','vta_unds','stk_soles','stk_unds'] as $c) if (array_key_exists($c, $data)) $data[$c] = $this->numericOrNull($data[$c]);
            $rows->push((object) array_merge(['fecha'=>null,'sku_txd'=>null,'desc_sku'=>null,'marca'=>null,'cod_local'=>null,'desc_local'=>null,'vta_act'=>null,'vta_unds'=>null,'stk_soles'=>null,'stk_unds'=>null], $data));
        }
        $reader->close(); @unlink($tmpPath);
        Log::info('[TxdFileParser] Oechsle parseado', ['rows' => $rows->count()]);
        return $rows;
    }

    public function parseRipley($file): Collection
    {
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_ripley_' . uniqid() . '.' . $ext;
        copy($file->getRealPath(), $tmpPath);
        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);
        $sheet = null;
        foreach ($reader->getSheetIterator() as $s) if (strtolower(trim($s->getName())) === 'td1') { $sheet = $s; break; }
        if (!$sheet) $sheet = $reader->getSheetIterator()->current();
        $rows = new Collection();
        $mapCol = null;
        foreach ($sheet->getRowIterator() as $row) {
            $vals = $row->toArray();
            if ($mapCol === null) {
                $mapCol = [];
                foreach ($vals as $i => $col) { $k = preg_replace('/\s+/', ' ', strtolower(trim((string) $col))); if (isset(self::MAP_RIPLEY[$k])) $mapCol[$i] = self::MAP_RIPLEY[$k]; }
                continue;
            }
            if (empty(array_filter($vals, function ($c) { return trim((string) $c) !== ''; }))) continue;
            $data = [];
            foreach ($mapCol as $i => $dbCol) {
                $val = $vals[$i] ?? null;
                if ($dbCol === 'fecha' && $val !== null && $val !== '') {
                    if ($val instanceof \DateTimeInterface) $val = $val->format('Y-m-d');
                    elseif (is_numeric($val)) $val = date('Y-m-d', ($val - 25569) * 86400);
                    else { $ts = strtotime(str_replace('/', '-', (string) $val)); $val = $ts ? date('Y-m-d', $ts) : $this->parseRipleyDate((string) $val); }
                } elseif ($val instanceof \DateTimeInterface) $val = $val->format('Y-m-d');
                else $val = is_string($val) ? trim($val) : $val;
                $data[$dbCol] = ($val !== null && $val !== '') ? $val : null;
            }
            if (isset($data['codigo_modelo'])) $data['codigo_modelo'] = (string) $data['codigo_modelo'];
            foreach (['costo_vta','rebate_act','vta_soles','vta_unds','contr','stock_soles','stock_unds'] as $c) if (array_key_exists($c, $data)) $data[$c] = $this->numericOrNull($data[$c]);
            $rows->push((object) array_merge(['fecha'=>null,'marca'=>null,'temporada'=>null,'sucursal'=>null,'costo_vta'=>null,'codigo_modelo'=>'','nombre_modelo'=>null,'rebate_act'=>null,'sku_txd'=>null,'desc_sku'=>null,'vta_soles'=>null,'vta_unds'=>null,'contr'=>null,'stock_soles'=>null,'stock_unds'=>null], $data));
        }
        $reader->close(); @unlink($tmpPath);
        Log::info('[TxdFileParser] Ripley parseado', ['rows' => $rows->count()]);
        return $rows;
    }

    public function parseFalabellaStock($file): Collection
    {
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_fbstock_' . uniqid() . '.' . $ext;
        copy($file->getRealPath(), $tmpPath);
        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);
        $sheet = null;
        foreach ($reader->getSheetIterator() as $s) if (strtolower(trim($s->getName())) === 'product details') { $sheet = $s; break; }
        if (!$sheet) $sheet = $reader->getSheetIterator()->current();
        $agrupados = [];
        $mapCol = null;
        foreach ($sheet->getRowIterator() as $row) {
            $vals = $row->toArray();
            if ($mapCol === null) {
                $mapCol = [];
                foreach ($vals as $i => $col) { $k = preg_replace('/\s+/', ' ', strtolower(trim((string) $col))); if (isset(self::MAP_FB_STOCK[$k])) $mapCol[$i] = self::MAP_FB_STOCK[$k]; }
                continue;
            }
            if (empty(array_filter($vals, function ($c) { return trim((string) $c) !== ''; }))) continue;
            $tmp = [];
            foreach ($mapCol as $i => $dbCol) { $v = $vals[$i] ?? null; if ($v instanceof \DateTimeInterface) $v = $v->format('Y-m-d'); $tmp[$dbCol] = is_string($v) ? trim($v) : $v; }
            $sku = $tmp['sku_txd'] ?? ''; $desc = $tmp['desc_hijo_txd'] ?? '';
            if (!$sku && !$desc) continue;
            $stockRaw = $tmp['inv_unds_act'] ?? 0;
            if (!is_numeric($stockRaw)) { $f = filter_var((string) $stockRaw, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION); $stockRaw = $f !== false && $f !== '' ? (int) $f : 0; }
            $key = $sku.'|'.$desc;
            if (!isset($agrupados[$key])) $agrupados[$key] = ['sku_txd'=>$sku,'desc_hijo_txd'=>$desc,'sucursal'=>'Tienda Virtual','temporada'=>$tmp['temporada']??null,'inv_unds_act'=>0,'marca'=>$tmp['marca']??null];
            $agrupados[$key]['inv_unds_act'] += (int)$stockRaw;
        }
        $reader->close(); @unlink($tmpPath);
        $rows = new Collection();
        foreach ($agrupados as $r) $rows->push((object) $r);
        Log::info('[TxdFileParser] Falabella Stock parseado', ['rows' => $rows->count(), 'agrupados'=>count($agrupados)]);
        return $rows;
    }

    public function parseFalabellaVentas($file): Collection
    {
        $ext = $file->extension();
        $tmpPath = sys_get_temp_dir() . '/txd_fbventas_' . uniqid() . '.' . $ext;
        copy($file->getRealPath(), $tmpPath);
        $reader = ReaderFactory::createFromFile($tmpPath);
        $reader->open($tmpPath);
        $sheet = null;
        foreach ($reader->getSheetIterator() as $s) if (strtolower(trim($s->getName())) === 'sheet 1') { $sheet = $s; break; }
        if (!$sheet) $sheet = $reader->getSheetIterator()->current();
        $mapCol = null; $agrupados = [];
        foreach ($sheet->getRowIterator() as $row) {
            $vals = $row->toArray();
            if ($mapCol === null) {
                $mapCol = [];
                foreach ($vals as $i => $col) { $k = preg_replace('/\s+/', ' ', strtolower(trim((string) $col))); if (isset(self::MAP_FB_VENTAS[$k])) $mapCol[$i] = self::MAP_FB_VENTAS[$k]; }
                continue;
            }
            if (empty(array_filter($vals, function ($c) { return trim((string) $c) !== ''; }))) continue;
            $tmp = [];
            foreach ($mapCol as $i => $dbCol) { $v = $vals[$i] ?? null; if ($v instanceof \DateTimeInterface) $v = $v->format('Y-m-d H:i:s'); $tmp[$dbCol] = is_string($v) ? trim($v) : $v; }
            $sku = $tmp['sku'] ?? ''; $desc = $tmp['desc_sku'] ?? ''; $precio = (float)($tmp['precio'] ?? 0); $createdAt = $tmp['created_at'] ?? '';
            if (!$sku && !$desc) continue;
            // Usar la fecha real del archivo (created_at = fecha de la venta en Seller Center)
            try {
                $fechaCarbon = Carbon::parse($createdAt);
            } catch (\Throwable $e) {
                try { $fechaCarbon = Carbon::createFromFormat('M d, Y H:i', $createdAt); }
                catch (\Throwable $e2) { Log::warning('[TxdFileParser] No se pudo parsear fecha FB', ['date'=>$createdAt]); continue; }
            }
            $fechaStr = $fechaCarbon->format('Y-m-d');
            $key = $sku . '|' . $desc . '|' . $fechaStr;
            if (!isset($agrupados[$key])) {
                $agrupados[$key] = ['sku'=>$sku,'desc_sku'=>$desc,'sucursal'=>'Tienda Virtual','fecha'=>$fechaStr,'vta_unds'=>0,'vta_soles'=>0,'marca'=>$tmp['marca'] ?? ''];
            }
            $agrupados[$key]['vta_unds'] += 1;
            $agrupados[$key]['vta_soles'] += round($precio, 2);
            if (!empty($tmp['marca'])) $agrupados[$key]['marca'] = $tmp['marca'];
        }
        $reader->close(); @unlink($tmpPath);
        $rows = new Collection();
        foreach ($agrupados as $r) $rows->push((object) $r);
        Log::info('[TxdFileParser] Falabella Ventas parseado', ['rows'=>$rows->count(),'agrupados'=>count($agrupados)]);
        return $rows;
    }

    private function numericOrNull($value)
    {
        if ($value === null || $value === '' || strtoupper((string) $value) === 'NA') return null;
        $num = filter_var((string) $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        if ($num === false || $num === '') return null;
        return (float) str_replace(',', '.', $num);
    }

    private function parseRipleyDate(string $fechaRaw): string
    {
        try { return Carbon::createFromFormat('d-m-Y', $fechaRaw)->format('Y-m-d'); } catch (\Throwable $e) {
            try { $d = Carbon::createFromTimestamp(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $fechaRaw)); return $d->format('Y-m-d'); } catch (\Throwable $e) { return $fechaRaw; }
        }
    }

    private function detectDelimiter(string $content): string
    {
        $firstLine = explode("\n", $content, 2)[0];
        $delims = [','=>0,';'=>0,"\t"=>0,'|'=>0];
        foreach ($delims as $d=>&$c) $c = substr_count($firstLine, $d);
        unset($c); arsort($delims);
        return ($delims[key($delims)] ?? 0) > 0 ? key($delims) : ',';
    }
}
