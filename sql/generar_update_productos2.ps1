# Uso:
#   1. Guarda tu lista pegada ('2066820','O22MLPL012AZUSM'), ... en sql\lista_codigos.txt
#   2. powershell -ExecutionPolicy Bypass -File sql\generar_update_productos2.ps1
#   3. Abre sql\update_productos2_generado.sql en SSMS y ejecuta
$dir = $PSScriptRoot
$pares = New-Object System.Collections.Generic.List[object]
$vistos = @{}
foreach ($m in [regex]::Matches((Get-Content "$dir\lista_codigos.txt" -Raw), "\('(\d+)'\s*,\s*'([^']*)'\)")) {
    $nuevo = $m.Groups[1].Value.Trim()
    $cod   = $m.Groups[2].Value.Trim()      # quita espacios sobrantes (ej. 'V25KDSH001CEL28 ')
    $k = "$nuevo|$cod"
    if (-not $vistos.ContainsKey($k)) { $vistos[$k] = 1; $pares.Add(@($nuevo, $cod)) }
}

# avisa de codigos con mas de un nuevo codigo
$pares | Group-Object { $_[1] } | Where-Object { ($_.Group | ForEach-Object { $_[0] } | Select-Object -Unique).Count -gt 1 } |
    ForEach-Object { Write-Host "CONFLICTO: $($_.Name) -> $(($_.Group | ForEach-Object { $_[0] }) -join ', ')" }

$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine("IF OBJECT_ID('tempdb..#map') IS NOT NULL DROP TABLE #map;")
[void]$sb.AppendLine("CREATE TABLE #map (nuevo VARCHAR(20), cod VARCHAR(30));")
for ($i = 0; $i -lt $pares.Count; $i += 1000) {
    $fin = [Math]::Min($i + 999, $pares.Count - 1)
    $filas = $pares[$i..$fin] | ForEach-Object { "('$($_[0])','$($_[1])')" }
    [void]$sb.AppendLine("INSERT INTO #map (nuevo, cod) VALUES")
    [void]$sb.AppendLine(($filas -join ",`n") + ";")
}
[void]$sb.AppendLine(@"
UPDATE p
SET    p.productos2 = m.nuevo + '-' + p.producto
FROM   productos p
INNER JOIN (SELECT cod, MIN(nuevo) AS nuevo FROM #map GROUP BY cod) m
        ON m.cod = LTRIM(RTRIM(p.CODIGO_PRODUCTO));
DROP TABLE #map;
"@)
Set-Content "$dir\update_productos2_generado.sql" $sb.ToString() -Encoding UTF8
Write-Host "Listo: $($pares.Count) pares -> update_productos2_generado.sql"
