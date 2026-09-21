# ============================================================
#  Instalador pbi-tools — compatible con PBI Desktop instalado
#  Busca el release especifico para la version de PBI Desktop.
# ============================================================

$ErrorActionPreference = "Stop"
$installDir = "$env:LOCALAPPDATA\pbi-tools"

# ── Detectar version de Power BI Desktop ───────────────────
function Get-PBIVersion {
    $pkg = Get-AppxPackage -Name "Microsoft.MicrosoftPowerBIDesktop" -ErrorAction SilentlyContinue
    if ($pkg) {
        $exe = Join-Path $pkg.InstallLocation "bin\PBIDesktop.exe"
        if (Test-Path $exe) { return (Get-Item $exe).VersionInfo.FileVersion }
    }
    $candidates = @(
        "C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe",
        "C:\Program Files (x86)\Microsoft Power BI Desktop\bin\PBIDesktop.exe"
    )
    foreach ($c in $candidates) {
        if (Test-Path $c) { return (Get-Item $c).VersionInfo.FileVersion }
    }
    return $null
}

$pbiVersion = Get-PBIVersion
if ($pbiVersion) {
    Write-Host "Power BI Desktop detectado: v$pbiVersion" -ForegroundColor Green
    # Extraer solo Major.Minor (ej: 2.153)
    $pbiMajorMinor = ($pbiVersion -split '\.')[0..1] -join '.'
    Write-Host "Buscando pbi-tools compatible con PBI Desktop $pbiMajorMinor.x ..." -ForegroundColor Cyan
} else {
    Write-Host "Power BI Desktop no detectado. Descargando ultima version de pbi-tools..." -ForegroundColor Yellow
    $pbiMajorMinor = $null
}

# ── Obtener todos los releases de GitHub (incluye pre-releases) ──
Write-Host "Consultando releases en GitHub..." -ForegroundColor Cyan
$allReleases = Invoke-RestMethod "https://api.github.com/repos/pbi-tools/pbi-tools/releases?per_page=30"

# ── Buscar release compatible con la version de PBI Desktop ──
$targetRelease = $null

if ($pbiMajorMinor) {
    # Buscar release cuyo tag o nombre contenga la version de PBI Desktop
    foreach ($rel in $allReleases) {
        $tag = $rel.tag_name
        $name = $rel.name
        if ($tag -match $pbiMajorMinor -or $name -match $pbiMajorMinor) {
            $targetRelease = $rel
            Write-Host "Release especifico encontrado: $tag" -ForegroundColor Green
            break
        }
    }
}

# Fallback: usar el release mas reciente
if (-not $targetRelease) {
    $targetRelease = $allReleases | Select-Object -First 1
    Write-Host "Usando release mas reciente: $($targetRelease.tag_name)" -ForegroundColor Yellow
    if ($pbiMajorMinor) {
        Write-Host "AVISO: No se encontro un release especifico para PBI Desktop $pbiMajorMinor.x" -ForegroundColor Yellow
        Write-Host "Si hay errores de compatibilidad, visita:" -ForegroundColor Yellow
        Write-Host "  https://github.com/pbi-tools/pbi-tools/releases" -ForegroundColor Cyan
        Write-Host "y busca manualmente el release para v$pbiVersion" -ForegroundColor Yellow
    }
}

# ── Descargar el ZIP del release ───────────────────────────
$asset = $targetRelease.assets |
         Where-Object { $_.name -like "*.zip" -and $_.name -notlike "*symbols*" } |
         Select-Object -First 1

if (-not $asset) {
    Write-Error "No se encontro asset ZIP en el release '$($targetRelease.tag_name)'. Descarga manualmente desde:`n  https://github.com/pbi-tools/pbi-tools/releases"
}

Write-Host ""
Write-Host "Descargando: $($asset.name)" -ForegroundColor Cyan
Write-Host "Release    : $($targetRelease.tag_name)" -ForegroundColor Cyan
Write-Host "URL        : $($asset.browser_download_url)" -ForegroundColor Gray

$zipPath = "$env:TEMP\pbi-tools.zip"
Invoke-WebRequest -Uri $asset.browser_download_url -OutFile $zipPath -UseBasicParsing

# ── Instalar ───────────────────────────────────────────────
Write-Host "Instalando en $installDir ..." -ForegroundColor Cyan
if (Test-Path $installDir) { Remove-Item $installDir -Recurse -Force }
Expand-Archive -Path $zipPath -DestinationPath $installDir -Force
Remove-Item $zipPath -Force

# ── Agregar al PATH ────────────────────────────────────────
$currentPath = [Environment]::GetEnvironmentVariable("PATH", "User")
if ($currentPath -notlike "*$installDir*") {
    [Environment]::SetEnvironmentVariable("PATH", "$currentPath;$installDir", "User")
    $env:PATH += ";$installDir"
    Write-Host "pbi-tools agregado al PATH del usuario." -ForegroundColor Green
}

# ── Verificar ──────────────────────────────────────────────
Write-Host ""
Write-Host "Verificando instalacion..." -ForegroundColor Cyan
$pbiToolsExe = Get-ChildItem $installDir -Filter "pbi-tools*.exe" | Select-Object -First 1
if (-not $pbiToolsExe) {
    Write-Error "No se encontro pbi-tools.exe en $installDir"
}

& $pbiToolsExe.FullName info 2>&1 | Select-Object -First 5 | Write-Host

Write-Host ""
Write-Host "pbi-tools instalado: $($targetRelease.tag_name)" -ForegroundColor Green
Write-Host "Ahora ejecuta: .\2_compilar.ps1" -ForegroundColor Yellow
