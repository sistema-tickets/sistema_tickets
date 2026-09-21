# Pagina 1 con 6 KPI cards.
# FIX v4: Fuerza DataModel como Stored (method=0x0000) via reflection,
# porque .NET CreateEntry(NoCompression) escribe Deflate-0 en lugar de Store.

$ErrorActionPreference = "Stop"
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression

$projectDir = $PSScriptRoot
$blankPbix  = Join-Path $projectDir "_blank.pbix"
$outputPbix = Join-Path $projectDir "Sistema_Tickets_p1.pbix"

if (-not (Test-Path $blankPbix)) { Write-Error "_blank.pbix no encontrado." }

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

# Funcion para forzar metodo Stored via reflection (evita que .NET use Deflate-0)
function Set-ZipEntryStored {
    param([System.IO.Compression.ZipArchiveEntry]$entry)
    $type = $entry.GetType()
    # El campo interno _compressionLevel o _method según versión .NET
    $field = $type.GetField("_compressionLevel",
        [System.Reflection.BindingFlags]"NonPublic,Instance")
    if ($field) {
        $field.SetValue($entry, [System.IO.Compression.CompressionLevel]::NoCompression)
    }
    # Intentar campo _storedUncompressedSize o _generalPurposeBitFlag
    $mfield = $type.GetField("_compressionMethod",
        [System.Reflection.BindingFlags]"NonPublic,Instance")
    if (-not $mfield) {
        $mfield = $type.GetField("CompressionMethod",
            [System.Reflection.BindingFlags]"NonPublic,Instance")
    }
    if ($mfield) {
        $mfield.SetValue($entry, [short]0)  # 0 = Stored
        Write-Host "    -> CompressionMethod forzado a 0 (Stored) via reflection" -ForegroundColor DarkGreen
    } else {
        Write-Host "    -> Campo CompressionMethod no encontrado, usando NoCompression" -ForegroundColor Yellow
    }
}

# Leer todas las entradas de _blank.pbix preservando orden
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

# Leer y decodificar Report/Layout
$layoutBytes = ($entries | Where-Object { $_.Item1 -eq "Report/Layout" } | Select-Object -First 1).Item2

if ($layoutBytes[0] -eq 0xFF -and $layoutBytes[1] -eq 0xFE) {
    $layoutText = [System.Text.Encoding]::Unicode.GetString($layoutBytes, 2, $layoutBytes.Length - 2); $enc = "utf16bom"
} elseif ($layoutBytes[0] -eq 0x7B -and $layoutBytes[1] -eq 0x00) {
    $layoutText = [System.Text.Encoding]::Unicode.GetString($layoutBytes); $enc = "utf16"
} else {
    $layoutText = [System.Text.Encoding]::UTF8.GetString($layoutBytes); $enc = "utf8"
}

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

Write-Host ("Layout final: {0} chars, {1} bytes (enc={2})" -f $merged.Length, $lb.Length, $enc) -ForegroundColor Gray

# Construir ZIP con DataModel Stored via reflection
Write-Host "`nConstruyendo $outputPbix ..." -ForegroundColor Cyan
if (Test-Path $outputPbix) { Remove-Item $outputPbix -Force }

$zout = [System.IO.Compression.ZipFile]::Open($outputPbix, 'Create')
try {
    foreach ($t in $entries) {
        if ($t.Item1 -eq "Report/Layout") { continue }

        if ($t.Item1 -eq "DataModel") {
            # Crear entrada sin compresion y forzar metodo Stored via reflection
            $ne = $zout.CreateEntry($t.Item1, [System.IO.Compression.CompressionLevel]::NoCompression)
            Set-ZipEntryStored $ne
            Write-Host ("  + {0,-45} [Stored via reflection, {1} bytes]" -f $t.Item1, $t.Item2.Length) -ForegroundColor Cyan
        } else {
            $ne = $zout.CreateEntry($t.Item1, [System.IO.Compression.CompressionLevel]::Optimal)
            Write-Host ("  + {0,-45} [Deflate]" -f $t.Item1) -ForegroundColor Gray
        }
        $ns = $ne.Open(); $ns.Write($t.Item2, 0, $t.Item2.Length); $ns.Close()
    }
    $le = $zout.CreateEntry("Report/Layout", [System.IO.Compression.CompressionLevel]::Optimal)
    $ls = $le.Open(); $ls.Write($lb, 0, $lb.Length); $ls.Close()
    Write-Host ("  + Report/Layout                               [Deflate, {0} bytes]" -f $lb.Length) -ForegroundColor Green
} finally { $zout.Dispose() }

# Verificar metodo de compresion en salida
Write-Host "`nVerificando metodos en output:" -ForegroundColor Cyan
$zv = [System.IO.Compression.ZipFile]::OpenRead($outputPbix)
foreach ($e in $zv.Entries | Sort-Object FullName) {
    $method = if ($e.CompressedLength -eq $e.Length) { "Stored" } else { "Deflate" }
    Write-Host ("  {0,-45} {1,8}b comp={2,8}b [{3}]" -f $e.FullName, $e.Length, $e.CompressedLength, $method) -ForegroundColor Gray
}
$zv.Dispose()

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " Generado: Sistema_Tickets_p1.pbix" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green

$resp = Read-Host "`nAbrir Sistema_Tickets_p1.pbix ahora? (s/n)"
if ($resp -eq 's') {
    $pbi = @("C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe",
             (& { $p=Get-AppxPackage "Microsoft.MicrosoftPowerBIDesktop" -EA SilentlyContinue; if($p){Join-Path $p.InstallLocation "bin\PBIDesktop.exe"}})) |
           Where-Object { $_ -and (Test-Path $_) } | Select-Object -First 1
    if ($pbi) { Start-Process $pbi -ArgumentList "`"$outputPbix`"" }
    else { Write-Host "PBI Desktop no encontrado. Abre el archivo manualmente." -ForegroundColor Yellow }
}
