<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
  .container { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.12); }
  .header { background: #b91c1c; color: #fff; padding: 24px 28px; }
  .header h1 { margin: 0; font-size: 20px; }
  .header p  { margin: 4px 0 0; font-size: 13px; opacity: .85; }
  .body { padding: 24px 28px; }
  .alerta { border-left: 4px solid #ccc; padding: 14px 16px; margin-bottom: 16px; border-radius: 0 6px 6px 0; background: #fafafa; }
  .alerta.critico { border-color: #b91c1c; background: #fff1f2; }
  .alerta.warning { border-color: #d97706; background: #fffbeb; }
  .alerta.info    { border-color: #2563eb; background: #eff6ff; }
  .alerta h3 { margin: 0 0 6px; font-size: 15px; }
  .alerta.critico h3 { color: #b91c1c; }
  .alerta.warning h3 { color: #92400e; }
  .alerta.info    h3 { color: #1d4ed8; }
  .alerta p  { margin: 0; font-size: 13px; color: #555; line-height: 1.5; }
  .badge { display: inline-block; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 12px; margin-right: 6px; text-transform: uppercase; }
  .badge.critico { background: #b91c1c; color: #fff; }
  .badge.warning { background: #d97706; color: #fff; }
  .badge.info    { background: #2563eb; color: #fff; }
  .footer { background: #f5f5f5; padding: 14px 28px; font-size: 12px; color: #888; border-top: 1px solid #e5e5e5; }
  .footer a { color: #888; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>⚠️ Alertas de Ventas — Smart Brands</h1>
    <p>{{ $ahora->format('l d/m/Y H:i') }} (Lima)</p>
  </div>
  <div class="body">

    @php
      $criticos = array_filter($alertas, fn($a) => $a['nivel'] === 'critico');
      $resto    = array_filter($alertas, fn($a) => $a['nivel'] !== 'critico');
    @endphp

    @if(count($criticos))
      <p style="margin:0 0 16px;font-size:14px;color:#b91c1c;font-weight:bold;">
        {{ count($criticos) }} alerta(s) CRÍTICA(S) — requieren atención inmediata
      </p>
    @endif

    @foreach($alertas as $alerta)
    <div class="alerta {{ $alerta['nivel'] }}">
      <h3>
        <span class="badge {{ $alerta['nivel'] }}">{{ $alerta['nivel'] }}</span>
        {{ $alerta['titulo'] }}
      </h3>
      <p>{{ $alerta['detalle'] }}</p>
    </div>
    @endforeach

    <hr style="border:none;border-top:1px solid #e5e5e5;margin:20px 0;">
    <p style="font-size:12px;color:#888;margin:0;">
      Total de alertas: {{ count($alertas) }}.
      Este email fue generado automáticamente por el monitor de ventas de gerencial.test.
    </p>
  </div>
  <div class="footer">
    Smart Brands S.A.C. &mdash; Sistema Gerencial &mdash;
    <a href="http://gerencial.test">gerencial.test</a>
  </div>
</div>
</body>
</html>
