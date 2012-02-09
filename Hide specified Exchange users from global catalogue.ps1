## hexa.ps1
##
## PowerShell script to hide existing Active Directory users from Exchange address list from CSV file
##
## Prerequisites: Exchange Management Shell Cmdlets
## 
## Usage: $ hexa.ps1 hea.csv
## 
## CSV file structure:
##
## $ cat hea.csv
## alias
## n.surname

## Import data from CSV and store it in variable 'data'

$data = import-csv $args[0]

## Parse CSV and process each line

foreach ($i in $data)
{
## Variable for User Principal Name

    $upn = $i.alias + "@pitproduct.ru"

## Get user by UPN from Active Directory    
## CSV fields used: alias

    $aduser = Get-QADUser -Identity $i.alias -Service "cm-dc001.pitproduct.ru"
    
## Set mailbox to hidden from Exchange address list
    
    set-Mailbox -identity $upn -DomainController "cm-dc001.pitproduct.ru" -HiddenFromAddressListsEnabled $true
}