Write-Host "Starting Hotel Ratings Python API..." -ForegroundColor Green
Write-Host ""
Write-Host "Make sure you have installed the requirements:" -ForegroundColor Yellow
Write-Host "  pip install -r requirements.txt" -ForegroundColor Cyan
Write-Host ""
Write-Host "Starting Flask server..." -ForegroundColor Green
Write-Host "API will be available at: http://localhost:5000" -ForegroundColor Cyan
Write-Host ""

# Start Flask
flask run --host=0.0.0.0 --port=5000

Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
