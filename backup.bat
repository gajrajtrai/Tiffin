@echo off
REM ─────────────────────────────────────────────────────────────
REM  Chula Tiffins — Nightly Backup
REM  Runs from Windows Task Scheduler at 2:00 AM
REM  Writes output to storage\logs\backup.log
REM ─────────────────────────────────────────────────────────────

cd /d "C:\Users\RAIPC\tiffin-app"

set PHP="C:\Program Files\php\php.exe"
set LOG="C:\Users\RAIPC\tiffin-app\storage\logs\backup.log"

echo. >> %LOG%
echo ============================================ >> %LOG%
echo Backup started at %date% %time% >> %LOG%
echo ============================================ >> %LOG%

REM Create the backup
%PHP% artisan backup:run --only-db >> %LOG% 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo BACKUP FAILED with error code %ERRORLEVEL% >> %LOG%
)

REM Include uploaded files (images, QR codes, receipts) on Sundays only
for /f "tokens=1" %%a in ('powershell -Command "(Get-Date).DayOfWeek.value__"') do set DOW=%%a
if "%DOW%"=="0" (
    echo Sunday — including files backup >> %LOG%
    %PHP% artisan backup:run --only-files >> %LOG% 2>&1
)

REM Clean old backups
%PHP% artisan backup:clean >> %LOG% 2>&1

echo Backup finished at %date% %time% >> %LOG%