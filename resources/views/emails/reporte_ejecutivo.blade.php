@php
    $svc  = app(\App\Services\ReporteEjecutivoService::class);
    $t    = $an['total'];
    $d    = $svc->derivados($t);
    $fin  = \Carbon\Carbon::parse($an['fin']);
    $ini  = \Carbon\Carbon::parse($an['ini']);
    $pc   = function ($v, $signo = false) { return $v === null ? '-' : (($signo && $v > 0 ? '+' : '') . number_format($v * 100, 1) . '%'); };
@endphp
<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#1f2a44;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;">
<tr><td align="center" style="padding:20px 10px;">
  <table width="680" cellpadding="0" cellspacing="0" style="max-width:680px;background:#ffffff;border-radius:8px;">
    <tr><td style="padding:24px 28px 8px 28px;">
      <p style="margin:0 0 12px 0;font-size:15px;">Buenos días,</p>
      <p style="margin:0 0 14px 0;font-size:14px;line-height:1.5;">
        Resumen de ventas {{ $fin->format('Y') }} del <b>{{ $ini->format('d/m/Y') }}</b> al <b>{{ $fin->format('d/m/Y') }}</b>.
        Adjunto el Excel con el detalle por tienda, por mes y por día.
      </p>
      <ul style="margin:0 0 6px 18px;padding:0;font-size:14px;line-height:1.7;">
        <li>Venta neta {{ $fin->format('Y') }}: <b>{{ $svc->fmtM($t['vta26']) }}</b></li>
        <li>Venta neta {{ $fin->copy()->subYear()->format('Y') }} (día equivalente): <b>{{ $svc->fmtM($t['vta25']) }}</b> &nbsp;({{ $pc($d[1], true) }})</li>
        <li>Meta: <b>{{ $svc->fmtM($t['meta']) }}</b> &nbsp;(cumplimiento {{ $pc($d[0]) }})</li>
        <li>Unidades: <b>{{ number_format($t['unds26']) }}</b> &nbsp;|&nbsp; Tickets: <b>{{ number_format($t['tickets26']) }}</b> &nbsp;|&nbsp; GM: <b>{{ $pc($d[2]) }}</b></li>
        <li>Tiendas que cumplen su meta: <b>{{ $an['tiendas_cumplen'] }}</b> de {{ $an['tiendas_con_meta'] }}</li>
      </ul>
    </td></tr>
    <tr><td style="padding:8px 28px;"><img src="{{ $message->embed($imgs['kpi']) }}" width="624" style="width:100%;max-width:624px;height:auto;display:block;border:0;" alt="Resumen"></td></tr>
    <tr><td style="padding:8px 28px;"><img src="{{ $message->embed($imgs['meses']) }}" width="624" style="width:100%;max-width:624px;height:auto;display:block;border:0;" alt="Venta por mes"></td></tr>
    <tr><td style="padding:8px 28px;"><img src="{{ $message->embed($imgs['top']) }}" width="624" style="width:100%;max-width:624px;height:auto;display:block;border:0;" alt="Top tiendas"></td></tr>
    <tr><td style="padding:8px 28px;"><img src="{{ $message->embed($imgs['canal']) }}" width="624" style="width:100%;max-width:624px;height:auto;display:block;border:0;" alt="Por canal"></td></tr>
    <tr><td style="padding:12px 28px 24px 28px;font-size:12px;color:#667085;line-height:1.5;">
      Cifras en soles. La comparación con el año anterior es por día equivalente. La meta se toma hasta la fecha de corte.<br>
      Reporte generado automáticamente por Smart Brands Gerencial el {{ now()->format('d/m/Y H:i') }}.
    </td></tr>
  </table>
</td></tr>
</table>
</body>
</html>
