@echo off
title Toca do Estudo (Laravel 11 + Livewire 3 Monolith)
echo ========================================================
echo   Iniciando Toca do Estudo — Monolito PHP (Laravel + Livewire)
echo   Acesse a aplicacao em: http://127.0.0.1:8000
echo ========================================================
echo.
cd /d "%~dp0backend"
composer dev
pause
