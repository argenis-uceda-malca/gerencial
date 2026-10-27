--------------------------------------------------------------------------------------------------------
---------------------------NUEVO REPORTE YTD ULTIMO 2026------------------------------------------------------------------
drop table ytd_reporte;
create table ytd_reporte as
with temp1 as (
with temp_costos as (
select *
from dm_productos_costos a
where a.idempresa =1
)

------------------------------------
  --VENTA BTQ y EC 2026
------------------------------------
select
       case when a.sucursal like '%FALABELLA%' then 'TXD SELL IN' else e.grupo_canal end grupo_canal,
	   case when a.sucursal like '%FALABELLA%' then 'SAGA FALABELLA' else e.btq_sucursal end btq_sucursal,
	   coalesce (f.sucursal_equivalente,a.sucursal) sucursal,

       --a.fecha_documento,
	   a.fecha_documento as fecha_equivalente,
	   --cast(to_char(dc.fecha,'dd')as numeric) as dia_equivalente,
	   --dc.semana as semana,
	   dc.mes as mes,
	   dc.anio as anio,
       a.temporada as temporada,
       case when a.temporada like 'PRIMAVERA%' or a.temporada like 'VERANO%' then 'PV ' || right(a.temporada,4)
		    when a.temporada like 'OTOÑO%' or a.temporada like 'INVIERNO%' then 'OI ' || right(a.temporada,4)
		       else a.temporada end temporada_2,
		       case when a.temporada like 'PRIMAVERA%' or a.temporada like 'VERANO%' then 'PV'
		            when a.temporada like 'OTOÑO%' or a.temporada like 'INVIERNO%' then 'OI'
		            when a.temporada like 'BASICO TODAS TEMPORADA%' then 'BTT'
		       else a.temporada end temporada_3,
       a.linea,
       case when a.linea in ('BERMUDA','SHORT') then 'SHORT'
		    when a.linea in ('BUFANDA','CHALINA','PASHMINA') then 'PASHMINA'
	        when a.linea in ('BVD','TOP','POLO') then 'POLO'
		    when a.linea in ('JUMPER','ENTERIZO') then 'ENTERIZO'
		    when a.linea in ('CARDIGAN','CHOMPA') then 'CHOMPA'
	   else a.linea end linea_2,

	   case when a.linea in ('BERMUDA','SHORT','FALDA') then 'SHORT+FALDA'
	        when a.linea in ('BVD','TOP','POLO','BLUSA') then 'BLUSA+POLO'
            when a.linea in ('JUMPER','ENTERIZO','VESTIDO') then 'VESTIDO+ENTERIZO'
		    when a.linea in ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') then 'CASACA+CHOMPA+ABRIGO'
		    when a.linea in ('PANTALON','JEAN') then 'PANTALON+JEAN'
	   else 'OTROS' end linea_2_2,
       a.marca,
       /*case when a.marca not in ('EXIT','MILK','BLUES BY MILK','FINA','MENTHA & CHOCOLATE','KORDA') then 'OTROS'
       else a.marca end marca_2,*/
       case
		 when a.sucursal like '%OUTLET%'  then 'OUTLETS'
		 when a.sucursal like '%ONLINE%' then 'WEB'
		 when a.sucursal like '%MCH%' then 'BOUTIQUES MCH'
	 	 when a.sucursal like '%KORDA%' then 'BOUTIQUES KORDA'
		 when a.sucursal like '%EXIT%' then 'BOUTIQUES EXIT'
		 when a.sucursal like '%MILK%' then 'BOUTIQUES MILK'
		 when a.sucursal like '%FINA%' then 'BOUTIQUES FINA'
		 when a.sucursal like '%BLUES%' then 'BOUTIQUES BLUES'
	   else a.sucursal end marca_2,
       a.categoria,
       sum(a.importe_subtotal) as vta_soles_act,
       sum(a.unidades) as vta_und_act,
       sum(a.costo_venta_neta) as costo_venta_neta,
       0 as vta_soles_hst,
       0 as vta_und_hst,
       0 as costo_venta_neta_hst,
       0 as vta_soles_hst_1,
       0 as vta_und_hst_1,
       0 as costo_venta_neta_hst_1,
       0 as vta_soles_hst_2,
       0 as vta_und_hst_2,
       0 as costo_venta_neta_hst_2,
       0 as vta_soles_hst_3,
       0 as vta_und_hst_3,
       0 as costo_venta_neta_hst_3,
       0 as meta_venta,
       0 as meta_contribucion,
       --row_number() over(partition by a.nro_tickets order by a.nro_tickets) as nro_tickets_act,
       --0 as nro_tickets_hst,
       a.nro_tickets as nro_tickets_act,
       null as nro_tickets_hst,
       0 as nro_tickets_hst_1,
       --a.tipo_doc,
       sum(coalesce(a.unidades * a.pvp,0)) as vta_retail_act,
       0 as vta_retail_hst,
       0 as vta_retail_hst_1,
       a.sublinea,
       trim(a.coleccion) as coleccion,
       a.codigo_padre


from pla_ventas_diarias_2 a
left join pla_estancia g on g.codigo_hijo = a.codigo_hijo
left join datamart_logistica_productos p on p.codigo_producto = a.codigo_hijo
left join pla_sucursal_equivalencia f on f.sucursal =a.sucursal
left join datamart_calendario dc on dc.fecha =a.fecha_documento
left join public.pla_sucursal_filtro tp on a.idsucursal = tp.idsucursal and dc.semana  =tp.semana and dc.dia_semana =tp.dia_semana
left join pla_canal_sucursal e on e.sucursal=a.sucursal
where a.fecha_documento >='2026-01-01'
/*and a.linea not in ('IMPUESTO','SERVICIO','SERVICIO LOGISTICO','BOLSAS','ADMINISTRATIVO')
and a.sucursal <> 'SEDE CENTRAL' and a.categoria <>'SERVICIOS GENERALES' and a.marca<>'SERVICIOS' --and a.sucursal ='EXIT JOCKEY PLAZA'
and a.grupo_canal in ('PUBLICO GENERAL','E-COMMERCE','OUTLET')*/
and a.sucursal <> 'SEDE CENTRAL' and a.categoria <>'SERVICIOS GENERALES' and a.marca<>'SERVICIOS'
and a.linea not in ('BOLSAS','ADMINISTRATIVO')
and (a.grupo_canal in ('PUBLICO GENERAL','OUTLET','E-COMMERCE','CONCESIONARIO')
or (a.grupo_canal='TIENDAS POR DEPARTAMENTO' and a.marca='PARFOIS'))
group by e.grupo_canal,
	     e.btq_sucursal,
	     a.sucursal,
	     f.sucursal_equivalente,
         a.fecha_documento,
         --dc.fecha,
	     --dc.semana,
	     dc.mes,
	     dc.anio,
         a.temporada,
         a.linea,
         a.marca,
         a.categoria,
         a.nro_tickets,
         --a.tipo_doc,
         a.sublinea,
         a.coleccion,
         a.codigo_padre

union all

select
       case when a.sucursal like '%FALABELLA%' then 'TXD SELL IN' else e.grupo_canal end grupo_canal,
	   case when a.sucursal like '%FALABELLA%' then 'SAGA FALABELLA' else e.btq_sucursal end btq_sucursal,
	   coalesce (f.sucursal_equivalente,a.sucursal) sucursal,
	   a.fecha_documento as fecha_equivalente,
	   dc.mes as mes,
	   2026 as anio,
       a.temporada as temporada,
       case when a.temporada like 'PRIMAVERA%' or a.temporada like 'VERANO%' then 'PV ' || right(a.temporada,4)
		    when a.temporada like 'OTOÑO%' or a.temporada like 'INVIERNO%' then 'OI ' || right(a.temporada,4)
		       else a.temporada end temporada_2,
	   case when a.temporada like 'PRIMAVERA%' or a.temporada like 'VERANO%' then 'PV'
		    when a.temporada like 'OTOÑO%' or a.temporada like 'INVIERNO%' then 'OI'
		    when a.temporada like 'BASICO TODAS TEMPORADA%' then 'BTT'
		       else a.temporada end temporada_3,
       a.linea,
       case when a.linea in ('BERMUDA','SHORT') then 'SHORT'
		    when a.linea in ('BUFANDA','CHALINA','PASHMINA') then 'PASHMINA'
	        when a.linea in ('BVD','TOP','POLO') then 'POLO'
		    when a.linea in ('JUMPER','ENTERIZO') then 'ENTERIZO'
		    when a.linea in ('CARDIGAN','CHOMPA') then 'CHOMPA'
	   else a.linea end linea_2,
	   case when a.linea in ('BERMUDA','SHORT','FALDA') then 'SHORT+FALDA'
	        when a.linea in ('BVD','TOP','POLO','BLUSA') then 'BLUSA+POLO'
            when a.linea in ('JUMPER','ENTERIZO','VESTIDO') then 'VESTIDO+ENTERIZO'
		    when a.linea in ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') then 'CASACA+CHOMPA+ABRIGO'
		    when a.linea in ('PANTALON','JEAN') then 'PANTALON+JEAN'
	   else 'OTROS' end linea_2_2,
       a.marca,
       case
		 when a.sucursal like '%OUTLET%'  then 'OUTLETS'
		 when a.sucursal like '%ONLINE%' then 'WEB'
		 when a.sucursal like '%MCH%' then 'BOUTIQUES MCH'
	 	 when a.sucursal like '%KORDA%' then 'BOUTIQUES KORDA'
		 when a.sucursal like '%EXIT%' then 'BOUTIQUES EXIT'
		 when a.sucursal like '%MILK%' then 'BOUTIQUES MILK'
		 when a.sucursal like '%FINA%' then 'BOUTIQUES FINA'
		 when a.sucursal like '%BLUES%' then 'BOUTIQUES BLUES'
	   else a.sucursal end marca_2,
       a.categoria,
       0 as vta_soles_act,
       0 as vta_und_act,
       0 as costo_venta_neta,
       sum(a.importe_subtotal) as vta_soles_hst,
       sum(a.unidades) as vta_und_hst,
       sum(a.costo_venta_neta) as costo_venta_neta_hst,
       0 as vta_soles_hst_1,
       0 as vta_und_hst_1,
       0 as costo_venta_neta_hst_1,
       0 as vta_soles_hst_2,
       0 as vta_und_hst_2,
       0 as costo_venta_neta_hst_2,
       0 as vta_soles_hst_3,
       0 as vta_und_hst_3,
       0 as costo_venta_neta_hst_3,
       0 as meta_venta,
       0 as meta_contribucion,
       --0 as nro_tickets_act,
       --row_number() over(partition by a.nro_tickets order by a.nro_tickets) as nro_tickets_hst,
       null as nro_tickets_act,
	   a.nro_tickets as nro_tickets_hst,
       0 as nro_tickets_hst_1,
       0 as vta_retail_act,
       sum(coalesce(a.unidades * a.pvp,0)) as vta_retail_hst,
       0 as vta_retail_hst_1,
       a.sublinea,
       trim(a.coleccion) as coleccion,
       a.codigo_padre

from pla_ventas_diarias_2 a
left join pla_estancia g on g.codigo_hijo = a.codigo_hijo
left join datamart_logistica_productos p on p.codigo_producto = a.codigo_hijo
left join pla_sucursal_equivalencia f on f.sucursal =a.sucursal
left join datamart_calendario dc on dc.fecha =a.fecha_documento
left join public.pla_sucursal_filtro tp on a.idsucursal = tp.idsucursal and dc.semana  =tp.semana and dc.dia_semana =tp.dia_semana
left join pla_canal_sucursal e on e.sucursal=a.sucursal
where a.fecha_documento >='2025-01-01' and a.fecha_documento < '2026-01-01'
and a.sucursal <> 'SEDE CENTRAL' and a.categoria <>'SERVICIOS GENERALES' and a.marca<>'SERVICIOS'
and a.linea not in ('BOLSAS','ADMINISTRATIVO')
and (a.grupo_canal in ('PUBLICO GENERAL','OUTLET','E-COMMERCE','CONCESIONARIO')
or (a.grupo_canal='TIENDAS POR DEPARTAMENTO' and a.marca='PARFOIS'))
group by e.grupo_canal,
	     e.btq_sucursal,
	     a.sucursal,
	     f.sucursal_equivalente,
	     a.fecha_documento,
	     dc.mes,
         a.temporada,
         a.linea,
         a.marca,
         a.categoria,
         a.nro_tickets,
         a.sublinea,
         a.coleccion,
         a.codigo_padre


union all

select 'TXD SELL IN' as grupo_canal,
	   a.txd as btq_sucursal,
	   (a.desc_local) as sucursal,
	   --a.fecha,
	   cast(a.fecha as date) as fecha_equivalente,
	   --cast(coalesce (f.fecha_equivalente,a.fecha ) as date) as fecha_equivalente,
	   --cast(to_char(dc.fecha,'dd')as numeric) as dia_equivalente,
	   --dc.semana as semana,
	   dc.mes as mes,
	   dc.anio as anio,
       p.temporada as temporada,
       case when p.temporada like 'PRIMAVERA%' or p.temporada like 'VERANO%' then 'PV ' || right(p.temporada,4)
		            when p.temporada like 'OTOÑO%' or p.temporada like 'INVIERNO%' then 'OI ' || right(p.temporada,4)
		       else p.temporada end temporada_2,
		       case when p.temporada like 'PRIMAVERA%' or p.temporada like 'VERANO%' then 'PV'
		            when p.temporada like 'OTOÑO%' or p.temporada like 'INVIERNO%' then 'OI'
		            when p.temporada like 'BASICO TODAS TEMPORADA%' then 'BTT'
		       else p.temporada end temporada_3,
	   p.linea,
       case when p.linea in ('BERMUDA','SHORT') then 'SHORT'
		    when p.linea in ('BUFANDA','CHALINA','PASHMINA') then 'PASHMINA'
	        when p.linea in ('BVD','TOP','POLO') then 'POLO'
		    when p.linea in ('JUMPER','ENTERIZO') then 'ENTERIZO'
		    when p.linea in ('CARDIGAN','CHOMPA') then 'CHOMPA'
	   else p.linea end linea_2,

	   case when p.linea in ('BERMUDA','SHORT','FALDA') then 'SHORT+FALDA'
	        when p.linea in ('BVD','TOP','POLO','BLUSA') then 'BLUSA+POLO'
            when p.linea in ('JUMPER','ENTERIZO','VESTIDO') then 'VESTIDO+ENTERIZO'
		    when p.linea in ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') then 'CASACA+CHOMPA+ABRIGO'
		    when p.linea in ('PANTALON','JEAN') then 'PANTALON+JEAN'
	   else 'OTROS' end linea_2_2,
       a.marca,
       case when a.marca not in ('EXIT','MILK','BLUES BY MILK','FINA','MENTHA & CHOCOLATE','KORDA') then 'OTROS'
       else a.marca end marca_2,

       p.categoria,
       case when txd='RIPLEY' then sum(a.vta_soles)*0.65
       	    when txd='OECHSLE' then sum(a.vta_soles)*0.68
       	    -- when txd='SAGA FALABELLA' then sum(a.vta_soles)*0.81
       	     end vta_soles_act,
       sum(a.vta_unds) as vta_und_act,
       sum(co.costo_unitario_mn*a.vta_unds) as costo_venta_neta,
       0 as vta_soles_hst,
       0 as vta_und_hst,
       0 as costo_venta_neta_hst,
       0 as vta_soles_hst_1,
       0 as vta_und_hst_1,
       0 as costo_venta_neta_hst_1,
       0 as vta_soles_hst_2,
       0 as vta_und_hst_2,
       0 as costo_venta_neta_hst_2,
       0 as vta_soles_hst_3,
       0 as vta_und_hst_3,
       0 as costo_venta_neta_hst_3,
       0 as meta_venta,
       0 as meta_contribucion,
       --0 as nro_tickets_act,
       --0 as nro_tickets_hst,
       null as nro_tickets_act,
	   null as nro_tickets_hst,
       0 as nro_tickets_hst_1,
       --null as tipo_doc,
       sum(a.vta_unds*pv.precio) as vta_retail_act,
       0 as vta_retail_hst,
       0 as vta_retail_hst_1,
       p.sublinea as sublinea,
       p.coleccion as coleccion,
       p.codigo_padre

from pla_ventas_txd a
left join pla_fechas_equivalentes f on a.fecha =f.fecha
left join datamart_calendario dc on dc.fecha =a.fecha
left join pla_sku_txd s on a.sku_txd=s.sku_txd
left join temp_costos co on s.sku_sb =co.codigo_producto
left join datamart_logistica_productos p on s.sku_sb=p.codigo_producto
left join public.lista_precios_detalle_item pv on p.codigo_padre = pv.codigo_producto and pv.idlista=27
where a.fecha >= '2026-01-01'
and p.linea not in ('IMPUESTO','SERVICIO','SERVICIO LOGISTICO','BOLSAS','ADMINISTRATIVO')
group by
           a.txd,
           a.desc_local,
           a.fecha,
           --f.fecha_equivalente,
           --dc.fecha,
           --dc.semana,
           dc.mes,
           dc.anio,
           p.temporada,
           p.linea,
           a.marca,
           p.coleccion,
           p.categoria,
            p.sublinea,
	       p.coleccion,
	       p.codigo_padre


union all

select 'TXD SELL IN' as grupo_canal,
	   a.txd as btq_sucursal,
	   (a.desc_local) as sucursal,
	   cast(a.fecha as date) as fecha_equivalente,
	   dc.mes as mes,
	   2026 as anio,
       p.temporada as temporada,
       case when p.temporada like 'PRIMAVERA%' or p.temporada like 'VERANO%' then 'PV ' || right(p.temporada,4)
		    when p.temporada like 'OTOÑO%' or p.temporada like 'INVIERNO%' then 'OI ' || right(p.temporada,4)
		       else p.temporada end temporada_2,
	   case when p.temporada like 'PRIMAVERA%' or p.temporada like 'VERANO%' then 'PV'
		    when p.temporada like 'OTOÑO%' or p.temporada like 'INVIERNO%' then 'OI'
		    when p.temporada like 'BASICO TODAS TEMPORADA%' then 'BTT'
		       else p.temporada end temporada_3,
	   p.linea,
       case when p.linea in ('BERMUDA','SHORT') then 'SHORT'
		    when p.linea in ('BUFANDA','CHALINA','PASHMINA') then 'PASHMINA'
	        when p.linea in ('BVD','TOP','POLO') then 'POLO'
		    when p.linea in ('JUMPER','ENTERIZO') then 'ENTERIZO'
		    when p.linea in ('CARDIGAN','CHOMPA') then 'CHOMPA'
	   else p.linea end linea_2,
	   case when p.linea in ('BERMUDA','SHORT','FALDA') then 'SHORT+FALDA'
	        when p.linea in ('BVD','TOP','POLO','BLUSA') then 'BLUSA+POLO'
            when p.linea in ('JUMPER','ENTERIZO','VESTIDO') then 'VESTIDO+ENTERIZO'
		    when p.linea in ('CARDIGAN','CHOMPA','CASACA','CHALECO','ABRIGO') then 'CASACA+CHOMPA+ABRIGO'
		    when p.linea in ('PANTALON','JEAN') then 'PANTALON+JEAN'
	   else 'OTROS' end linea_2_2,
       a.marca,
       case when a.marca not in ('EXIT','MILK','BLUES BY MILK','FINA','MENTHA & CHOCOLATE','KORDA') then 'OTROS'
       else a.marca end marca_2,
       p.categoria,
       0 as vta_soles_act,
       0 as vta_und_act,
       0 as costo_venta_neta,
       case when txd='RIPLEY' then sum(a.vta_soles)*0.65
       	    when txd='OECHSLE' then sum(a.vta_soles)*0.68
       	     end vta_soles_hst,
       sum(a.vta_unds) as vta_und_hst,
       sum(co.costo_unitario_mn*a.vta_unds) as costo_venta_neta_hst,
       0 as vta_soles_hst_1,
       0 as vta_und_hst_1,
       0 as costo_venta_neta_hst_1,
       0 as vta_soles_hst_2,
       0 as vta_und_hst_2,
       0 as costo_venta_neta_hst_2,
       0 as vta_soles_hst_3,
       0 as vta_und_hst_3,
       0 as costo_venta_neta_hst_3,
       0 as meta_venta,
       0 as meta_contribucion,
       --0 as nro_tickets_act,
       --0 as nro_tickets_hst,
       null as nro_tickets_act,
	   null as nro_tickets_hst,
       0 as nro_tickets_hst_1,
       0 as vta_retail_act,
       sum(a.vta_unds*pv.precio) as vta_retail_hst,
       0 as vta_retail_hst_1,
       p.sublinea as sublinea,
       p.coleccion as coleccion,
       p.codigo_padre

from pla_ventas_txd a
left join pla_fechas_equivalentes f on a.fecha =f.fecha
left join datamart_calendario dc on dc.fecha =a.fecha
left join pla_sku_txd s on a.sku_txd=s.sku_txd
left join temp_costos co on s.sku_sb =co.codigo_producto
left join datamart_logistica_productos p on s.sku_sb=p.codigo_producto
left join public.lista_precios_detalle_item pv on p.codigo_padre = pv.codigo_producto and pv.idlista=27
where a.fecha >= '2025-01-01' and a.fecha < '2026-01-01'
and p.linea not in ('IMPUESTO','SERVICIO','SERVICIO LOGISTICO','BOLSAS','ADMINISTRATIVO')
group by
           a.txd,
           a.desc_local,
           a.fecha,
           dc.mes,
           p.temporada,
           p.linea,
           a.marca,
           p.coleccion,
           p.categoria,
           p.sublinea,
	       p.codigo_padre

----------------------------------------------------------------------------------------------------------------------
----------------------------------------------------------------------------------------------------------------------
 ---------------------------------------------------------------------------------------------------------------------------------------
 ------------METAS VENTA / META GM DIARIAS.
	       union all

	select
	       case
	       	when m.sucursal like '%ONLINE%' then 'WEB'
	       	when m.sucursal like '%EXIT%' then 'BOUTIQUES'
	       	when m.sucursal like '%FINA%' then 'BOUTIQUES'
	       	when m.sucursal like '%MCH%' then 'BOUTIQUES'
	       	when m.sucursal like '%MILK%' then 'BOUTIQUES'
	      	when m.sucursal like '%FALABELLA%' then 'TXD SELL IN'
	      	when m.sucursal like '%OUTLET%' then 'OUTLETS'
	       	else m.sucursal end grupo_canal,
		   case
	       	when m.sucursal like '%ONLINE%' then 'WEB'
	       	when m.sucursal like '%EXIT%' then 'BOUTIQUES EXIT'
	       	when m.sucursal like '%FINA%' then 'BOUTIQUES FINA'
	       	when m.sucursal like '%MCH%' then 'BOUTIQUES MCH'
	       	when m.sucursal like '%MILK%' then 'BOUTIQUES MILK'
	      	when m.sucursal like '%FALABELLA%' then 'SAGA FALABELLA'
	      	when m.sucursal like '%OUTLET%' then 'OUTLETS'
	       	else m.sucursal end btq_sucursal,
		   case when m.sucursal like '%OUTLET ASIA%' then 'MCH ASIA'
	        when m.sucursal like '%OUTLET CRONOS%' then 'MCH CRONOS'
	        when m.sucursal in ('JEANS BAR RP CHICLAYO','MILK RP CHICLAYO') then 'MILK RP CHICLAYO'
	        when m.sucursal in ('OUTLET MILK MA SANTA ANITA','MILK MA SANTA ANITA') then 'MILK MA SANTA ANITA'
	        when m.sucursal in ('OUTLET MILK LR BRASIL','MILK LR BRASIL') then 'MILK LR BRASIL'
	        when m.sucursal in ('OUTLET EXIT MA SANTA ANITA','EXIT MA SANTA ANITA') then 'EXIT MA SANTA ANITA'
	        else m.sucursal end sucursal,

		   --cast(m.fecha as date),
		   cast(m.fecha as date) as fecha_equivalente,
		   --cast(TO_CHAR(c.fecha,'dd') as numeric) as dia_equivalente,
	       --c.semana as semana,
	       c.mes as mes,
	       c.anio as anio,
           null as temporada,
           null as temporada_2,
           null as temporada_3,
           null as linea,
		   null as linea_2,
		   null as linea_2_2,
	       case
			            when m.sucursal like '%OUTLET%'  then 'OUTLETS'
			            when m.sucursal like '%ONLINE%' then 'WEB'
			            when m.sucursal like '%MCH%' and m.sucursal not like '%ONLINE%' then 'MENTHA & CHOCOLATE'
			            when m.sucursal like '%KORDA%' and m.sucursal not like '%ONLINE%' then 'KORDA'
			            when m.sucursal like '%EXIT%' and m.sucursal not like '%ONLINE%' then 'EXIT'
			            when m.sucursal like '%MILK%' and m.sucursal not like '%ONLINE%' then 'MILK'
			            when m.sucursal like '%FINA%' and m.sucursal not like '%ONLINE%' then 'FINA'
			            when m.sucursal like '%BLUES%' and m.sucursal not like '%ONLINE%' then 'BLUES BY MILK'
			                  end marca,
	       case
			            when m.sucursal like '%OUTLET%'  then 'OUTLETS'
			            when m.sucursal like '%ONLINE%' then 'WEB'
			            when m.sucursal like '%MCH%' and m.sucursal not like '%ONLINE%' then 'MENTHA & CHOCOLATE'
			            when m.sucursal like '%KORDA%' and m.sucursal not like '%ONLINE%' then 'KORDA'
			            when m.sucursal like '%EXIT%' and m.sucursal not like '%ONLINE%' then 'EXIT'
			            when m.sucursal like '%MILK%' and m.sucursal not like '%ONLINE%' then 'MILK'
			            when m.sucursal like '%FINA%' and m.sucursal not like '%ONLINE%' then 'FINA'
			            when m.sucursal like '%BLUES%' and m.sucursal not like '%ONLINE%' then 'BLUES BY MILK'
			                  end marca_2,
           null as categoria,
       0 as  vta_soles_act,
       0 as vta_und_act,
       0 as costo_venta_neta,
       0 as vta_soles_hst,
       0 as vta_und_hst,
       0 as costo_venta_neta_hst,
       0 as vta_soles_hst_1,
       0 as vta_und_hst_1,
       0 as costo_venta_neta_hst_1,
       0 as vta_soles_hst_2,
       0 as vta_und_hst_2,
       0 as costo_venta_neta_hst_2,
       0 as vta_soles_hst_3,
       0 as vta_und_hst_3,
       0 as costo_venta_neta_hst_3,
	   --0 as stock,
       --0 as costo_stock,
       --0 as stock_hst,
       --0 as costo_stock_hst,
	    /*  case
				       when m.sucursal LIKE '% FALABELLA' then NULL
			       		when m.sucursal in ('MCH ROBLE','MCH LR SAN BORJA','MCH OP ANGAMOS','MCH PLAZA NORTE','MCH PLAZA SAN MIGUEL','MCH RP SALAVERRY','MCH CRONOS','MCH RP TRUJILLO') then  s.porcentaje*m.importe
			       		when m.sucursal in ('MILK RP CHICLAYO','MILK MGP CHORRILLOS','MILK LR SAN BORJA','MILK EL ROBLE','OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET SAN JUAN DE LURIGANCHO','OUTLET MINKA','OUTLET OP ATOCONGO','OUTLET CHIMBOTE',
			       							'KORDA ONLINE','MILK MP AREQUIPA','MILK LR BRASIL','MILK MALL DEL SUR','MILK MP TRUJILLO','MILK OP PIURA','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO','MILK RP PIURA','MILK RP CENTRO CIVICO') and  m.fecha <='2022-10-11' then  r.porcentaje*m.importe

			       		when m.sucursal in ('MILK RP CHICLAYO','MILK MGP CHORRILLOS','MILK LR SAN BORJA','MILK EL ROBLE','OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET SAN JUAN DE LURIGANCHO','OUTLET MINKA','OUTLET OP ATOCONGO','OUTLET CHIMBOTE',
			       							'KORDA ONLINE','MILK MP AREQUIPA','MILK LR BRASIL','MILK MALL DEL SUR','MILK MP TRUJILLO','MILK OP PIURA','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO','MILK RP PIURA',
			       							'MILK RP CENTRO CIVICO','EXIT RP CUSCO','KORDA MA IQUITOS','OUTLET SANTA CLARA','OUTLET EL DERBY','OUTLET DINET','MCH ASIA') and m.fecha >='2022-10-12' then t.porcentaje*m.importe
			       		--when m.sucursal in ('MILK MP AREQUIPA') and m.fecha >='2022-09-01' then w.porcentaje*m.importe
			       else m.importe end meta_venta,
			       case
				       when m.sucursal LIKE '% FALABELLA' then NULL
			       		when m.sucursal in ('MCH ROBLE','MCH LR SAN BORJA','MCH OP ANGAMOS','MCH PLAZA NORTE','MCH PLAZA SAN MIGUEL','MCH RP SALAVERRY','MCH CRONOS','MCH RP TRUJILLO') then  s.porcentaje*m.importe*m.contribucion
			       		when m.sucursal in ('MILK RP CHICLAYO','MILK MGP CHORRILLOS','MILK LR SAN BORJA','MILK EL ROBLE','OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET SAN JUAN DE LURIGANCHO','OUTLET MINKA','OUTLET OP ATOCONGO','OUTLET CHIMBOTE',
			       							'KORDA ONLINE','MILK MP AREQUIPA','MILK LR BRASIL','MILK MALL DEL SUR','MILK MP TRUJILLO','MILK OP PIURA','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO','MILK RP PIURA','MILK RP CENTRO CIVICO') and  m.fecha <='2022-10-11'then  r.porcentaje*m.importe*m.contribucion

			       		when m.sucursal in ('MILK RP CHICLAYO','MILK MGP CHORRILLOS','MILK LR SAN BORJA','MILK EL ROBLE','OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET SAN JUAN DE LURIGANCHO','OUTLET MINKA','OUTLET OP ATOCONGO','OUTLET CHIMBOTE',
			       							'KORDA ONLINE','MILK MP AREQUIPA','MILK LR BRASIL','MILK MALL DEL SUR','MILK MP TRUJILLO','MILK OP PIURA','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO','MILK RP PIURA',
			       							'MILK RP CENTRO CIVICO','EXIT RP CUSCO','KORDA MA IQUITOS','OUTLET SANTA CLARA','OUTLET EL DERBY','OUTLET DINET','MCH ASIA') and m.fecha >='2022-10-12' then t.porcentaje*m.importe*m.contribucion

			       else m.importe*m.contribucion end meta_contribucion,	  */--comentado 2024-11
       mn.porcentaje*m.importe as meta_venta,
       m.importe*m.contribucion*mn.porcentaje as meta_contribucion,
       0 as nro_tickets_act,
       0 as nro_tickets_hst,
       0 as nro_tickets_hst_1,
       --null as tipo_doc,
       0 as vta_retail_act,
       0 as vta_retail_hst,
       0 as vta_retail_hst_1,
       null as sublinea,
       null as coleccion,
       null codigo_padre

	from datamart_ventas_cuota_postgre m

	--left join pla_meta_sucursal_marca s on s.sucursal = m.sucursal
	left join pla_meta_sucursal_marca_nuevo mn on mn.sucursal = m.sucursal

	left join pla_fechas_equivalentes f on m.fecha=f.fecha
	left join datamart_calendario c on coalesce(f.fecha_equivalente,m.fecha)=c.fecha
	left join public.dm_comercial_sucursales su on m.idsucursal=su.idsucursal
	left join public.pla_sucursal_filtro tp on m.idsucursal = tp.idsucursal and c.semana =tp.semana and c.dia_semana =tp.dia_semana
    /*left join (select k.* from pla_meta_sucursal_marca_korda_milk k where k.sucursal in ('MILK RP CHICLAYO','MILK MGP CHORRILLOS','MILK LR SAN BORJA','MILK EL ROBLE','OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET SAN JUAN DE LURIGANCHO','OUTLET MINKA','OUTLET OP ATOCONGO','OUTLET CHIMBOTE',
			       					'KORDA ONLINE','MILK MP AREQUIPA','MILK LR BRASIL','MILK MALL DEL SUR','MILK MP TRUJILLO','MILK OP PIURA','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO','MILK RP PIURA','MILK RP CENTRO CIVICO') ) r on r.sucursal = m.sucursal and m.fecha <='2022-10-11'
	left join (select p.* from pla_meta_sucursal_marca_korda_milk_fecha p where p.sucursal in ('MILK RP CHICLAYO','MILK MGP CHORRILLOS','MILK LR SAN BORJA','MILK EL ROBLE','OUTLET VULCANO II','OUTLET VULCANO MAYORISTA','OUTLET SAN JUAN DE LURIGANCHO','OUTLET MINKA','OUTLET OP ATOCONGO','OUTLET CHIMBOTE',
			       					'KORDA ONLINE','MILK MP AREQUIPA','MILK LR BRASIL','MILK MALL DEL SUR','MILK MP TRUJILLO','MILK OP PIURA','MILK ONLINE','MILK PLAZA NORTE','MILK RP TRUJILLO','MILK RP PIURA','MILK RP CENTRO CIVICO','EXIT RP CUSCO','KORDA MA IQUITOS','OUTLET SANTA CLARA','OUTLET EL DERBY','OUTLET DINET','MCH ASIA') ) t on t.sucursal = m.sucursal and m.fecha >='2022-10-12'
	*/
	where m.fecha >='2026-01-01'

	       union all

	select

       'TXD SELL IN' as grupo_canal,
	   m.txd as btq_sucursal,
	   case
	   	when m.marca= 'EXIT' and m.txd = 'SAGA FALABELLA' then 'EXIT FALABELLA'
        when m.marca= 'KORDA' and m.txd = 'SAGA FALABELLA' then 'KORDA FALABELLA'
        else m.sucursal end sucursal,
       --m.fecha,
       cast(m.fecha as date) as fecha_equivalente,
       --cast(to_char(m.fecha,'dd')as numeric) as dia_equivalente,
	   --c.semana as semana,
	   c.mes as mes,
	   c.anio as anio,
	   null as temporada,
	   null as temporada_2,
	   null as temporada_3,
	   null as linea,
	   null as linea_2,
	   null as linea_2_2,
	   m.marca as marca,
	   case when m.marca not in ('EXIT','MILK','BLUES BY MILK','FINA','MENTHA & CHOCOLATE','KORDA') then 'OTROS'
       else m.marca end marca_2,
	   --m.marca as marca_2,
	   --m.marca as marca_temporada,
	   --null as coleccion,
       --null as mes_coleccion_,
       null as categoria,
       0 as  vta_soles_act,
       0 as vta_und_act,
       0 as costo_venta_neta,
       0 as vta_soles_hst,
       0 as vta_und_hst,
       0 as costo_venta_neta_hst,
       0 as vta_soles_hst_1,
       0 as vta_und_hst_1,
       0 as costo_venta_neta_hst_1,
       0 as vta_soles_hst_2,
       0 as vta_und_hst_2,
       0 as costo_venta_neta_hst_2,
       0 as vta_soles_hst_3,
       0 as vta_und_hst_3,
       0 as costo_venta_neta_hst_3,
	   --0 as stock,
       --0 as costo_stock,
       --0 as stock_hst,
       --0 as costo_stock_hst,
	   --'SSS' as filtro_sss2,
       --m.importe as meta_venta, -- COMENTADO PARA QUE SALGA CON META SELL IN, m.importe es la meta sell out , pero al multiplicar por los porcentajes de abajo sale la meta sell in
       case when txd='RIPLEY' then (m.importe)*0.65
       	    when txd='OECHSLE' then (m.importe)*0.68
       	    -- when txd='SAGA FALABELLA' then sum(a.vta_soles)*0.81
       	     end meta_venta,
       m.contri_meta as meta_contribucion,
      -- null as mall,
       0 as nro_tickets_act,
       0 as nro_tickets_hst,
       0 as nro_tickets_hst_1,
       --null as tipo_doc,
       0 as vta_retail_act,
       0 as vta_retail_hst,
       0 as vta_retail_hst_1,
       null as sublinea,
       null as coleccion,
       null codigo_padre

from pla_metas_txd m
--left join datamart_calendario c on m.fecha=c.fecha
left join pla_fechas_equivalentes f on m.fecha=f.fecha
left join datamart_calendario c on coalesce(f.fecha_equivalente,m.fecha)=c.fecha
where sell like '%sell out%' and m.fecha >='2026-01-01'

          )

select a.*,
			   case
		       		when right(a.temporada,2) = '21' then temporada_3||' 21'
		       		when right(a.temporada,2) = '22' then temporada_3||' 22'
		       		when right(a.temporada,2) = '23' then temporada_3||' 23'
		       		when right(a.temporada,2) = '24' then temporada_3||' 24'
		       		when right(a.temporada,2) = '25' then temporada_3||' 25'
		       		when right(a.temporada,2) = '26' then temporada_3||' 26'
		       	    when a.temporada like 'BASICO TODAS TEMPORADA%' then 'TT'
		       else 'TPASD' end temporada_4,

		       case
		       	   	when temporada_3 like '%OI 23%' then 'CORRIENTE'
		       		when temporada_3 like '%PV 24%' then 'CORRIENTE'
		       		when a.temporada like '%BASICO TODAS TEMPORADA%' then 'CORRIENTE'
		       		else 'NO CORRIENTE'
		       end temporada_5,
		       /*case when nro_tickets_act = 1 and tipo_doc not in ('NCE','NDE','NC')
		       then 1 else 0 end flag_tickets_act,
               case when nro_tickets_hst = 1 and tipo_doc not in ('NCE','NDE','NC')
               then 1 else 0 end flag_tickets_hst,
               case when nro_tickets_hst_1 = 1 and tipo_doc not in ('NCE','NDE','NC')
               then 1 else 0 end flag_tickets_hst_1,*/
               null _mes_coleccion

from temp1 a;
