@echo off
echo Demarrage du serveur de developpement...
echo.

REM Recuperer l'adresse IP locale
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4"') do (
    set IP=%%a
    goto :found
)
:found
set IP=%IP:~1%

echo Serveur accessible sur :
echo - Local  : http://localhost:8000
echo - Reseau : http://%IP%:8000
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur
echo.

php -S 0.0.0.0:8000 -t public
pause
```

## Accès depuis un autre appareil

Une fois le serveur lancé, sur votre téléphone/tablette/autre PC connecté au **même réseau WiFi**, allez sur :
```
http://VOTRE_IP_LOCALE:8000