@echo off
echo.Список уволенных за прошедшую неделю: > C:\scripts\fired-weekly\fired-weekly.txt
echo. >> C:\scripts\fired-weekly\fired-weekly.txt
call sqlcmd -S "BOSS\ORACLENT" -i "C:\scripts\fired-weekly\fired-weekly.sql" -E -d "кампомос" -w 80 >> C:\scripts\fired-weekly\fired-weekly.txt
echo. >> C:\scripts\fired-weekly\fired-weekly.txt
echo.При необходимости заблокируйте соответствующие учетные записи. >> C:\scripts\fired-weekly\fired-weekly.txt
call c:\scripts\fired-weekly\blat.exe c:\scripts\fired-weekly\fired-weekly.txt -to alerts@atriarussia.ru -sf c:\scripts\fired-weekly\subject.txt -server 194.186.104.60 -f info@campomos.ru -charset CP866