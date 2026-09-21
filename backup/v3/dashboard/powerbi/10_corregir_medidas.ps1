# Corrige las medidas con sintaxis NOT IN que PBI Desktop no acepta.
# Reemplaza con filtros <> equivalentes y universalmente soportados.

$ErrorActionPreference = "Stop"

# Obtener puerto AS
$pbiToolsExe = Get-ChildItem "$env:LOCALAPPDATA\pbi-tools" -Filter "pbi-tools.exe" -EA SilentlyContinue | Select-Object -First 1
$pbiPort = $null
if ($pbiToolsExe) {
    $infoRaw = & $pbiToolsExe.FullName info 2>&1 | Out-String
    $json    = [regex]::Match($infoRaw, '(?s)\{.*\}')
    if ($json.Success) {
        try { $pbiPort = [int]($json.Value | ConvertFrom-Json).pbiSessions[0].port } catch { }
    }
}
if (-not $pbiPort) { $pbiPort = [int](Read-Host "Puerto AS (pbi-tools info)") }
Write-Host "Puerto AS: $pbiPort" -ForegroundColor Green

# Cargar AMO desde Tabular Editor 2 (bundled)
$amoTabDll = Join-Path $PSScriptRoot "_tabular_editor\Microsoft.AnalysisServices.Tabular.dll"
Add-Type -Path $amoTabDll -ErrorAction Stop
Write-Host "AMO cargado." -ForegroundColor Green

# Conectar
$server = New-Object Microsoft.AnalysisServices.Tabular.Server
$server.Connect("localhost:$pbiPort")
$db     = $server.Databases[0]
$model  = $db.Model

$medidasTable = $model.Tables | Where-Object { $_.Name -eq "_Medidas" } | Select-Object -First 1
if (-not $medidasTable) { Write-Error "Tabla _Medidas no encontrada." }

# Medidas corregidas (NOT IN -> filtros <> equivalentes)
$correcciones = @{
    "Tickets Activos" = @{
        expr = "CALCULATE(COUNTROWS(tickets), tickets[estado_id] <> 8, tickets[estado_id] <> 9)"
        fmt  = "#,0"
    }
    "Sin Asignar" = @{
        expr = "CALCULATE(COUNTROWS(tickets), ISBLANK(tickets[admin_id]), tickets[estado_id] <> 8, tickets[estado_id] <> 9)"
        fmt  = "#,0"
    }
    "Criticos Activos" = @{
        expr = "CALCULATE(COUNTROWS(tickets), tickets[prioridad_id] = 4, tickets[estado_id] <> 8, tickets[estado_id] <> 9)"
        fmt  = "#,0"
    }
}

# Corregir tambien medidas que dependen de Tickets Activos
$dependientes = @{
    "Tickets Cerrados"   = @{ expr = "CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 8)"; fmt = "#,0" }
    "Tickets Rechazados" = @{ expr = "CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 9)"; fmt = "#,0" }
    "Con Gasto"          = @{ expr = "CALCULATE(COUNTROWS(tickets), tickets[genera_gasto] = 1)"; fmt = "#,0" }
}

$todasCorrecciones = $correcciones + $dependientes

Write-Host "Corrigiendo medidas..." -ForegroundColor Cyan
foreach ($nombre in $todasCorrecciones.Keys) {
    $m = $medidasTable.Measures | Where-Object { $_.Name -eq $nombre } | Select-Object -First 1
    if ($m) {
        $m.Expression  = $todasCorrecciones[$nombre].expr
        $m.FormatString = $todasCorrecciones[$nombre].fmt
        Write-Host "  Corregido: $nombre" -ForegroundColor Green
    } else {
        Write-Host "  No encontrado: $nombre (se omite)" -ForegroundColor Yellow
    }
}

$model.SaveChanges()
$server.Disconnect()

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " Medidas corregidas exitosamente." -ForegroundColor Green
Write-Host " En Power BI Desktop:" -ForegroundColor Green
Write-Host "   Inicio -> Actualizar (refresca el modelo)" -ForegroundColor White
Write-Host "   Los triangulos deberian desaparecer." -ForegroundColor White
Write-Host "   El campo 'x' se resuelve solo tras el refresh." -ForegroundColor White
Write-Host "=============================================" -ForegroundColor Green
