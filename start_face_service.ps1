# PowerShell script to start the Face Recognition microservice
$ErrorActionPreference = "Stop"

Write-Host "--------------------------------------------------" -ForegroundColor Cyan
Write-Host "EcoSpot Face Recognition Microservice Starter" -ForegroundColor Cyan
Write-Host "--------------------------------------------------" -ForegroundColor Cyan

$servicePath = "face_service/main.py"
$logFile = "face_service.log"

if (-not (Test-Path $servicePath)) {
    Write-Host "Error: $servicePath not found. Please run this script from the project root." -ForegroundColor Red
    exit 1
}

# Clear previous logs if they exist
if (Test-Path $logFile) {
    Clear-Content $logFile
    Write-Host "Cleared previous logs." -ForegroundColor Gray
}

Write-Host "`nStarting Face Recognition microservice on http://127.0.0.1:8001" -ForegroundColor Green
Write-Host "IMPORTANT: KEEP THIS WINDOW OPEN while using Face ID features." -ForegroundColor Yellow
Write-Host "The service will automatically restart if it crashes.`n" -ForegroundColor Gray

# Infinite loop to keep the service alive
while ($true) {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Starting service..." -ForegroundColor Gray
    python $servicePath
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Service stopped or crashed. Restarting in 2 seconds..." -ForegroundColor Red
    Start-Sleep -Seconds 2
}
