# Setup correcto: PBI Desktop crea el Mashup, TE2 agrega medidas y relaciones.
# Esta es la unica forma confiable con PBI Desktop V3 (2.153.x).

$ErrorActionPreference = "Stop"
$projectDir  = $PSScriptRoot
$blankPbix   = Join-Path $projectDir "_blank.pbix"
$outputPbix  = Join-Path $projectDir "Sistema_Tickets.pbix"
$te2Exe      = Join-Path $projectDir "_tabular_editor\TabularEditor.exe"

function Find-PBIDesktop {
    $pkg = Get-AppxPackage -Name "Microsoft.MicrosoftPowerBIDesktop" -ErrorAction SilentlyContinue
    if ($pkg) { $p = Join-Path $pkg.InstallLocation "bin\PBIDesktop.exe"; if (Test-Path $p) { return $p } }
    @("C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe",
      "C:\Program Files (x86)\Microsoft Power BI Desktop\bin\PBIDesktop.exe"
    ) | Where-Object { Test-Path $_ } | Select-Object -First 1
}
$pbiDesktop = Find-PBIDesktop

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " PASO 1: Conectar tablas ODBC en Power BI Desktop" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host " Power BI Desktop se abrira ahora con el archivo en blanco." -ForegroundColor White
Write-Host " Sigue estos pasos (aprox. 10 minutos):" -ForegroundColor White
Write-Host ""
Write-Host " 1. Espera que cargue completamente" -ForegroundColor Yellow
Write-Host " 2. Inicio -> Obtener datos -> ODBC" -ForegroundColor Yellow
Write-Host " 3. En 'Nombre del origen de datos (DSN)': selecciona LOCAL" -ForegroundColor Yellow
Write-Host " 4. Clic en Aceptar" -ForegroundColor Yellow
Write-Host " 5. Si pide credenciales: Base de datos, usuario=root, contrasena=(vacia)" -ForegroundColor Yellow
Write-Host " 6. En el Navegador, expande 'bd_tickets' y marca TODAS estas tablas/vistas:" -ForegroundColor Yellow
Write-Host ""

$items = @(
    "tickets", "estados", "prioridades", "tipos_asunto", "tipos_solicitud",
    "regiones", "sitios", "lineas_negocio", "asuntos", "sla_configuracion", "usuarios",
    "v_tickets_pendientes", "v_sla_cumplimiento", "v_metricas_admin",
    "v_dash_creados_vs_cerrados", "v_dash_por_sitio", "v_dash_por_linea_negocio",
    "v_dash_por_tipo_asunto", "v_promedio_por_prioridad", "v_promedio_por_asunto"
)
foreach ($item in $items) { Write-Host "    [X] $item" -ForegroundColor White }

Write-Host ""
Write-Host " 7. Clic en 'Cargar' (boton inferior)" -ForegroundColor Yellow
Write-Host " 8. Espera que terminen de cargar todas las tablas" -ForegroundColor Yellow
Write-Host " 9. Archivo -> Guardar como -> guarda aqui:" -ForegroundColor Yellow
Write-Host "    $outputPbix" -ForegroundColor Cyan
Write-Host "10. NO cierres Power BI Desktop" -ForegroundColor Yellow
Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan

Start-Process $pbiDesktop -ArgumentList "`"$blankPbix`""
Read-Host "Presiona Enter cuando hayas cargado las tablas y guardado el archivo (sin cerrar PBI Desktop)"

if (-not (Test-Path $outputPbix)) {
    Write-Error "No se encontro $outputPbix. Asegurate de haberlo guardado en esa ruta."
}
Write-Host "Archivo guardado: OK" -ForegroundColor Green

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " PASO 2: Agregar medidas y relaciones con Tabular Editor" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan

# Obtener puerto del AS de PBI Desktop
$pbiToolsExe = Get-ChildItem "$env:LOCALAPPDATA\pbi-tools" -Filter "pbi-tools.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
$pbiPort = $null

if ($pbiToolsExe) {
    $infoRaw   = & $pbiToolsExe.FullName info 2>&1 | Out-String
    $jsonMatch = [regex]::Match($infoRaw, '(?s)\{.*\}')
    if ($jsonMatch.Success) {
        try {
            $info    = $jsonMatch.Value | ConvertFrom-Json
            $session = $info.pbiSessions | Select-Object -First 1
            if ($session) { $pbiPort = [int]$session.port }
        } catch { }
    }
}

if (-not $pbiPort) {
    Write-Host "No se detecto el puerto automaticamente." -ForegroundColor Yellow
    $pbiPort = [int](Read-Host "Ejecuta 'pbi-tools info' en otra terminal y escribe el puerto")
}
Write-Host "Puerto AS: $pbiPort" -ForegroundColor Green

# Script C# / TMSL para agregar medidas via TE2
# Usamos TE2 CLI con script de C# para agregar medidas al modelo existente
$te2Script = @'
using System;
using TabularEditor.TOMWrapper;

var model = Model;

// Crear tabla _Medidas si no existe
Table medidasTable;
if (!model.Tables.ContainsKey("_Medidas")) {
    medidasTable = model.AddCalculatedTable("_Medidas", "DATATABLE(\"x\", STRING, {{\"\"}})", true);
    medidasTable.IsHidden = false;
} else {
    medidasTable = model.Tables["_Medidas"];
}

// Medidas
var medidas = new System.Collections.Generic.Dictionary<string, (string expr, string fmt)> {
    {"Total Tickets", ("COUNTROWS(tickets)", "#,0")},
    {"Tickets Activos", ("CALCULATE(COUNTROWS(tickets), tickets[estado_id] NOT IN {8, 9})", "#,0")},
    {"Tickets Cerrados", ("CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 8)", "#,0")},
    {"Tickets Rechazados", ("CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 9)", "#,0")},
    {"Sin Asignar", ("CALCULATE(COUNTROWS(tickets), ISBLANK(tickets[admin_id]), tickets[estado_id] NOT IN {8, 9})", "#,0")},
    {"Criticos Activos", ("CALCULATE(COUNTROWS(tickets), tickets[prioridad_id] = 4, tickets[estado_id] NOT IN {8, 9})", "#,0")},
    {"Con Gasto", ("CALCULATE(COUNTROWS(tickets), tickets[genera_gasto] = 1)", "#,0")},
    {"Dentro SLA", ("CALCULATE(COUNTROWS(v_sla_cumplimiento), v_sla_cumplimiento[resultado_sla] = \"dentro_sla\")", "#,0")},
    {"Fuera SLA", ("CALCULATE(COUNTROWS(v_sla_cumplimiento), v_sla_cumplimiento[resultado_sla] = \"fuera_sla\")", "#,0")},
    {"Pct Cumplimiento SLA", ("DIVIDE([Dentro SLA], [Dentro SLA] + [Fuera SLA], BLANK())", "0.0%")},
    {"Vencidos SLA", ("CALCULATE(COUNTROWS(v_tickets_pendientes), v_tickets_pendientes[sla_estado] = \"vencido\")", "#,0")},
    {"En Riesgo SLA", ("CALCULATE(COUNTROWS(v_tickets_pendientes), v_tickets_pendientes[sla_estado] = \"en_riesgo\")", "#,0")},
    {"Promedio Resolucion h", ("AVERAGEX(FILTER(tickets, tickets[estado_id] = 8 && NOT ISBLANK(tickets[closed_at])), DATEDIFF(tickets[created_at], tickets[closed_at], HOUR))", "0.0 \"h\"")},
    {"Creados Ultimos 30d", ("CALCULATE([Total Tickets], DATESINPERIOD(tickets[created_at], TODAY(), -30, DAY))", "#,0")},
    {"Cerrados Ultimos 30d", ("CALCULATE([Tickets Cerrados], DATESINPERIOD(tickets[closed_at], TODAY(), -30, DAY))", "#,0")},
    {"Tasa Resolucion", ("DIVIDE([Tickets Cerrados], [Total Tickets], 0)", "0.0%")},
    {"Color SLA", ("SWITCH(SELECTEDVALUE(v_tickets_pendientes[sla_estado]), \"vencido\", \"#DC2626\", \"en_riesgo\", \"#D97706\", \"ok\", \"#16A34A\", \"sin_sla\", \"#94A3B8\", \"#94A3B8\")", "")}
};

foreach (var m in medidas) {
    Measure mea;
    if (!medidasTable.Measures.ContainsKey(m.Key)) {
        mea = medidasTable.AddMeasure(m.Key, m.Value.expr);
    } else {
        mea = medidasTable.Measures[m.Key];
        mea.Expression = m.Value.expr;
    }
    if (!string.IsNullOrEmpty(m.Value.fmt)) mea.FormatString = m.Value.fmt;
}

Info("Medidas creadas/actualizadas: " + medidas.Count);
'@

$scriptFile = Join-Path $env:TEMP "te2_medidas.cs"
$te2Script | Set-Content $scriptFile -Encoding UTF8

if (Test-Path $te2Exe) {
    Write-Host "Ejecutando Tabular Editor 2 para agregar medidas..." -ForegroundColor Cyan

    # Obtener nombre de BD
    $dbName = "Model"
    try {
        $proc  = Get-Process -Name "PBIDesktop" | Select-Object -First 1
        if ($proc) {
            # Intentar obtener desde pbi-tools info
            if ($infoRaw) {
                $session2 = $info.pbiSessions | Select-Object -First 1
                if ($session2.databaseName) { $dbName = $session2.databaseName }
            }
        }
    } catch { }

    Write-Host "  Base de datos: $dbName" -ForegroundColor Gray
    $result = & $te2Exe "localhost:$pbiPort" $dbName -S $scriptFile 2>&1
    Write-Host ($result | Out-String)

    if ($LASTEXITCODE -eq 0) {
        Write-Host "Medidas agregadas exitosamente!" -ForegroundColor Green
    } else {
        Write-Host "Error al agregar medidas via CLI. Continua manualmente." -ForegroundColor Yellow
    }
    Remove-Item $scriptFile -ErrorAction SilentlyContinue
} else {
    Write-Host "Tabular Editor 2 no disponible. Las medidas se agregaran manualmente." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " PASO 3: Guardar y cerrar Power BI Desktop" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " En Power BI Desktop: Ctrl+S para guardar" -ForegroundColor White
Write-Host " Luego cierra Power BI Desktop" -ForegroundColor White
Read-Host "Presiona Enter cuando hayas guardado y cerrado PBI Desktop"

Write-Host ""
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " PASO 4: Inyectar el reporte (Report/Layout)" -ForegroundColor Cyan
Write-Host "======================================================" -ForegroundColor Cyan
Write-Host " Ejecutando 4_inyectar_reporte.ps1 ..." -ForegroundColor White

$injectScript = Join-Path $projectDir "4_inyectar_reporte.ps1"
if (Test-Path $injectScript) {
    & $injectScript
} else {
    Write-Host "Ejecuta manualmente: .\4_inyectar_reporte.ps1" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host " PROCESO COMPLETO" -ForegroundColor Green
Write-Host " Abre Sistema_Tickets.pbix en Power BI Desktop" -ForegroundColor Green
Write-Host " Inicio -> Actualizar (credenciales ODBC: root / vacio)" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
