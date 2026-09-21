# Agrega medidas DAX directamente via AMO (sin Tabular Editor).
# Requiere: Power BI Desktop abierto con el modelo cargado.

$ErrorActionPreference = "Stop"

# Obtener puerto AS
$pbiToolsExe = Get-ChildItem "$env:LOCALAPPDATA\pbi-tools" -Filter "pbi-tools.exe" -EA SilentlyContinue | Select-Object -First 1
$pbiPort = $null

if ($pbiToolsExe) {
    $infoRaw = & $pbiToolsExe.FullName info 2>&1 | Out-String
    $json    = [regex]::Match($infoRaw, '(?s)\{.*\}')
    if ($json.Success) {
        try {
            $info    = $json.Value | ConvertFrom-Json
            $session = $info.pbiSessions | Select-Object -First 1
            if ($session) { $pbiPort = [int]$session.port }
        } catch { }
    }
}

if (-not $pbiPort) {
    $pbiPort = [int](Read-Host "Ingresa el puerto de pbi-tools info")
}
Write-Host "Puerto AS: $pbiPort" -ForegroundColor Green

# Cargar AMO — buscar en Tabular Editor 2 (bundled) y en PBI Desktop
$binDirs = @(
    (Join-Path $PSScriptRoot "_tabular_editor"),          # TE2 bundlea los DLLs de AMO
    "C:\Program Files\Microsoft Power BI Desktop\bin",
    "C:\Program Files (x86)\Microsoft Power BI Desktop\bin"
)
$pkg = Get-AppxPackage -Name "Microsoft.MicrosoftPowerBIDesktop" -EA SilentlyContinue
if ($pkg) { $binDirs = @((Join-Path $pkg.InstallLocation "bin")) + $binDirs }

$amoLoaded = $false
foreach ($dir in $binDirs) {
    $dll = Join-Path $dir "Microsoft.AnalysisServices.Tabular.dll"
    if (Test-Path $dll) {
        try {
            Add-Type -Path $dll -ErrorAction Stop
            $amoLoaded = $true
            Write-Host "AMO cargado desde: $dir" -ForegroundColor Green
            break
        } catch { }
    }
}

if (-not $amoLoaded) {
    Write-Error "No se encontro Microsoft.AnalysisServices.Tabular.dll. Agrega las medidas manualmente siguiendo docs\power_bi_dashboard.md (Paso 6)."
}

# Conectar al AS de PBI Desktop
Write-Host "Conectando a localhost:$pbiPort ..." -ForegroundColor Cyan
$server = New-Object Microsoft.AnalysisServices.Tabular.Server
$server.Connect("localhost:$pbiPort")

$db    = $server.Databases[0]
$model = $db.Model
Write-Host "Base de datos: $($db.Name)" -ForegroundColor Green

# Crear tabla _Medidas si no existe
# Tables es una coleccion AMO — se busca con Find() o filtrando
$medidasTable = $model.Tables | Where-Object { $_.Name -eq "_Medidas" } | Select-Object -First 1

if (-not $medidasTable) {
    Write-Host "Creando tabla _Medidas..." -ForegroundColor Cyan

    $newTable = New-Object Microsoft.AnalysisServices.Tabular.Table
    $newTable.Name = "_Medidas"

    $partition = New-Object Microsoft.AnalysisServices.Tabular.Partition
    $partition.Name = "_Medidas-part"

    $source = New-Object Microsoft.AnalysisServices.Tabular.CalculatedPartitionSource
    $source.Expression = 'DATATABLE("x", STRING, {{""}})'
    $partition.Source = $source

    $newTable.Partitions.Add($partition)
    $model.Tables.Add($newTable)
    $model.SaveChanges()

    $medidasTable = $model.Tables | Where-Object { $_.Name -eq "_Medidas" } | Select-Object -First 1
    Write-Host "Tabla _Medidas creada." -ForegroundColor Green
} else {
    Write-Host "Tabla _Medidas ya existe." -ForegroundColor Gray
}

# Definicion de medidas: nombre -> [expresion, formato]
$medidas = [ordered]@{
    "Total Tickets"          = @("COUNTROWS(tickets)", "#,0")
    "Tickets Activos"        = @("CALCULATE(COUNTROWS(tickets), tickets[estado_id] NOT IN {8, 9})", "#,0")
    "Tickets Cerrados"       = @("CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 8)", "#,0")
    "Tickets Rechazados"     = @("CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 9)", "#,0")
    "Sin Asignar"            = @("CALCULATE(COUNTROWS(tickets), ISBLANK(tickets[admin_id]), tickets[estado_id] NOT IN {8, 9})", "#,0")
    "Criticos Activos"       = @("CALCULATE(COUNTROWS(tickets), tickets[prioridad_id] = 4, tickets[estado_id] NOT IN {8, 9})", "#,0")
    "Con Gasto"              = @("CALCULATE(COUNTROWS(tickets), tickets[genera_gasto] = 1)", "#,0")
    "Dentro SLA"             = @('CALCULATE(COUNTROWS(v_sla_cumplimiento), v_sla_cumplimiento[resultado_sla] = "dentro_sla")', "#,0")
    "Fuera SLA"              = @('CALCULATE(COUNTROWS(v_sla_cumplimiento), v_sla_cumplimiento[resultado_sla] = "fuera_sla")', "#,0")
    "Pct Cumplimiento SLA"   = @("DIVIDE([Dentro SLA], [Dentro SLA] + [Fuera SLA], BLANK())", "0.0%")
    "Vencidos SLA"           = @('CALCULATE(COUNTROWS(v_tickets_pendientes), v_tickets_pendientes[sla_estado] = "vencido")', "#,0")
    "En Riesgo SLA"          = @('CALCULATE(COUNTROWS(v_tickets_pendientes), v_tickets_pendientes[sla_estado] = "en_riesgo")', "#,0")
    "Promedio Resolucion h"  = @("AVERAGEX(FILTER(tickets, tickets[estado_id] = 8 && NOT ISBLANK(tickets[closed_at])), DATEDIFF(tickets[created_at], tickets[closed_at], HOUR))", "0.0")
    "Creados Ultimos 30d"    = @("CALCULATE([Total Tickets], DATESINPERIOD(tickets[created_at], TODAY(), -30, DAY))", "#,0")
    "Cerrados Ultimos 30d"   = @("CALCULATE([Tickets Cerrados], DATESINPERIOD(tickets[closed_at], TODAY(), -30, DAY))", "#,0")
    "Tasa Resolucion"        = @("DIVIDE([Tickets Cerrados], [Total Tickets], 0)", "0.0%")
    "Color SLA"              = @('SWITCH(SELECTEDVALUE(v_tickets_pendientes[sla_estado]), "vencido", "#DC2626", "en_riesgo", "#D97706", "ok", "#16A34A", "sin_sla", "#94A3B8", "#94A3B8")', "")
}

Write-Host "Agregando $($medidas.Count) medidas..." -ForegroundColor Cyan
$count = 0
foreach ($nombre in $medidas.Keys) {
    $expr = $medidas[$nombre][0]
    $fmt  = $medidas[$nombre][1]

    # Measures tambien usa coleccion AMO — buscar por nombre con Where-Object
    $existing = $medidasTable.Measures | Where-Object { $_.Name -eq $nombre } | Select-Object -First 1

    if ($existing) {
        $existing.Expression = $expr
        if ($fmt) { $existing.FormatString = $fmt }
        Write-Host "  Actualizado: $nombre" -ForegroundColor Gray
    } else {
        $m = New-Object Microsoft.AnalysisServices.Tabular.Measure
        $m.Name       = $nombre
        $m.Expression = $expr
        if ($fmt) { $m.FormatString = $fmt }
        $medidasTable.Measures.Add($m)
        Write-Host "  Creado: $nombre" -ForegroundColor Green
    }
    $count++
}

$model.SaveChanges()
$server.Disconnect()

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " $count medidas agregadas exitosamente" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "SIGUIENTE PASO en Power BI Desktop:" -ForegroundColor Yellow
Write-Host " 1. Ctrl+S para guardar" -ForegroundColor White
Write-Host " 2. Vista 'Modelo' -> crear las relaciones (docs\power_bi_dashboard.md Paso 4)" -ForegroundColor White
Write-Host " 3. Crear las paginas del reporte (docs\power_bi_dashboard.md Paso 7)" -ForegroundColor White
