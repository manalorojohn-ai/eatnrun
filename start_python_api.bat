@echo off
echo Starting Hotel Ratings Python API...
echo.
echo Make sure you have installed the requirements:
echo   pip install -r requirements.txt
echo.
echo Starting Flask server...
flask run --host=0.0.0.0 --port=5000
pause
