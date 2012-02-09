## bypasssenderdomains.ps1
##
## Powershell script for adding or removing whole domains to anti-spam whitelist
## 
## Usage: $ bypasssenderdomains.ps1 {--add | --remove} example.com

If ($args.Length -lt 2) {
    Write-Host "Usage: $ bypasssenderdomains.ps1 {--add | --remove} example.com"
    Return
}

$foo=Get-ContentFilterConfig

If ($args[0] -eq "--add") {
    $foo.BypassedSenderDomains += $args[1]
} ElseIf ($args[0] -eq "--remove") {
    $foo.BypassedSenderDomains -= $args[1]
} Else {
    Write-Host "Illegal command! Start script without arguments to see syntax."
    Return
}

$foo | Set-ContentFilterConfig
(Get-ContentFilterConfig).BypassedSenderDomains