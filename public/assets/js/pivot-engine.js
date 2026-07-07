/* ══════════════════════════════════════════════════════════════════════════
   PivotEngine — motor de tabla dinámica (pivot) sin dependencias.
   Uso: PivotEngine.compute(data, { rows, cols, values, collapsed, orderMap })

   - data:      array de objetos ya agregados en el servidor (fact table).
   - rows:      [dimNames]  dimensiones que agrupan verticalmente (N niveles).
   - cols:      [dimNames]  dimensiones que pivotan a columnas (N niveles anidados).
   - values:    [{ key, aggFn }]  medidas a mostrar. aggFn solo aplica a medidas 'raw'.
   - collapsed: Set de _rowKey colapsados (por defecto todo expandido).
   - orderMap:  { dimName: [valoresEnOrdenCanónico] }  para ordenar filas y columnas.

   Devuelve: { columnDefs, rowData, pinnedBottom, baseFields, measures }
   Los columnDefs traen un tag __fmt por columna de medida; la capa de vista
   les asigna valueFormatter/cellRenderer (mantiene el motor libre de DOM).
   ══════════════════════════════════════════════════════════════════════════ */
(function (global) {
  'use strict';

  var SEP = '';   // separador interno de claves

  /* ── Campos base que se agregan en cada celda ─────────────────────────── */
  var BASE_FIELDS = ['vta26', 'vta25', 'gm26', 'gm25', 'unds26', 'unds25', 'tickets26'];

  /* ── Catálogo de medidas ──────────────────────────────────────────────
     type 'raw'  → agrega un campo base con la aggFn elegida (sum/avg/min/max/count)
     type 'calc' → fórmula sobre las SUMAS de los campos base (ratios) */
  var MEASURES = {
    vta26:      { label: 'Vta Neta 26', type: 'raw',  field: 'vta26',     fmt: 'money', allowAgg: true },
    vta25:      { label: 'Vta Neta 25', type: 'raw',  field: 'vta25',     fmt: 'money', allowAgg: true },
    gm26:       { label: 'GM 26 (S/)',  type: 'raw',  field: 'gm26',      fmt: 'money', allowAgg: true },
    gm25:       { label: 'GM 25 (S/)',  type: 'raw',  field: 'gm25',      fmt: 'money', allowAgg: true },
    unds26:     { label: 'Unid 26',     type: 'raw',  field: 'unds26',    fmt: 'int',   allowAgg: true },
    unds25:     { label: 'Unid 25',     type: 'raw',  field: 'unds25',    fmt: 'int',   allowAgg: true },
    tickets26:  { label: 'Tickets',     type: 'raw',  field: 'tickets26', fmt: 'int',   allowAgg: true },

    var_pct:    { label: '%Var vs 25',  type: 'calc', fmt: 'var',
                  calc: function (s) { return s.vta25 > 0 ? (s.vta26 - s.vta25) / s.vta25 * 100 : null; } },
    gm26_pct:   { label: '%GM 26',      type: 'calc', fmt: 'pct',
                  calc: function (s) { return s.vta26 > 0 ? s.gm26 / s.vta26 * 100 : null; } },
    gm25_pct:   { label: '%GM 25',      type: 'calc', fmt: 'pct',
                  calc: function (s) { return s.vta25 > 0 ? s.gm25 / s.vta25 * 100 : null; } },
    var_unds:   { label: '%Var Unid',   type: 'calc', fmt: 'var',
                  calc: function (s) { return s.unds25 > 0 ? (s.unds26 - s.unds25) / s.unds25 * 100 : null; } },
    pprom26:    { label: 'P.Prom 26',   type: 'calc', fmt: 'money',
                  calc: function (s) { return s.unds26 > 0 ? s.vta26 / s.unds26 : null; } },
    ticket_prom:{ label: 'Ticket Prom', type: 'calc', fmt: 'money',
                  calc: function (s) { return s.tickets26 > 0 ? s.vta26 / s.tickets26 : null; } }
  };

  var AGG_LABEL = { sum: 'Σ', avg: 'Prom', min: 'Mín', max: 'Máx', count: 'Cont' };

  /* ── Estadística por campo base dentro de una celda ───────────────────── */
  function newStat()  { return { sum: 0, count: 0, min: Infinity, max: -Infinity }; }
  function pushStat(st, v) {
    v = +v || 0;
    st.sum += v; st.count += 1;
    if (v < st.min) st.min = v;
    if (v > st.max) st.max = v;
  }
  function mergeStat(a, b) {
    a.sum += b.sum; a.count += b.count;
    if (b.min < a.min) a.min = b.min;
    if (b.max > a.max) a.max = b.max;
  }
  function resolveAgg(st, aggFn) {
    if (!st || st.count === 0) return null;
    switch (aggFn) {
      case 'count': return st.count;
      case 'avg':   return st.count ? st.sum / st.count : null;
      case 'min':   return st.min === Infinity ? null : st.min;
      case 'max':   return st.max === -Infinity ? null : st.max;
      case 'sum':
      default:      return st.sum;
    }
  }

  /* ── Celda = mapa campoBase → stat ────────────────────────────────────── */
  function newCell() {
    var c = {};
    for (var i = 0; i < BASE_FIELDS.length; i++) c[BASE_FIELDS[i]] = newStat();
    return c;
  }
  function accCell(cell, row) {
    for (var i = 0; i < BASE_FIELDS.length; i++) pushStat(cell[BASE_FIELDS[i]], row[BASE_FIELDS[i]]);
  }
  function mergeCell(dst, src) {
    for (var i = 0; i < BASE_FIELDS.length; i++) mergeStat(dst[BASE_FIELDS[i]], src[BASE_FIELDS[i]]);
  }
  function sumsOf(cell) {
    var s = {};
    for (var i = 0; i < BASE_FIELDS.length; i++) s[BASE_FIELDS[i]] = cell[BASE_FIELDS[i]].sum;
    return s;
  }
  function measureValue(cell, spec) {
    if (!cell) return null;
    var def = MEASURES[spec.key];
    if (!def) return null;
    if (def.type === 'calc') return def.calc(sumsOf(cell));
    return resolveAgg(cell[def.field], spec.aggFn || 'sum');
  }
  function measureHeader(spec) {
    var def = MEASURES[spec.key];
    if (!def) return spec.key;
    if (def.type === 'raw' && spec.aggFn && spec.aggFn !== 'sum') {
      return def.label + ' (' + (AGG_LABEL[spec.aggFn] || spec.aggFn) + ')';
    }
    return def.label;
  }

  /* ── Ordenamiento canónico ────────────────────────────────────────────── */
  function makeComparator(dim, orderMap) {
    var order = (orderMap && orderMap[dim]) || null;
    return function (a, b) {
      if (order) {
        var ia = order.indexOf(a), ib = order.indexOf(b);
        if (ia < 0) ia = 999; if (ib < 0) ib = 999;
        if (ia !== ib) return ia - ib;
      }
      var na = parseFloat(a), nb = parseFloat(b);
      if (!isNaN(na) && !isNaN(nb)) return na - nb;
      return String(a).localeCompare(String(b), 'es');
    };
  }

  /* ── Núcleo ───────────────────────────────────────────────────────────── */
  function compute(data, opts) {
    opts = opts || {};
    var rows      = opts.rows   || [];
    var cols      = opts.cols   || [];
    var values    = (opts.values && opts.values.length) ? opts.values : [{ key: 'vta26', aggFn: 'sum' }];
    var collapsed = opts.collapsed || new Set();
    var orderMap  = opts.orderMap || {};

    /* raíz del árbol de filas = total general */
    var root = { value: '', level: -1, key: '', children: {}, order: [], cells: {} };

    /* recolectar combinaciones de columnas (tuplas ordenadas) */
    var colMap = {};   // colKey → path[]

    function cellOf(node, colKey) {
      if (!node.cells[colKey]) node.cells[colKey] = newCell();
      return node.cells[colKey];
    }

    data.forEach(function (row) {
      /* clave de columna */
      var colPath = cols.map(function (d) { return String(row[d] == null ? '' : row[d]); });
      var colKey  = cols.length ? colPath.join(SEP) : '__all__';
      if (!colMap[colKey]) colMap[colKey] = colPath;

      /* acumular en total general */
      accCell(cellOf(root, colKey), row);

      /* descender el árbol de filas, acumulando subtotal en cada nivel */
      var node = root;
      for (var lvl = 0; lvl < rows.length; lvl++) {
        var v = String(row[rows[lvl]] == null ? '' : row[rows[lvl]]);
        if (!node.children[v]) {
          node.children[v] = {
            value: v, level: lvl,
            key: (node.key ? node.key + SEP : '') + v,
            children: {}, order: [], cells: {}
          };
          node.order.push(v);
        }
        node = node.children[v];
        accCell(cellOf(node, colKey), row);
      }
    });

    /* ordenar hijos de cada nivel */
    (function sortTree(node) {
      if (node.level + 1 < rows.length) {
        var dim = rows[node.level + 1];
        node.order.sort(makeComparator(dim, orderMap));
      }
      node.order.forEach(function (k) { sortTree(node.children[k]); });
    })(root);

    /* ordenar columnas por cada nivel de dim */
    var colKeys = Object.keys(colMap);
    if (cols.length) {
      colKeys.sort(function (a, b) {
        var pa = colMap[a], pb = colMap[b];
        for (var i = 0; i < cols.length; i++) {
          var cmp = makeComparator(cols[i], orderMap)(pa[i], pb[i]);
          if (cmp !== 0) return cmp;
        }
        return 0;
      });
    }
    var colCombos = colKeys.map(function (k) { return { key: k, path: colMap[k] }; });

    /* ── construir fila plana para AG Grid ── */
    function buildRowObj(node, isGroup, label) {
      var obj = {
        _label: label != null ? label : node.value,
        _level: node.level < 0 ? 0 : node.level,
        _isGroup: !!isGroup,
        _rowKey: node.key,
        _expanded: !collapsed.has(node.key)
      };
      var rowTotal = newCell();
      colCombos.forEach(function (cc) {
        var cell = node.cells[cc.key];
        if (cell) mergeCell(rowTotal, cell);
        values.forEach(function (v) {
          obj[cc.key + '__' + v.key] = cell ? measureValue(cell, v) : null;
        });
      });
      values.forEach(function (v) { obj['__total__' + v.key] = measureValue(rowTotal, v); });
      return obj;
    }

    /* ── aplanar respetando estado colapsado ── */
    var rowData = [];
    (function flatten(node) {
      node.order.forEach(function (k) {
        var ch      = node.children[k];
        var isGroup = ch.level < rows.length - 1;   // tiene hijos como filas separadas
        rowData.push(buildRowObj(ch, isGroup));
        if (isGroup && !collapsed.has(ch.key)) flatten(ch);
      });
    })(root);

    /* total general → fila fija abajo */
    var grand = buildRowObj(root, false, '▶▶ Total general');
    grand._isTotal = true;
    grand._level = 0;

    /* ── column defs ── */
    var columnDefs = buildColumnDefs(cols, colCombos, values);

    return {
      columnDefs: columnDefs,
      rowData: rowData,
      pinnedBottom: [grand],
      baseFields: BASE_FIELDS.slice(),
      measures: MEASURES
    };
  }

  /* ── construcción de columnas (grupos anidados por dim de columna) ─────── */
  function buildColumnDefs(cols, colCombos, values) {
    function cap(s) { s = String(s); return s.charAt(0).toUpperCase() + s.slice(1); }

    function measureCol(colKey, spec) {
      var def = MEASURES[spec.key] || { fmt: 'money' };
      var w = def.fmt === 'money' ? 112 : def.fmt === 'var' ? 92 : def.fmt === 'pct' ? 82 : 82;
      return {
        field: colKey + '__' + spec.key,
        headerName: measureHeader(spec),
        width: w,
        suppressMovable: true,
        type: 'numericColumn',
        __fmt: def.fmt
      };
    }
    function measureGroup(header, colKey) {
      return { headerName: header, children: values.map(function (v) { return measureCol(colKey, v); }) };
    }

    var defs = [{
      field: '_label', headerName: '', pinned: 'left', width: 240,
      __label: true, suppressMovable: true
    }];

    if (!cols.length) {
      defs.push(measureGroup('Total', '__all__'));
      return defs;
    }

    /* árbol de columnas a partir de las tuplas */
    var rootG = { children: {}, order: [] };
    colCombos.forEach(function (cc) {
      var g = rootG;
      cc.path.forEach(function (seg) {
        if (!g.children[seg]) { g.children[seg] = { children: {}, order: [], _seg: seg }; g.order.push(seg); }
        g = g.children[seg];
      });
      g._leafKey = cc.key;
    });

    function groupDef(g) {
      if (g._leafKey !== undefined && g.order.length === 0) {
        return measureGroup(cap(g._seg), g._leafKey);
      }
      return { headerName: cap(g._seg), children: g.order.map(function (s) { return groupDef(g.children[s]); }) };
    }

    rootG.order.forEach(function (s) { defs.push(groupDef(rootG.children[s])); });
    defs.push(measureGroup('TOTAL', '__total__'));
    return defs;
  }

  global.PivotEngine = {
    compute: compute,
    MEASURES: MEASURES,
    BASE_FIELDS: BASE_FIELDS,
    AGG_LABEL: AGG_LABEL
  };
})(window);
