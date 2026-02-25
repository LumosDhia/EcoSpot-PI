# EcoSpot Face Service REPAIR & START
$ErrorActionPreference = "Continue" # Don't stop on minor errors

Write-Host "--- REPAIRING FACE SERVICE ---" -ForegroundColor Cyan

# 1. Kill any process already using port 8001
$portProcess = Get-NetTCPConnection -LocalPort 8001 -ErrorAction SilentlyContinue
if ($portProcess) {
    Write-Host "Cleaning up port 8001..." -ForegroundColor Yellow
    Stop-Process -Id $portProcess.OwningProcess -Force -ErrorAction SilentlyContinue
}

# 2. Ensure dependencies are there
Write-Host "Verifying Python dependencies..." -ForegroundColor Gray
pip install fastapi uvicorn deepface tf-keras opencv-python-headless --quiet

# 3. Use 0.0.0.0 to bind to all interfaces (avoids 127.0.0.1 vs localhost issues)
Write-Host "`nStarting Face Recognition microservice on port 8001..." -ForegroundColor Green
Write-Host "KEEP THIS WINDOW OPEN!" -ForegroundColor Red

# Run directly so the user sees all output
python -m uvicorn face_service.main:app --host 0.0.0.0 --port 8001 --log-level info
