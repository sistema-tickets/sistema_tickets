# Pagina 1 con 6 KPI cards.
# Usa _blank.pbix como fuente UNICA (DataModel + todo), solo reemplaza Report/Layout.
# Razon: Sistema_Tickets.pbix tiene Mashup vacio (section Section1) tras SaveChanges de AMO.

$ErrorActionPreference = "Stop"
Add-Type -AssemblyName System.IO.Compression.FileSystem

$projectDir = $PSScriptRoot
$blankPbix  = Join-Path $projectDir "_blank.pbix"
$outputPbix = Join-Path $projectDir "Sistema_Tickets_p1.pbix"

if (-not (Test-Path $blankPbix)) { Write-Error "_blank.pbix no encontrado." }

# Pagina 1 con 6 KPI cards (canvas 1280x720)
$page1 = @'
[{
  "id":"pg01","name":"ReportSection01","displayName":"Resumen Ejecutivo",
  "ordinal":0,"filters":"[]",
  "config":"{\"visibility\":0,\"displayOption\":1}",
  "visualContainers":[
    {"x":16,"y":20,"z":1000,"width":190,"height":90,"filters":"[]",
     "config":"{\"name\":\"kpi01\",\"layouts\":[{\"id\":0,\"position\":{\"x\":16,\"y\":20,\"z\":1000,\"width\":190,\"height\":90,\"tabOrder\":1000}}],\"singleVisual\":{\"visualType\":\"card\",\"projections\":{\"Values\":[{\"queryRef\":\"m.Total Tickets\"}]},\"prototypeQuery\":{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Total Tickets\"},\"Name\":\"m.Total Tickets\"}]},\"vcObjects\":{\"title\":[{\"properties\":{\"text\":{\"expr\":{\"Literal\":{\"Value\":\"'Total Tickets'\"}}}}}]}}}",
     "query":"{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Total Tickets\"},\"Name\":\"Total Tickets\"}]}"},
    {"x":216,"y":20,"z":1001,"width":190,"height":90,"filters":"[]",
     "config":"{\"name\":\"kpi02\",\"layouts\":[{\"id\":0,\"position\":{\"x\":216,\"y\":20,\"z\":1001,\"width\":190,\"height\":90,\"tabOrder\":1001}}],\"singleVisual\":{\"visualType\":\"card\",\"projections\":{\"Values\":[{\"queryRef\":\"m.Tickets Activos\"}]},\"prototypeQuery\":{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Tickets Activos\"},\"Name\":\"m.Tickets Activos\"}]},\"vcObjects\":{\"title\":[{\"properties\":{\"text\":{\"expr\":{\"Literal\":{\"Value\":\"'Activos'\"}}}}}]}}}",
     "query":"{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Tickets Activos\"},\"Name\":\"Activos\"}]}"},
    {"x":416,"y":20,"z":1002,"width":190,"height":90,"filters":"[]",
     "config":"{\"name\":\"kpi03\",\"layouts\":[{\"id\":0,\"position\":{\"x\":416,\"y\":20,\"z\":1002,\"width\":190,\"height\":90,\"tabOrder\":1002}}],\"singleVisual\":{\"visualType\":\"card\",\"projections\":{\"Values\":[{\"queryRef\":\"m.Tickets Cerrados\"}]},\"prototypeQuery\":{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Tickets Cerrados\"},\"Name\":\"m.Tickets Cerrados\"}]},\"vcObjects\":{\"title\":[{\"properties\":{\"text\":{\"expr\":{\"Literal\":{\"Value\":\"'Cerrados'\"}}}}}]}}}",
     "query":"{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Tickets Cerrados\"},\"Name\":\"Cerrados\"}]}"},
    {"x":616,"y":20,"z":1003,"width":190,"height":90,"filters":"[]",
     "config":"{\"name\":\"kpi04\",\"layouts\":[{\"id\":0,\"position\":{\"x\":616,\"y\":20,\"z\":1003,\"width\":190,\"height\":90,\"tabOrder\":1003}}],\"singleVisual\":{\"visualType\":\"card\",\"projections\":{\"Values\":[{\"queryRef\":\"m.Vencidos SLA\"}]},\"prototypeQuery\":{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Vencidos SLA\"},\"Name\":\"m.Vencidos SLA\"}]},\"vcObjects\":{\"title\":[{\"properties\":{\"text\":{\"expr\":{\"Literal\":{\"Value\":\"'Vencidos SLA'\"}}}}}]}}}",
     "query":"{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Vencidos SLA\"},\"Name\":\"Vencidos\"}]}"},
    {"x":816,"y":20,"z":1004,"width":190,"height":90,"filters":"[]",
     "config":"{\"name\":\"kpi05\",\"layouts\":[{\"id\":0,\"position\":{\"x\":816,\"y\":20,\"z\":1004,\"width\":190,\"height\":90,\"tabOrder\":1004}}],\"singleVisual\":{\"visualType\":\"card\",\"projections\":{\"Values\":[{\"queryRef\":\"m.Sin Asignar\"}]},\"prototypeQuery\":{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Sin Asignar\"},\"Name\":\"m.Sin Asignar\"}]},\"vcObjects\":{\"title\":[{\"properties\":{\"text\":{\"expr\":{\"Literal\":{\"Value\":\"'Sin Asignar'\"}}}}}]}}}",
     "query":"{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Sin Asignar\"},\"Name\":\"Sin Asignar\"}]}"},
    {"x":1016,"y":20,"z":1005,"width":190,"height":90,"filters":"[]",
     "config":"{\"name\":\"kpi06\",\"layouts\":[{\"id\":0,\"position\":{\"x\":1016,\"y\":20,\"z\":1005,\"width\":190,\"height\":90,\"tabOrder\":1005}}],\"singleVisual\":{\"visualType\":\"card\",\"projections\":{\"Values\":[{\"queryRef\":\"m.Criticos Activos\"}]},\"prototypeQuery\":{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Criticos Activos\"},\"Name\":\"m.Criticos Activos\"}]},\"vcObjects\":{\"title\":[{\"properties\":{\"text\":{\"expr\":{\"Literal\":{\"Value\":\"'Criticos'\"}}}}}]}}}",
     "query":"{\"Version\":2,\"From\":[{\"Name\":\"m\",\"Entity\":\"_Medidas\",\"Type\":0}],\"Select\":[{\"Measure\":{\"Expression\":{\"SourceRef\":{\"Source\":\"m\"}},\"Property\":\"Criticos Activos\"},\"Name\":\"Criticos\"}]}"}
  ]
}]
'@

# Leer TODAS las entradas de _blank.pbix (preservando orden)
Write-Host "Leyendo _blank.pbix ..." -ForegroundColor Cyan
$entries = [System.Collections.Generic.List[System.Tuple[string,byte[]]]]::new()
$zr = [System.IO.Compression.ZipFile]::OpenRead($blankPbix)
foreach ($e in $zr.Entries) {
    $ms = New-Object System.IO.MemoryStream
    $s = $e.Open(); $s.CopyTo($ms); $s.Close()
    $entries.Add([System.Tuple]::Create($e.FullName, $ms.ToArray()))
    $ms.Dispose()
}
$zr.Dispose()

foreach ($t in $entries) {
    Write-Host ("  {0,-45} {1,8} bytes" -f $t.Item1, $t.Item2.Length) -ForegroundColor Gray
}

# Leer y decodificar Report/Layout de _blank.pbix
$layoutBytes = ($entries | Where-Object { $_.Item1 -eq "Report/Layout" } | Select-Object -First 1).Item2
Write-Host ("`nReport/Layout: primeros bytes {0:X2} {1:X2}, tamano {2}" -f $layoutBytes[0], $layoutBytes[1], $layoutBytes.Length) -ForegroundColor Gray

if ($layoutBytes[0] -eq 0xFF -and $layoutBytes[1] -eq 0xFE) {
    $layoutText = [System.Text.Encoding]::Unicode.GetString($layoutBytes, 2, $layoutBytes.Length - 2); $enc = "utf16bom"
} elseif ($layoutBytes[0] -eq 0x7B -and $layoutBytes[1] -eq 0x00) {
    $layoutText = [System.Text.Encoding]::Unicode.GetString($layoutBytes); $enc = "utf16"
} else {
    $layoutText = [System.Text.Encoding]::UTF8.GetString($layoutBytes); $enc = "utf8"
}

Write-Host ("Layout enc={0}, {1} chars" -f $enc, $layoutText.Length) -ForegroundColor Gray

# Inyectar sections con Pagina 1
$si = $layoutText.IndexOf('"sections"')
if ($si -ge 0) {
    $ai = $layoutText.IndexOf('[', $si); $d = 0; $ae = $ai
    for ($i = $ai; $i -lt $layoutText.Length; $i++) {
        if ($layoutText[$i] -eq '[') { $d++ }
        if ($layoutText[$i] -eq ']') { $d--; if ($d -eq 0) { $ae = $i; break } }
    }
    $merged = $layoutText.Substring(0, $ai) + $page1 + $layoutText.Substring($ae + 1)
} else {
    $close = $layoutText.LastIndexOf('}')
    $merged = $layoutText.Substring(0, $close) + ',"sections":' + $page1 + '}'
}

if ($enc -eq "utf16bom") { $lb = [byte[]]@(0xFF,0xFE) + [System.Text.Encoding]::Unicode.GetBytes($merged) }
elseif ($enc -eq "utf16")  { $lb = [System.Text.Encoding]::Unicode.GetBytes($merged) }
else                        { $lb = [System.Text.Encoding]::UTF8.GetBytes($merged) }

Write-Host ("Layout final: {0} chars -> {1} bytes" -f $merged.Length, $lb.Length) -ForegroundColor Gray

# Construir ZIP de salida preservando orden
Write-Host "`nConstruyendo $outputPbix ..." -ForegroundColor Cyan
if (Test-Path $outputPbix) { Remove-Item $outputPbix -Force }

$zout = [System.IO.Compression.ZipFile]::Open($outputPbix, 'Create')
try {
    foreach ($t in $entries) {
        if ($t.Item1 -eq "Report/Layout") { continue }
        $ne = $zout.CreateEntry($t.Item1, [System.IO.Compression.CompressionLevel]::Optimal)
        $ns = $ne.Open(); $ns.Write($t.Item2, 0, $t.Item2.Length); $ns.Close()
    }
    $le = $zout.CreateEntry("Report/Layout", [System.IO.Compression.CompressionLevel]::Optimal)
    $ls = $le.Open(); $ls.Write($lb, 0, $lb.Length); $ls.Close()
} finally { $zout.Dispose() }

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " Generado: Sistema_Tickets_p1.pbix" -ForegroundColor Green
Write-Host " Fuente: _blank.pbix (DataModel valido)" -ForegroundColor Green
Write-Host " Pagina 1 con 6 KPI cards:" -ForegroundColor Green
Write-Host "   Total Tickets | Activos | Cerrados" -ForegroundColor White
Write-Host "   Vencidos SLA  | Sin Asignar | Criticos" -ForegroundColor White
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANTE: Si abre correctamente, ejecutar:" -ForegroundColor Yellow
Write-Host "  .\9_agregar_medidas_amo.ps1  (agrega _Medidas con las 17 medidas)" -ForegroundColor White
Write-Host "  Ctrl+S en Power BI Desktop" -ForegroundColor White
Write-Host "=============================================" -ForegroundColor Green

$resp = Read-Host "`nAbrir Sistema_Tickets_p1.pbix ahora? (s/n)"
if ($resp -eq 's') {
    $pbi = @("C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe",
             (& { $p=Get-AppxPackage "Microsoft.MicrosoftPowerBIDesktop" -EA SilentlyContinue; if($p){Join-Path $p.InstallLocation "bin\PBIDesktop.exe"}})) |
           Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if ($pbi) { Start-Process $pbi -ArgumentList "`"$outputPbix`"" }
    else { Write-Host "PBI Desktop no encontrado. Abre el archivo manualmente." -ForegroundColor Yellow }
}
