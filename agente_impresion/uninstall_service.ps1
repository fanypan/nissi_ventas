$ErrorActionPreference = "Stop"
$AgentDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$VenvPython = Join-Path $AgentDir ".venv\Scripts\python.exe"
if (Test-Path $VenvPython) {
    Start-Process -FilePath $VenvPython -ArgumentList @((Join-Path $AgentDir "print_agent.py"), "stop") -Wait -NoNewWindow -ErrorAction SilentlyContinue
    Start-Process -FilePath $VenvPython -ArgumentList @((Join-Path $AgentDir "print_agent.py"), "remove") -Wait -NoNewWindow
}
Write-Host "Servicio eliminado (si existia)."
