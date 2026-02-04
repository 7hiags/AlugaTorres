@echo off
echo Starting Weather API Server...
cd /d "%~dp0backend"
if exist venv\Scripts\activate.bat (
    call venv\Scripts\activate.bat
) else (
    echo Virtual environment not found, using system Python...
)
python api_meteorologia.py
pause
