# Arranque al login (recomendado): la impresora predeterminada es la del usuario de caja.
# No hace falta admin: si el Programador de tareas niega acceso, usa la carpeta Inicio.
$ErrorActionPreference = "Stop"
if ($PSScriptRoot) {
    $AgentDir = $PSScriptRoot
} else {
    $AgentDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
if (-not $AgentDir) {
    $AgentDir = Get-Location
}
$AgentDir = [System.IO.Path]::GetFullPath($AgentDir)
Set-Location $AgentDir

$VenvPython = Join-Path $AgentDir ".venv\Scripts\pythonw.exe"
$Agent = Join-Path $AgentDir "print_agent.py"
$Req = Join-Path $AgentDir "requirements.txt"
$Example = Join-Path $AgentDir "config.example.ini"
$Config = Join-Path $AgentDir "config.ini"
$TaskName = "NissiImpresionCaja"
$StartupDir = [Environment]::GetFolderPath("Startup")
$StartupVbs = Join-Path $StartupDir "NissiImpresionCaja.vbs"
$HiddenVbs = Join-Path $AgentDir "start_hidden.vbs"

if (-not (Test-Path -LiteralPath $Req)) {
    throw @"
No esta requirements.txt en:
  $AgentDir

Tenés que copiar la carpeta completa agente_impresion (print_agent.py, requirements.txt, config.example.ini, este .ps1).
No ejecutes el script desde otra carpeta ni copies solo el .ps1.
"@
}

function Get-SystemPython {
    $cmd = Get-Command python -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    $cmd = Get-Command py -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }
    throw "No se encontro Python. Instala Python 3.10+ y marca 'Add python.exe to PATH'."
}

function Write-HiddenVbs {
    $py = $VenvPython.Replace("\", "\\")
    $script = $Agent.Replace("\", "\\")
    $work = $AgentDir.Replace("\", "\\")
    @"
Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "$work"
sh.Run """$py"" ""$script"" --serve", 0, False
"@ | Set-Content -LiteralPath $HiddenVbs -Encoding ASCII
}

$Python = Get-SystemPython
if (-not (Test-Path $VenvPython)) {
    Write-Host "Creando venv en $AgentDir\.venv"
    & $Python -m venv (Join-Path $AgentDir ".venv")
}
$Pip = Join-Path $AgentDir ".venv\Scripts\pip.exe"
& $Pip install -r "$Req"
if (-not (Test-Path $Config)) {
    Copy-Item $Example $Config
    Write-Host "Creado config.ini (impresora = predeterminada de Windows)"
}

Write-HiddenVbs
Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue

$registeredTask = $false
try {
    $Action = New-ScheduledTaskAction -Execute $VenvPython -Argument "`"$Agent`" --serve" -WorkingDirectory $AgentDir
    $Trigger = New-ScheduledTaskTrigger -AtLogOn -User $env:USERNAME
    $Principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited
    $Settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -DontStopOnIdleEnd `
        -RestartCount 3 `
        -RestartInterval (New-TimeSpan -Minutes 1) `
        -ExecutionTimeLimit ([TimeSpan]::Zero) `
        -MultipleInstances IgnoreNew
    Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Principal $Principal -Settings $Settings -Description "Nissi: imprime tickets y KuDE por el spooler local (127.0.0.1)" -Force | Out-Null
    Start-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    $registeredTask = $true
    Write-Host "Tarea $TaskName registrada (arranca al login)."
} catch {
    Write-Host "Programador de tareas: acceso denegado. Usando carpeta Inicio (no hace falta admin)."
    Copy-Item -LiteralPath $HiddenVbs -Destination $StartupVbs -Force
    Write-Host "Atajo en: $StartupVbs"
}

Get-CimInstance Win32_Process -ErrorAction SilentlyContinue |
    Where-Object { $_.CommandLine -like "*print_agent.py*" } |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
Start-Sleep -Seconds 1
Start-Process -FilePath $VenvPython -ArgumentList "`"$Agent`" --serve" -WorkingDirectory $AgentDir
Start-Sleep -Seconds 2

try {
    $status = Invoke-RestMethod -Uri "https://127.0.0.1:4171/status" -TimeoutSec 5
    Write-Host "Servicio en linea (https). Impresora: $($status.printer)"
} catch {
    try {
        $status = Invoke-RestMethod -Uri "http://127.0.0.1:4171/status" -TimeoutSec 5
        Write-Host "Servicio en linea (http). Impresora: $($status.printer)"
        Write-Host "AVISO: si Nissi se abre por https, el agente tiene que estar en https. Mira print_agent.log si no se creo el certificado."
    } catch {
        Write-Host "Instalado. Si /status no responde, mira print_agent.log"
    }
}
if (-not $registeredTask) {
    Write-Host "Arranque automatico: carpeta Inicio de Windows (este usuario)."
}
Write-Host "Listo. Para elegir impresora: edita config.ini (name=) o deja vacio = predeterminada."
