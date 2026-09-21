# ============================================================
#  Deploy modelo → Power BI Desktop via Tabular Editor 2
#
#  Flujo:
#    1. Descarga Tabular Editor 2 (gratuito, open source)
#    2. Abre _blank.pbix en Power BI Desktop
#    3. Obtiene el puerto del Analysis Services de PBI Desktop
#    4. Despliega model.bim al PBI Desktop en ejecucion
#    5. Usuario guarda el .pbix con Ctrl+S
#
#  Resultado: .pbix con las 22 tablas, 17 medidas y 11 relaciones.
#  El reporte se configura siguiendo docs/power_bi_dashboard.md
# ============================================================

$ErrorActionPreference = "Stop"
$projectDir = $PSScriptRoot
$modelBim   = Join-Path $projectDir "model.bim"
$blankPbix  = Join-Path $projectDir "_blank.pbix"
$te2Dir     = Join-Path $projectDir "_tabular_editor"
$te2Exe     = Join-Path $te2Dir "TabularEditor.exe"

if (-not (Test-Path $modelBim)) { Write-Error "No se encontro model.bim en $projectDir" }
if (-not (Test-Path $blankPbix)) { Write-Error "No se encontro _blank.pbix. Ejecuta primero 2_compilar.ps1 (el blank se crea en ese paso)." }

# ── 1. Detectar Power BI Desktop ───────────────────────────
function Find-PBIDesktop {
    $pkg = Get-AppxPackage -Name "Microsoft.MicrosoftPowerBIDesktop" -ErrorAction SilentlyContinue
    if ($pkg) {
        $p = Join-Path $pkg.InstallLocation "bin\PBIDesktop.exe"
        if (Test-Path $p) { return $p }
    }
    @(
        "C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe",
        "C:\Program Files (x86)\Microsoft Power BI Desktop\bin\PBIDesktop.exe"
    ) | Where-Object { Test-Path $_ } | Select-Object -First 1
}

$pbiDesktop = Find-PBIDesktop
if (-not $pbiDesktop) { Write-Error "Power BI Desktop no encontrado." }
Write-Host "PBI Desktop: $pbiDesktop" -ForegroundColor Green

$pbiInstallDir = Split-Path (Split-Path $pbiDesktop)  # bin -> PBIDesktop dir
$pbiBinDir     = Split-Path $pbiDesktop

# ── 2. Localizar pbi-tools (para obtener el puerto) ────────
function Find-InPath ($name) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $all = (($env:PATH -split ";") + ([Environment]::GetEnvironmentVariable("PATH","User") -split ";") + "$env:LOCALAPPDATA\pbi-tools") | Select-Object -Unique
    foreach ($d in $all) { $p = Join-Path $d $name; if (Test-Path $p) { return $p } }
    return $null
}

$pbiToolsExe = Find-InPath "pbi-tools.exe"

# ── 3. Descargar Tabular Editor 2 si no existe ─────────────
if (-not (Test-Path $te2Exe)) {
    Write-Host "`nDescargando Tabular Editor 2 (gratuito)..." -ForegroundColor Cyan
    $te2Release = Invoke-RestMethod "https://api.github.com/repos/TabularEditor/TabularEditor/releases/latest"
    $te2Asset   = $te2Release.assets |
                  Where-Object { $_.name -like "*.zip" } |
                  Select-Object -First 1

    if (-not $te2Asset) { Write-Error "No se encontro asset ZIP en el release de Tabular Editor 2." }

    Write-Host "Descargando: $($te2Asset.name)" -ForegroundColor Gray
    $zipPath = "$env:TEMP\TabularEditor2.zip"
    Invoke-WebRequest -Uri $te2Asset.browser_download_url -OutFile $zipPath -UseBasicParsing

    if (-not (Test-Path $te2Dir)) { New-Item -ItemType Directory $te2Dir | Out-Null }
    Expand-Archive $zipPath $te2Dir -Force
    Remove-Item $zipPath -Force

    if (-not (Test-Path $te2Exe)) { Write-Error "TabularEditor.exe no encontrado en $te2Dir tras la extraccion." }
    Write-Host "Tabular Editor 2 instalado en $te2Dir" -ForegroundColor Green
} else {
    Write-Host "Tabular Editor 2 ya disponible: $te2Exe" -ForegroundColor Green
}

# ── 4. Abrir _blank.pbix en PBI Desktop ────────────────────
Write-Host ""
$runningPBI = Get-Process -Name "PBIDesktop" -ErrorAction SilentlyContinue
if (-not $runningPBI) {
    Write-Host "Abriendo _blank.pbix en Power BI Desktop..." -ForegroundColor Cyan
    Start-Process $pbiDesktop -ArgumentList "`"$blankPbix`""
    Write-Host "Esperando que cargue (15 segundos)..." -ForegroundColor Gray
    Start-Sleep -Seconds 15
} else {
    Write-Host "Power BI Desktop ya esta abierto." -ForegroundColor Green
}

# ── 5. Obtener puerto del AS de PBI Desktop ─────────────────
Write-Host "`nObteniendo puerto del Analysis Services..." -ForegroundColor Cyan
$pbiPort = $null

if ($pbiToolsExe) {
    $infoRaw   = & $pbiToolsExe info 2>&1 | Out-String
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
    Write-Host ""
    Write-Host "No se detecto el puerto automaticamente." -ForegroundColor Yellow
    Write-Host "Ejecuta en OTRA ventana de PowerShell:" -ForegroundColor Cyan
    Write-Host "  & '$pbiToolsExe' info" -ForegroundColor White
    Write-Host "Busca el campo 'port' en 'pbiSessions'." -ForegroundColor Cyan
    $pbiPort = [int](Read-Host "Ingresa el numero de puerto")
}
Write-Host "Puerto AS: $pbiPort" -ForegroundColor Green

# ── 6. Obtener nombre de la base de datos ──────────────────
Write-Host "`nObteniendo nombre de la base de datos..." -ForegroundColor Cyan

# Intentar obtenerlo desde pbi-tools info (campo databaseName en la sesion)
$dbName = $null
if ($pbiToolsExe) {
    $infoRaw2   = & $pbiToolsExe info 2>&1 | Out-String
    $jsonMatch2 = [regex]::Match($infoRaw2, '(?s)\{.*\}')
    if ($jsonMatch2.Success) {
        try {
            $info2    = $jsonMatch2.Value | ConvertFrom-Json
            $session2 = $info2.pbiSessions | Select-Object -First 1
            if ($session2 -and $session2.databaseName) {
                $dbName = $session2.databaseName
                Write-Host "Nombre de BD desde pbi-tools info: $dbName" -ForegroundColor Green
            }
        } catch { }
    }
}

# Buscar AMO en ubicaciones alternativas
if (-not $dbName) {
    $amoCandidates = @(
        (Join-Path $pbiBinDir "Microsoft.AnalysisServices.Tabular.dll"),
        (Join-Path $pbiInstallDir "Microsoft.AnalysisServices.Tabular.dll"),
        "C:\Windows\Microsoft.NET\assembly\GAC_MSIL\Microsoft.AnalysisServices.Tabular\*\Microsoft.AnalysisServices.Tabular.dll",
        "$env:LOCALAPPDATA\Programs\Common\Microsoft\AMO\*\Microsoft.AnalysisServices.Tabular.dll"
    )
    $amoTabDll = $amoCandidates | ForEach-Object { Resolve-Path $_ -ErrorAction SilentlyContinue } | Select-Object -First 1

    if ($amoTabDll) {
        try {
            Add-Type -Path $amoTabDll.Path -ErrorAction Stop
            $server = New-Object Microsoft.AnalysisServices.Tabular.Server
            $server.Connect("localhost:$pbiPort")
            $dbName = $server.Databases[0].Name
            $server.Disconnect()
            Write-Host "Nombre de BD via AMO: $dbName" -ForegroundColor Green
        } catch {
            Write-Host "AMO disponible pero fallo la conexion: $_" -ForegroundColor Yellow
        }
    }
}

# Fallback: "Model" es el nombre por defecto en PBI Desktop para archivos en blanco
if (-not $dbName) {
    $dbName = "Model"
    Write-Host "Usando nombre por defecto: $dbName" -ForegroundColor Yellow
}

Write-Host "Base de datos destino: $dbName" -ForegroundColor Green

# ── 7. Desplegar model.bim via Tabular Editor 2 CLI ────────
Write-Host ""
Write-Host "Desplegando model.bim en Power BI Desktop..." -ForegroundColor Cyan
Write-Host "  Modelo: $modelBim"
Write-Host "  Server: localhost:$pbiPort"
Write-Host "  Base de datos: $dbName"
Write-Host ""

# TE2 CLI: TabularEditor.exe <bimFile> <server> <database> -D -O -P
$te2Args = @("`"$modelBim`"", "localhost:$pbiPort", "`"$dbName`"", "-D", "-O", "-P")
Write-Host "Ejecutando: TabularEditor.exe $($te2Args -join ' ')" -ForegroundColor DarkGray

$result   = & $te2Exe $modelBim "localhost:$pbiPort" $dbName -D -O -P 2>&1
$exitCode = $LASTEXITCODE

Write-Host ($result | Out-String)

if ($exitCode -ne 0) {
    Write-Host ""
    Write-Host "El despliegue directo fallo. Intentando sin nombre de BD..." -ForegroundColor Yellow
    $result   = & $te2Exe $modelBim "localhost:$pbiPort" -D -O 2>&1
    $exitCode = $LASTEXITCODE
    Write-Host ($result | Out-String)
}

if ($exitCode -ne 0) {
    Write-Host ""
    Write-Warning @"
El despliegue automatico fallo.

ALTERNATIVA MANUAL (5 minutos):
  1. Abre Tabular Editor 2 desde: $te2Exe
  2. File -> Open -> Browse -> selecciona: $modelBim
  3. Model -> Deploy...
     - Target server: localhost:$pbiPort
     - Target database: $dbName
     - Opciones: Deploy structure + Overwrite
  4. Clic en Deploy
  5. Vuelve a Power BI Desktop y guarda: Ctrl+S
"@
} else {
    Write-Host ""
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host " MODELO DESPLEGADO EXITOSAMENTE" -ForegroundColor Green
    Write-Host "=========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "AHORA en Power BI Desktop:" -ForegroundColor Yellow
    Write-Host " 1. Ve a Inicio -> Actualizar" -ForegroundColor White
    Write-Host " 2. Ingresa credenciales ODBC:" -ForegroundColor White
    Write-Host "    Usuario: root   Contrasena: (vacia)" -ForegroundColor White
    Write-Host " 3. Guarda con Ctrl+S como Sistema_Tickets.pbix" -ForegroundColor White
    Write-Host ""
    Write-Host "Para el reporte (paginas y graficos):" -ForegroundColor Yellow
    Write-Host " Sigue el archivo: docs\power_bi_dashboard.md" -ForegroundColor White
    Write-Host " Seccion 'Paso 7 - Diseno del tablero'" -ForegroundColor White
    Write-Host "=========================================" -ForegroundColor Green
}

# Abrir TE2 GUI si fallo el CLI
if ($exitCode -ne 0) {
    $resp = Read-Host "`nAbrir Tabular Editor 2 para el despliegue manual? (s/n)"
    if ($resp -eq 's') { Start-Process $te2Exe }
}
