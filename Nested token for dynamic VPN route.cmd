@echo off
setlocal ENABLEEXTENSIONS
for /f "tokens=1" %%a in ('route print ^|findstr /i 0x..0005') do (
	route change 0.0.0.0 mask 0.0.0.0 10.2.0.2
	for /f "tokens=3" %%b in ('route print ^|findstr /i /l "10.5.0."') do (
		route add 10.2.0.0 mask 255.255.0.0 %%b if %%a metric 1
		)
	)