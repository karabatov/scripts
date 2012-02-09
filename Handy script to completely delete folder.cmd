cd C:\tmp
del /q *.*
for /d %%a in (*.*) do rd /s /q "%%a"