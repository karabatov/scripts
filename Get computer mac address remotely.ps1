Clear

$strComputer = Read-Host "Enter Machine Name"

Clear

 

$colItems = GWMI -class "Win32_NetworkAdapterConfiguration" -credential "campomos-2\y.karabatov" -computerName $strComputer -filter "IpEnabled = TRUE"

 

ForEach ($objItem in $colItems) 

{Write-Host "Machine Name: " $strComputer

Write-Host "MAC Address: " $objItem.MacAddress

Write-Host "IP Address: " $objItem.IpAddress}
