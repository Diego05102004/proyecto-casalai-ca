@echo off
title Microservicio IA Recepción - Demo
echo ============================================================
echo Microservicio IA Recepción - MODO DEMOSTRACIÓN
echo ============================================================
echo.
echo Este es un modo de demostración que funciona sin Tesseract OCR
echo Para funcionalidad completa, instale Tesseract OCR
echo Ver: INSTALAR_TESSERACT.md
echo.
echo Iniciando servidor en http://localhost:8000
echo Presiona Ctrl+C para detener
echo ============================================================
echo.

cd /d "%~dp0"
py demo_server.py

pause
