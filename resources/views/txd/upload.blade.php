@extends('layouts.base')
@section('title', 'Carga TXD — Smart Brands')

@push('styles')
<style>
.txd-page{--txd-radius:16px;--txd-border:var(--dm-border);--txd-card:var(--dm-card-bg);--txd-muted:var(--dm-muted)}
.txd-header{display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;margin-bottom:18px}
.txd-header h1{font-size:clamp(18px,3vw,22px);font-weight:700;letter-spacing:-.02em;margin:0;display:flex;align-items:center;gap:10px;color:var(--dm-ink)}
.txd-header h1 .ico{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:#696cff;color:#fff;font-size:19px;flex-shrink:0}
.txd-header p{margin:2px 0 0 48px;color:var(--dm-muted);font-size:13px;line-height:1.4}
@media(max-width:576px){.txd-header p{margin-left:0}}
.txd-summary{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.txd-badge{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:6px 10px;border-radius:999px;background:var(--dm-surface-alt);border:1px solid var(--dm-border);color:var(--dm-muted)}
.txd-badge b{color:#696cff}
.txd-counter{font-size:12px;color:var(--dm-muted)}
.txd-grid{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:576px){.txd-grid{grid-template-columns:1fr 1fr}}
@media(min-width:1200px){.txd-grid{grid-template-columns:1fr 1fr 1fr}}
.txd-card{position:relative;background:var(--txd-card);border:1px solid var(--txd-border);border-radius:var(--txd-radius);overflow:hidden;transition:transform .18s,box-shadow .18s,border-color .18s}
.txd-card:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(30,42,58,.07);border-color:#d9deef}
[data-theme="dark"] .txd-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.25);border-color:#3a4060}
.txd-card.has-file{border-color:#696cff;box-shadow:0 4px 18px rgba(105,108,255,.14)}
.txd-card.drag-over{border-color:#696cff;background:#f5f5ff}
[data-theme="dark"] .txd-card.drag-over{background:#252a4a}
.txd-card.uploading{pointer-events:none;opacity:.92}
.txd-card-head{padding:14px 14px 10px;display:flex;gap:10px;align-items:flex-start}
.txd-logo{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;font-size:18px;flex-shrink:0;color:#fff}
.txd-logo.oe{background:linear-gradient(135deg,#ff6b6b,#ee5a24)}.txd-logo.ri{background:linear-gradient(135deg,#feca57,#ff9ff3);color:#1e2a3a}.txd-logo.fb{background:linear-gradient(135deg,#54a0ff,#5f27cd)}
.txd-meta{flex:1;min-width:0}.txd-meta h3{font-size:13.5px;font-weight:700;margin:0;line-height:1.2;color:var(--dm-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.txd-meta .sub{font-size:11.5px;color:var(--dm-muted);margin-top:2px;line-height:1.3}
.txd-pill{font-size:10px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;padding:4px 7px;border-radius:999px;white-space:nowrap}
.txd-pill.csv{background:#e8f8f0;color:#1a7a4a;border:1px solid #c6eedc}.txd-pill.xlsx{background:#eef2ff;color:#4a4ac4;border:1px solid #d8dcff}
.txd-drop{margin:0 12px 12px;border:1.7px dashed #d6dbe9;border-radius:12px;padding:16px 12px;text-align:center;cursor:pointer;transition:all .18s;background:var(--dm-surface-alt);position:relative}
[data-theme="dark"] .txd-drop{border-color:#3a4060;background:#21263d}
.txd-card.has-file .txd-drop{border-style:solid;border-color:#c7ccff;background:#f6f7ff}
[data-theme="dark"] .txd-card.has-file .txd-drop{background:#252a4a;border-color:#5a60d0}
.txd-drop:hover{border-color:#696cff;background:#f0f0ff}
[data-theme="dark"] .txd-drop:hover{background:#2a3050}
.txd-drop .dz-icon{width:42px;height:42px;border-radius:10px;display:grid;place-items:center;margin:0 auto 8px;background:#fff;border:1px solid var(--dm-border);color:#696cff;font-size:20px}
[data-theme="dark"] .txd-drop .dz-icon{background:#2e3450;border-color:#3a4060}
.txd-drop .dz-title{font-size:13px;font-weight:600;color:var(--dm-ink)}.txd-drop .dz-hint{font-size:11.5px;color:var(--dm-muted);margin-top:2px}
.txd-drop input{position:absolute;inset:0;opacity:0;cursor:pointer}
.txd-filepill{display:none;align-items:center;gap:8px;margin:0 12px 12px;padding:9px 10px;border-radius:10px;background:#eef0ff;border:1px solid #d8dcff}
[data-theme="dark"] .txd-filepill{background:#2a3050;border-color:#3a4060}
.txd-card.has-file .txd-filepill{display:flex}
.txd-filepill .fi{width:32px;height:32px;border-radius:8px;display:grid;place-items:center;background:#696cff;color:#fff;font-size:16px;flex-shrink:0}
.txd-filepill .fn{flex:1;min-width:0}.txd-filepill .fn strong{display:block;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--dm-ink)}.txd-filepill .fn span{font-size:11px;color:var(--dm-muted)}
.txd-filepill .rm{width:28px;height:28px;border-radius:8px;border:none;display:grid;place-items:center;background:#fff;border:1px solid var(--dm-border);color:#8e9bb4;cursor:pointer;flex-shrink:0}
.txd-filepill .rm:hover{background:#ffe8e8;border-color:#ffc9c9;color:#d33}
.txd-opts{padding:10px 12px 0;font-size:11px;color:var(--dm-muted)}
.txd-pipeline{background:var(--txd-card);border:1px solid var(--txd-border);border-radius:var(--txd-radius);overflow:hidden}
.txd-pipeline .head{padding:14px 16px;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.txd-pipeline .head h3{font-size:13.5px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;color:var(--dm-ink)}
.txd-pipeline .head p{font-size:12px;color:var(--dm-muted);margin:2px 0 0}
.form-switch .form-check-input{width:2.6em;height:1.45em;cursor:pointer}
.txd-dates{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px 16px}
@media(min-width:768px){.txd-dates{grid-template-columns:1fr 1fr 1fr 1fr}}
.txd-dates .form-control{font-size:13px;padding:7px 10px;border-radius:10px}
.txd-dates label{font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:var(--dm-muted);margin-bottom:4px}
.collapse-txd{overflow:hidden;transition:all .25s}
.txd-actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:14px 0 6px}
.txd-actions .btn{border-radius:10px;padding:10px 18px;font-weight:600;font-size:14px}
.txd-actions .hint{font-size:12px;color:var(--dm-muted)}
#txd-overlay{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(20,24,36,.55);backdrop-filter:blur(6px)}
#txd-overlay.show{display:flex}
.txd-modal{width:min(520px,100%);background:var(--dm-card-bg);border:1px solid var(--dm-border);border-radius:18px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:pop .22s ease}
@keyframes pop{from{transform:translateY(8px) scale(.98);opacity:0}to{transform:none;opacity:1}}
.txd-modal-head{padding:18px 20px 14px;display:flex;gap:14px;align-items:center;border-bottom:1px solid var(--dm-border)}
.txd-spinner{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:#eef0ff;color:#696cff;flex-shrink:0}
.txd-spinner .spinner-border{width:22px;height:22px;border-width:2.5px}
.txd-modal-head h4{margin:0;font-size:15px;font-weight:700;color:var(--dm-ink)}.txd-modal-head p{margin:2px 0 0;font-size:12.5px;color:var(--dm-muted)}
.txd-progress{height:8px;background:var(--dm-surface-alt);border-radius:999px;overflow:hidden;margin:14px 20px 8px}
.txd-progress i{display:block;height:100%;width:0%;background:linear-gradient(90deg,#696cff,#8b83ff);border-radius:999px;transition:width .25s}
.txd-steps{display:flex;gap:6px;padding:0 20px 6px;flex-wrap:wrap}
.txd-steps span{font-size:10.5px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;padding:4px 8px;border-radius:999px;background:var(--dm-surface-alt);border:1px solid var(--dm-border);color:var(--dm-muted)}
.txd-steps span.on{background:#696cff;color:#fff;border-color:#696cff}
.txd-filelist{padding:8px 20px 16px;display:grid;gap:6px;max-height:180px;overflow:auto}
.txd-filelist div{display:flex;align-items:center;gap:8px;font-size:12.5px;padding:7px 9px;border-radius:9px;background:var(--dm-surface-alt);border:1px solid var(--dm-border)}
.txd-filelist div b{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.txd-filelist .ok{color:#1a7a4a}.txd-filelist .wait{color:var(--dm-muted)}
@media(max-width:576px){.txd-actions{position:sticky;bottom:0;background:var(--dm-card-bg);border-top:1px solid var(--dm-border);margin:0 -12px;padding:12px;z-index:2;border-radius:12px 12px 0 0;box-shadow:0 -8px 24px rgba(0,0,0,.06)} .container.py-4{padding-bottom:6px !important}}
</style>
@endpush

@section('contenido')
<div class="container py-3 py-md-4 txd-page">
    <div class="txd-header">
        <div>
            <h1><span class="ico"><i class="bx bx-cloud-upload"></i></span> Carga TXD</h1>
            <p>Sube <b>Oechsle, Ripley y Falabella</b> en cualquier combinación. Arrastra los archivos o haz clic en cada tarjeta.</p>
        </div>
        <div class="txd-summary">
            <span class="txd-badge"><b id="txd-count">0</b> / 5 archivos</span>
            <span class="txd-counter" id="txd-size">0 MB</span>
            <a href="{{ route('reportetxd') }}" class="btn btn-sm btn-outline-primary" style="border-radius:999px"><i class="bx bx-bar-chart-alt-2"></i> Ver reporte</a>
        </div>
    </div>

    <div id="txd-alerts"></div>

    <form id="form-txd-upload" method="POST" action="{{ route('txd.upload.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="txd-grid mb-3">
            <div class="txd-card" data-input="oechsle_venta">
                <div class="txd-card-head">
                    <div class="txd-logo oe"><i class="bx bx-store"></i></div>
                    <div class="txd-meta"><h3>Oechsle — Venta</h3><div class="sub">Lun a Sáb · ventas del día</div></div>
                    <span class="txd-pill csv">CSV</span>
                </div>
                <label class="txd-drop">
                    <div class="dz-icon"><i class="bx bx-file"></i></div>
                    <div class="dz-title">Arrastra tu CSV aquí</div>
                    <div class="dz-hint">o haz clic para buscar · .csv, .txt</div>
                    <input type="file" name="oechsle_venta" accept=".csv,.txt">
                </label>
                <div class="txd-filepill"><div class="fi"><i class="bx bx-check"></i></div><div class="fn"><strong class="fname"></strong><span class="fsize"></span></div><button type="button" class="rm" title="Quitar"><i class="bx bx-x"></i></button></div>
                <div class="txd-opts"><i class="bx bx-info-circle"></i> Hoja única · separador coma</div>
            </div>

            <div class="txd-card" data-input="oechsle_stock">
                <div class="txd-card-head">
                    <div class="txd-logo oe" style="filter:hue-rotate(18deg)"><i class="bx bx-package"></i></div>
                    <div class="txd-meta"><h3>Oechsle — Stock</h3><div class="sub">Domingo · inventario</div></div>
                    <span class="txd-pill csv">CSV</span>
                </div>
                <label class="txd-drop">
                    <div class="dz-icon"><i class="bx bx-cube"></i></div>
                    <div class="dz-title">Arrastra tu CSV de stock</div>
                    <div class="dz-hint">o haz clic para buscar · .csv, .txt</div>
                    <input type="file" name="oechsle_stock" accept=".csv,.txt">
                </label>
                <div class="txd-filepill"><div class="fi"><i class="bx bx-check"></i></div><div class="fn"><strong class="fname"></strong><span class="fsize"></span></div><button type="button" class="rm"><i class="bx bx-x"></i></button></div>
                <div class="txd-opts"><i class="bx bx-info-circle"></i> Se combina con Venta si subes ambos</div>
            </div>

            <div class="txd-card" data-input="ripley">
                <div class="txd-card-head">
                    <div class="txd-logo ri"><i class="bx bx-shopping-bag"></i></div>
                    <div class="txd-meta"><h3>Ripley</h3><div class="sub">Lun a Dom · hoja “TD1”</div></div>
                    <span class="txd-pill xlsx">XLSX</span>
                </div>
                <label class="txd-drop">
                    <div class="dz-icon"><i class="bx bx-spreadsheet"></i></div>
                    <div class="dz-title">Arrastra tu Excel Ripley</div>
                    <div class="dz-hint">o haz clic para buscar · .xlsx, .xls</div>
                    <input type="file" name="ripley" accept=".xlsx,.xls">
                </label>
                <div class="txd-filepill"><div class="fi"><i class="bx bx-check"></i></div><div class="fn"><strong class="fname"></strong><span class="fsize"></span></div><button type="button" class="rm"><i class="bx bx-x"></i></button></div>
                <div class="txd-opts"><i class="bx bx-info-circle"></i> Usa la hoja TD1 · no renombres columnas</div>
            </div>

            <div class="txd-card" data-input="falabella_stock">
                <div class="txd-card-head">
                    <div class="txd-logo fb"><i class="bx bx-cabinet"></i></div>
                    <div class="txd-meta"><h3>Falabella — Stock</h3><div class="sub">Hoja “Product details”</div></div>
                    <span class="txd-pill xlsx">XLSX</span>
                </div>
                <label class="txd-drop">
                    <div class="dz-icon"><i class="bx bx-layer"></i></div>
                    <div class="dz-title">Arrastra Stock Falabella</div>
                    <div class="dz-hint">o haz clic para buscar · .xlsx, .xls</div>
                    <input type="file" name="falabella_stock" accept=".xlsx,.xls">
                </label>
                <div class="txd-filepill"><div class="fi"><i class="bx bx-check"></i></div><div class="fn"><strong class="fname"></strong><span class="fsize"></span></div><button type="button" class="rm"><i class="bx bx-x"></i></button></div>
                <div class="txd-opts"><i class="bx bx-info-circle"></i> Inventario actual por SKU</div>
            </div>

            <div class="txd-card" data-input="falabella_ventas">
                <div class="txd-card-head">
                    <div class="txd-logo fb" style="filter:hue-rotate(25deg)"><i class="bx bx-receipt"></i></div>
                    <div class="txd-meta"><h3>Falabella — Ventas</h3><div class="sub">Seller Center · “Sheet 1”</div></div>
                    <span class="txd-pill xlsx">XLSX</span>
                </div>
                <label class="txd-drop">
                    <div class="dz-icon"><i class="bx bx-cart"></i></div>
                    <div class="dz-title">Arrastra Ventas Falabella</div>
                    <div class="dz-hint">o haz clic para buscar · .xlsx, .xls</div>
                    <input type="file" name="falabella_ventas" accept=".xlsx,.xls">
                </label>
                <div class="txd-filepill"><div class="fi"><i class="bx bx-check"></i></div><div class="fn"><strong class="fname"></strong><span class="fsize"></span></div><button type="button" class="rm"><i class="bx bx-x"></i></button></div>
                <div class="txd-opts"><i class="bx bx-info-circle"></i> Exportado de Seller Center</div>
            </div>
        </div>

        <div class="txd-pipeline mb-3">
            <div class="head">
                <div>
                    <h3><i class="bx bx-cog" style="color:#696cff"></i> Pipeline automático <span class="badge bg-label-primary" style="font-size:10px">Opcional</span></h3>
                    <p>Ejecuta <code>automatizacion_ejecutar_txd_completo()</code> al terminar la carga</p>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" name="ejecutar_pipeline" value="1" id="sw-pipeline">
                    <label class="form-check-label fw-semibold" for="sw-pipeline" style="font-size:13px">Activar</label>
                </div>
            </div>
            <div id="pipeline-fields" class="collapse-txd" style="display:none">
                <div class="txd-dates">
                    <div><label>Fecha inicio</label><input type="date" name="p_fecha_ini" class="form-control"></div>
                    <div><label>Fecha fin</label><input type="date" name="p_fecha_fin" class="form-control"></div>
                    <div><label>Fecha stock</label><input type="date" name="p_fecha_stock" class="form-control"></div>
                    <div><label>Lunes de la semana</label><input type="date" name="p_fecha_lunes" class="form-control"></div>
                </div>
                <div class="px-3 pb-3"><small class="text-muted"><i class="bx bx-bulb"></i> Deja vacío para usar los defaults del SP.</small></div>
            </div>
        </div>

        <div class="txd-actions">
            <button type="submit" class="btn btn-primary" id="btn-txd-submit"><i class="bx bx-upload"></i> Cargar archivos</button>
            <button type="button" class="btn btn-outline-secondary" id="btn-clear"><i class="bx bx-trash"></i> Limpiar</button>
            <span class="hint" id="txd-hint">Selecciona al menos 1 archivo para habilitar el envío.</span>
        </div>
    </form>

    <div class="txd-pipeline mt-4">
        <div class="head">
            <div>
                <h3><i class="bx bx-play-circle" style="color:#28a745"></i> Ejecutar pipeline con lo ya cargado</h3>
                <p>Si ya subiste archivos sin marcar el switch, ejecútalo aquí sin volver a subir</p>
            </div>
        </div>
        <form id="form-txd-pipeline" method="POST" action="{{ route('txd.pipeline') }}">
            @csrf
            <div class="txd-dates">
                <div><label>Fecha inicio</label><input type="date" name="p_fecha_ini" class="form-control" id="pl-p_fecha_ini"></div>
                <div><label>Fecha fin</label><input type="date" name="p_fecha_fin" class="form-control" id="pl-p_fecha_fin"></div>
                <div><label>Fecha stock</label><input type="date" name="p_fecha_stock" class="form-control" id="pl-p_fecha_stock"></div>
                <div><label>Lunes de la semana</label><input type="date" name="p_fecha_lunes" class="form-control" id="pl-p_fecha_lunes"></div>
            </div>
            <div class="px-3 pb-3 d-flex gap-2">
                <button type="submit" class="btn btn-success" id="btn-pipeline"><i class="bx bx-cog"></i> Ejecutar pipeline</button>
                <small class="text-muted align-self-center">Usa las mismas fechas del bloque superior o deja vacío.</small>
            </div>
        </form>
    </div>
</div>

<div id="txd-overlay" aria-hidden="true">
    <div class="txd-modal">
        <div class="txd-modal-head">
            <div class="txd-spinner"><div class="spinner-border text-primary" role="status"></div></div>
            <div style="flex:1;min-width:0">
                <h4 id="txd-ov-title">Cargando archivos…</h4>
                <p id="txd-ov-sub">No cierres esta ventana</p>
            </div>
            <span class="badge bg-primary" id="txd-pct">0%</span>
        </div>
        <div class="txd-progress"><i id="txd-bar"></i></div>
        <div class="txd-steps">
            <span id="st-1" class="on">Validando</span><span id="st-2">Subiendo</span><span id="st-3">Procesando</span><span id="st-4">Listo</span>
        </div>
        <div class="txd-filelist" id="txd-ov-list"></div>
    </div>
</div>

<script>
$(function(){
    const $form=$('#form-txd-upload'), $btn=$('#btn-txd-submit'), $alerts=$('#txd-alerts');
    const $count=$('#txd-count'), $size=$('#txd-size'), $hint=$('#txd-hint'), $bar=$('#txd-bar'), $pct=$('#txd-pct');
    const $overlay=$('#txd-overlay'), $ovTitle=$('#txd-ov-title'), $ovSub=$('#txd-ov-sub'), $ovList=$('#txd-ov-list');

    function fmt(n){if(n<1024) return n+' B'; if(n<1048576) return (n/1024).toFixed(1)+' KB'; return (n/1048576).toFixed(2)+' MB'}
    function totalStats(){
        let c=0,s=0, names=[];
        $('.txd-card').each(function(){
            const f=$(this).find('input[type=file]')[0].files[0];
            if(f){c++; s+=f.size; names.push({name:f.name,size:f.size, card:$(this).data('input')})}
        });
        $count.text(c); $size.text(fmt(s));
        $btn.prop('disabled', c===0);
        $hint.text(c===0 ? 'Selecciona al menos 1 archivo para habilitar el envío.' : c+' archivo(s) listo(s) · '+fmt(s));
        return {c,s,names};
    }
    function setFile(card, file){
        const $card=$(card), $pill=$card.find('.txd-filepill');
        if(file){ $card.addClass('has-file'); $pill.find('.fname').text(file.name); $pill.find('.fsize').text(fmt(file.size)+' · listo'); }
        else { $card.removeClass('has-file'); $card.find('input').val(''); }
        totalStats();
    }

    $('.txd-card').each(function(){
        const card=this, $card=$(card), $input=$card.find('input[type=file]');
        const drop=$card.find('.txd-drop')[0];
        $input.on('change', function(){ const f=this.files[0]||null; if(f && this.accept){
            const ext='.'+f.name.split('.').pop().toLowerCase();
            const acc=(this.accept||'').toLowerCase();
            if(acc && !acc.split(',').some(a=>a.trim()===ext || (a.trim().startsWith('.')?a.trim()===ext:true))){
            }
        } setFile(card, f); });
        $card.find('.rm').on('click', function(e){ e.preventDefault(); e.stopPropagation(); setFile(card,null); });
        ['dragenter','dragover'].forEach(ev=> drop.addEventListener(ev, e=>{ e.preventDefault(); $card.addClass('drag-over'); }));
        ['dragleave','drop'].forEach(ev=> drop.addEventListener(ev, e=>{ if(ev==='drop'){ e.preventDefault(); const f=e.dataTransfer.files[0]; if(f){ const dt=new DataTransfer(); dt.items.add(f); $input[0].files=dt.files; $input.trigger('change'); } } $card.removeClass('drag-over'); }));
    });

    $('#btn-clear').on('click', function(){ $('.txd-card').each(function(){ setFile(this,null); }); $alerts.html(''); totalStats(); });
    $('#sw-pipeline').on('change', function(){ const on=this.checked; $('#pipeline-fields').stop()[on?'slideDown':'slideUp'](180); });
    totalStats();

    function step(n){
        $('.txd-steps span').removeClass('on');
        for(let i=1;i<=n;i++) $('#st-'+i).addClass('on');
    }

    $('#form-txd-pipeline').on('submit', function(e){
        e.preventDefault();
        const $b=$('#btn-pipeline');
        $b.prop('disabled',true).html('<span class="spinner-border spinner-border-sm"></span> Ejecutando…');
        $alerts.html('');
        $.ajax({
            url:$(this).attr('action'), method:'POST', data:$(this).serialize(),
            headers:{'X-CSRF-TOKEN':$('input[name="_token"]').val()},
            success:function(res){ $alerts.html('<div class="alert alert-success py-2"><i class="bx bx-check-circle"></i> '+res.message+'</div>'); if(window.toastr) toastr.success(res.message,'TXD',{progressBar:true}); },
            error:function(xhr){ let m=xhr.responseJSON?.message||'Error pipeline'; $alerts.html('<div class="alert alert-danger py-2">'+m+'</div>'); if(window.toastr) toastr.error(m,'TXD',{progressBar:true}); },
            complete:function(){ $b.prop('disabled',false).html('<i class="bx bx-cog"></i> Ejecutar pipeline'); }
        });
    });

    $form.on('submit', function(e){
        e.preventDefault();
        const st=totalStats();
        if(st.c===0){ $alerts.html('<div class="alert alert-warning py-2"><i class="bx bx-error"></i> Selecciona al menos un archivo.</div>'); return; }
        const fd=new FormData(this);
        $btn.prop('disabled',true).html('<span class="spinner-border spinner-border-sm"></span> Cargando…');
        $alerts.html('');
        $('.txd-card').addClass('uploading');
        $overlay.addClass('show');
        $ovTitle.text('Subiendo '+st.c+' archivo(s)…'); $ovSub.text('Esto puede tardar unos segundos'); $bar.css('width','8%'); $pct.text('8%'); step(2);
        $ovList.html(st.names.map(n=>'<div><i class="bx bx-loader-circle bx-spin wait"></i> <b>'+n.name+'</b> <small>'+fmt(n.size)+'</small></div>').join(''));

        $.ajax({
            url:$form.attr('action'), method:'POST', data:fd, processData:false, contentType:false,
            headers:{'X-CSRF-TOKEN':$('input[name="_token"]').val()},
            xhr:function(){
                const xhr=new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt){
                    if(evt.lengthComputable){
                        let p=Math.round(evt.loaded/evt.total*92)+8;
                        if(p>96) p=96;
                        $bar.css('width',p+'%'); $pct.text(p+'%');
                        $ovSub.text('Subiendo… '+fmt(evt.loaded)+' / '+fmt(evt.total));
                    }
                });
                return xhr;
            },
            success:function(res){
                $bar.css('width','100%'); $pct.text('100%'); step(4);
                $ovTitle.text('¡Carga exitosa!'); $ovSub.text(res.message||res.status||'Procesado correctamente');
                $ovList.html((st.names.map(n=>'<div><i class="bx bx-check-circle ok"></i> <b>'+n.name+'</b> <small>OK</small></div>').join('')) + '<div style="background:#e8f8f0;border-color:#c6eedc;color:#1a7a4a"><i class="bx bx-check-double"></i> <b>'+(res.status||'Completado')+'</b></div>');
                $alerts.html('<div class="alert alert-success d-flex align-items-center gap-2 py-2"><i class="bx bx-check-circle fs-5"></i><div>'+(res.status||res.message||'Archivos procesados correctamente.')+'</div><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>');
                if(window.toastr) toastr.success(res.status||'Carga exitosa','TXD',{progressBar:true});
                setTimeout(()=>{ $overlay.removeClass('show'); $('.txd-card').removeClass('uploading'); },1200);
                if(!$('#sw-pipeline').is(':checked')){ setTimeout(()=>{ $('.txd-card').each(function(){ setFile(this,null); }); },1400); }
            },
            error:function(xhr){
                $bar.css('width','100%'); $pct.text('Error'); $ovTitle.text('Error al procesar'); $ovSub.text('Revisa el mensaje y vuelve a intentar');
                $('.txd-filelist div').css('border-color','#ffc9c9');
                let msg='Error al procesar los archivos.';
                if(xhr.responseJSON && xhr.responseJSON.message) msg=xhr.responseJSON.message;
                else if(xhr.responseJSON && xhr.responseJSON.errors) msg=Object.values(xhr.responseJSON.errors).flat().join('<br>');
                $alerts.html('<div class="alert alert-danger d-flex gap-2 py-2"><i class="bx bx-error-circle fs-5"></i><div>'+msg+'</div><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>');
                if(window.toastr) toastr.error(msg,'TXD',{progressBar:true});
                $ovList.append('<div style="background:#ffe8e8;border-color:#ffc9c9;color:#b42318"><i class="bx bx-error"></i> <b>'+msg+'</b></div>');
                setTimeout(()=> $overlay.removeClass('show'), 2200);
                $('.txd-card').removeClass('uploading');
            },
            complete:function(){ $btn.prop('disabled',false).html('<i class="bx bx-upload"></i> Cargar archivos'); totalStats(); }
        });
    });
});
</script>
@endsection
