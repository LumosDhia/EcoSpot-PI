# PowerShell script to start the Face Recognition microservice
Write-Host "Checking Face Recognition microservice dependencies..." -ForegroundColor Cyan

$servicePath = "face_service/main.py"

if (-not (Test-Path $servicePath)) {
    Write-Host "Error: $servicePath not found. Please run this script from the project root." -ForegroundColor Red
    exit 1
}

Write-Host "Starting Face Recognition microservice on http://127.0.0.1:8001..." -ForegroundColor Green
Write-Host "Press Ctrl+C to stop the service (if running in this terminal)." -ForegroundColor Yellow

python $servicePath
