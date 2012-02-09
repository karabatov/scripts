## cadu.ps1
##
## PowerShell script to create Active Directory users 
## with Exchange mailboxes from CSV file
##
## Prerequisites: Exchange Management Shell, Quest Active Directory Cmdlets
## 
## Usage: $ cadu.ps1 ex-ad.csv
## 
## CSV file structure:
##
## $ cat ex-ad.csv
## firstname,lastname,name,alias,mail,department,title,description,phone,password
## Name,Surname,Surname Name,n.surname,name.surname@atriarussia.ru,IT,System administrator,IT,123,87654321

## Import data from CSV and store it in variable 'data'

$data = import-csv $args[0]

## Function to convert password into a secure string

function New-SecureString([string] $plainText)
{
    $secureString = new-object System.Security.SecureString
    foreach($char in $plainText.ToCharArray())
    {
        $secureString.AppendChar($char)
    }
    $secureString
}

## Parse CSV and process each line

foreach ($i in $data)
{
## Variables for encoded password and User Principal Name

    $ss = new-securestring $i.password
    $upn = $i.alias + "@pitproduct.ru"

## Create a new mailbox in Exchange
## CSV fields in use: name, password, alias, firstname, lastname

    New-Mailbox -Name $i.name -Database "CM-EX001\First Storage Group\Campomos" -Password $ss -UserPrincipalName $upn -Alias $i.alias -DisplayName $i.name -DomainController "cm-dc001.pitproduct.ru" -FirstName $i.FirstName -LastName $i.LastName -OrganizationalUnit "pitproduct.ru/Campomos/Temp" -ResetPasswordOnNextLogon $false
 
## Change mail address from n.surname@pitproduct.ru to name.surname@atriarussia.ru 
## CSV fields used: mail
 
    set-Mailbox -identity $upn -DomainController "cm-dc001.pitproduct.ru" -PrimarySMTPAddress $i.mail -EmailAddressPolicyEnabled $false -WindowsEmailAddress $i.mail -UseDatabaseQuotaDefaults $true
    $mailbox = Get-Mailbox -identity $upn -DomainController "cm-dc001.pitproduct.ru"
    $mailbox.EmailAddresses -= $i.alias + "@pitproduct.ru"
    $mailbox.EmailAddresses += $i.alias + "@campomos.ru"
    set-Mailbox -identity $upn -DomainController "cm-dc001.pitproduct.ru" -EmailAddresses $mailbox.EmailAddresses
 
## Set additional field values for domain user
## CSV fields used: department, phone, title, description

## Set random expiration date between 300 and 400 days since creation
    $rand = New-Object system.random
    $expire = (get-date).AddDays($rand.next(300,400))
    set-QADUser -Service "cm-dc001.pitproduct.ru" -Identity $upn -City "Moscow" -Company "Campomos" -Department $i.department -Office "Campomos" -PhoneNumber $i.phone -PostalCode "121471" -UserMustChangePassword $false -StreetAddress "Ryabinovaya St., 32" -Title $i.title -Description $i.description -objectAttributes @{c="RU";co="Russia"} -PasswordNeverExpires $false -AccountExpires $expire
 
## Add domain users to groups 
 
    Add-QADGroupMember -Identity "CN=Campomos Fileserver disk N,OU=Group,OU=Campomos,DC=pitproduct,DC=ru" -Service "cm-dc001.pitproduct.ru" -Member $upn
    Add-QADGroupMember -Identity "CN=term_use_campomos,OU=Group,OU=Campomos,DC=pitproduct,DC=ru" -Service "cm-dc001.pitproduct.ru" -Member $upn
    Add-QADGroupMember -Identity "CN=term_use,CN=Users,DC=pitproduct,DC=ru" -Service "cm-dc001.pitproduct.ru" -Member $upn
    
## Wait 20 seconds while mailbox is being replicated over network...
    Start-Sleep -Second 20
    
## Send welcoming e-mail
    $FromAddress = "info@campomos.ru"
    $ToAddress = $i.mail
    $MessageSubject = "Ознакомительная презентация по работе с Exchange"
    $MessageBody = "Добрый день! Ознакомьтесь, пожалуйста, с презентацией во вложении."
    $SendingServer = "10.2.0.1"
    $SMTPMessage = New-Object System.Net.Mail.MailMessage $FromAddress, $ToAddress, $MessageSubject, $MessageBody
    $Attachment = New-Object Net.Mail.Attachment("c:\scripts\Работа с почтой Exchange.ppt")
    $SMTPMessage.Attachments.Add($Attachment)
    $SMTPClient = New-Object System.Net.Mail.SMTPClient $SendingServer
    $SMTPClient.Send($SMTPMessage)    
    
}