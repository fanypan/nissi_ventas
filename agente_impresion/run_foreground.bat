@echo off
cd /d "%~dp0"
if exist ".venv\Scripts\python.exe" (
    ".venv\Scripts\python.exe" print_agent.py --serve
) else (
    python print_agent.py --serve
)
