# Servicio Windows (cuenta LocalSystem). Preferi install_autostart.ps1:
# LocalSystem a veces no ve la impresora del cajero.
$ErrorActionPreference = "Stop"
$AgentDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$VenvPython = Join-Path $AgentDir ".venv\Scripts\python.exe"
if (-not (Test-Path $VenvPython)) {
    throw "Primero ejecuta install_autostart.ps1 para crear el venv, o: python -m venv .venv && .venv\Scripts\pip install -r requirements.txt"
}
Start-Process -FilePath $VenvPython -ArgumentList @((Join-Path $AgentDir "print_agent.py"), "install") -Wait -NoNewWindow
Start-Process -FilePath $VenvPython -ArgumentList @((Join-Path $AgentDir "print_agent.py"), "start") -Wait -NoNewWindow
Write-Host "Servicio NissiImpresionCaja instalado. Si no imprime, configura la cuenta del servicio como el usuario de caja o usa install_autostart.ps1."
