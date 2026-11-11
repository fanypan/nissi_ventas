$ErrorActionPreference = "SilentlyContinue"
$TaskName = "NissiImpresionCaja"
$StartupVbs = Join-Path ([Environment]::GetFolderPath("Startup")) "NissiImpresionCaja.vbs"

Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
if (Test-Path -LiteralPath $StartupVbs) {
    Remove-Item -LiteralPath $StartupVbs -Force
}

Get-CimInstance Win32_Process |
    Where-Object { $_.CommandLine -like "*print_agent.py*" } |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force }

Write-Host "Tarea, atajo de Inicio y proceso del agente detenidos."
