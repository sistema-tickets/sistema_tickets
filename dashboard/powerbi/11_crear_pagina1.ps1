# Inyecta Pagina 1 con 6 KPI cards usando _blank.pbix como base limpia.
# _blank.pbix fue guardado por PBI Desktop ANTES de cualquier modificacion.

$ErrorActionPreference = "Stop"
Add-Type -AssemblyName System.IO.Compression.FileSystem

$projectDir  = $PSScriptRoot
$blankPbix   = Join-Path $projectDir "_blank.pbix"
$modelPbix   = Join-Path $projectDir "Sistema_Tickets.pbix"
$outputPbix  = Join-Path $projectDir "Sistema_Tickets_p1.pbix"

if (-not (Test-Path $blankPbix))  { Write-Error "_blank.pbix no encontrado." }
if (-not (Test-Path $modelPbix))  { Write-Error "Sistema_Tickets.pbix no encontrado." }

# Pagina 1 con 6 KPI cards (ancho canvas 1280 x alto 720)
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

# ── Leer Report/Layout del BLANK (layout nativo de PBI Desktop sin modelo) ──
Write-Host "Leyendo layout nativo de _blank.pbix ..." -ForegroundColor Cyan
$zr   = [System.IO.Compression.ZipFile]::OpenRead($blankPbix)
$le   = $zr.Entries | Where-Object { $_.FullName -eq "Report/Layout" } | Select-Object -First 1
$ms   = New-Object System.IO.MemoryStream
$s    = $le.Open(); $s.CopyTo($ms); $s.Close()
$raw  = $ms.ToArray(); $ms.Dispose()
$zr.Dispose()

Write-Host ("  Primeros bytes: {0:X2} {1:X2}  Tamano: {2} bytes" -f $raw[0],$raw[1],$raw.Length) -ForegroundColor Gray

if ($raw[0] -eq 0xFF -and $raw[1] -eq 0xFE) {
    $baseText = [System.Text.Encoding]::Unicode.GetString($raw, 2, $raw.Length - 2); $enc = "utf16bom"
} elseif ($raw[0] -eq 0x7B -and $raw[1] -eq 0x00) {
    $baseText = [System.Text.Encoding]::Unicode.GetString($raw); $enc = "utf16"
} else {
    $baseText = [System.Text.Encoding]::UTF8.GetString($raw); $enc = "utf8"
}

Write-Host ("  Blank layout: {0} chars, enc={1}" -f $baseText.Length, $enc) -ForegroundColor Gray

# Reemplazar sections con nuestra Pagina 1
$si = $baseText.IndexOf('"sections"')
if ($si -ge 0) {
    $ai = $baseText.IndexOf('[', $si); $d = 0; $ae = $ai
    for ($i = $ai; $i -lt $baseText.Length; $i++) {
        if ($baseText[$i] -eq '[') { $d++ }
        if ($baseText[$i] -eq ']') { $d--; if ($d -eq 0) { $ae = $i; break } }
    }
    $merged = $baseText.Substring(0, $ai) + $page1 + $baseText.Substring($ae + 1)
} else {
    $close  = $baseText.LastIndexOf('}')
    $merged = $baseText.Substring(0, $close) + ',"sections":' + $page1 + '}'
}

Write-Host ("  Layout final: {0} chars" -f $merged.Length) -ForegroundColor Gray

if ($enc -eq "utf16bom") { $bom = [byte[]]@(0xFF,0xFE); $lb = $bom + [System.Text.Encoding]::Unicode.GetBytes($merged) }
elseif ($enc -eq "utf16")  { $lb = [System.Text.Encoding]::Unicode.GetBytes($merged) }
else                        { $lb = [System.Text.Encoding]::UTF8.GetBytes($merged) }

# ── Copiar Sistema_Tickets.pbix → output y reemplazar entradas ──
Write-Host "Construyendo $outputPbix ..." -ForegroundColor Cyan
Copy-Item $modelPbix $outputPbix -Force

$allBytes = [System.Collections.Generic.Dictionary[string,byte[]]]::new()
$zr2 = [System.IO.Compression.ZipFile]::OpenRead($outputPbix)
foreach ($e in $zr2.Entries) {
    if ($e.FullName -ne "Report/Layout") {
        $m2 = New-Object System.IO.MemoryStream
        $s2 = $e.Open(); $s2.CopyTo($m2); $s2.Close()
        $allBytes[$e.FullName] = $m2.ToArray(); $m2.Dispose()
    }
}
$zr2.Dispose()

# Recrear ZIP en modo Create (evita corrupcion de modo Update)
Remove-Item $outputPbix -Force
$zout = [System.IO.Compression.ZipFile]::Open($outputPbix, 'Create')
try {
    foreach ($k in $allBytes.Keys) {
        $ne = $zout.CreateEntry($k, [System.IO.Compression.CompressionLevel]::Optimal)
        $ns = $ne.Open(); $ns.Write($allBytes[$k], 0, $allBytes[$k].Length); $ns.Close()
    }
    $le2 = $zout.CreateEntry("Report/Layout", [System.IO.Compression.CompressionLevel]::Optimal)
    $ls2 = $le2.Open(); $ls2.Write($lb, 0, $lb.Length); $ls2.Close()
} finally { $zout.Dispose() }

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " Generado: Sistema_Tickets_p1.pbix" -ForegroundColor Green
Write-Host " Pagina 1 con 6 KPI cards:" -ForegroundColor Green
Write-Host "   Total Tickets | Activos | Cerrados" -ForegroundColor White
Write-Host "   Vencidos SLA  | Sin Asignar | Criticos" -ForegroundColor White
Write-Host "=============================================" -ForegroundColor Green

$resp = Read-Host "`nAbrir Sistema_Tickets_p1.pbix ahora? (s/n)"
if ($resp -eq 's') {
    $pbi = @("C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe",
             (& { $p=Get-AppxPackage "Microsoft.MicrosoftPowerBIDesktop" -EA SilentlyContinue; if($p){Join-Path $p.InstallLocation "bin\PBIDesktop.exe"}})) |
           Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if ($pbi) { Start-Process $pbi -ArgumentList "`"$outputPbix`"" }
}
