Dim strDomain, intCount
Dim strSubject, strBody

Function convertFiletime(strDateTime, itsDate)
    Const ADS_SCOPE_SUBTREE = 2
    Dim lngTimeZoneBias, strNewDate, lngSeconds

    If itsDate = 1 Then
        Set objShell = CreateObject("Wscript.Shell")
        lngTimeZoneBias = objShell.RegRead("HKLM\System\CurrentControlSet\Control\TimeZoneInformation\ActiveTimeBias")
        If UCase(TypeName(lngTimeZoneBias)) = "LONG" Then
            lngFinalBias = lngTimeZoneBias
        ElseIf UCase(TypeName(lngTimeZoneBias)) = "VARIANT()" Then
            lngFinalBias = 0
            For k = 0 To UBound(lngTimeZoneBias)
                lngFinalBias = lngFinalBias + (lngTimeZoneBias(k) * 256^k)
            Next
        End If

        strNewDate = DateAdd("n", lngFinalBias, strDateTime)
        lngSeconds = DateDiff("s", #1/1/1601#, strNewDate)
        convertFiletime = CStr(lngSeconds) & "0000000"
    Else
        Set objDateTime = CreateObject("WbemScripting.SWbemDateTime") 
        Call objDateTime.SetFileTime(strDateTime, False)
        convertFiletime = objDateTime.GetVarDate    
    End If
End Function

intCount = 0
strDomain = "OU=Campomos,DC=pitproduct,DC=ru"
strSubject = "[dc-expire] ѕользователи PIT с истекающим сроком учетной записи: "
strBody = "—писок пользователей, у которых " & Date() & " заканчиваетс€ срок действи€ учетной записи:" & vbCRLF

Set objConn = CreateObject("ADODB.Connection")
objConn.Provider = "ADsDSOObject"
objConn.Open "Active Directory Provider"
Set objRS = objConn.Execute("SELECT cn,sAMAccountName,lastLogon FROM 'LDAP://" & strDomain & "' WHERE objectClass='user' AND objectCategory='person'  AND accountExpires>='" & convertFiletime(Date() + 1, 1) &"' AND accountExpires<='" & convertFiletime(Date() + 2, 1) & "'")
If objRS.RecordCount <> 0 Then
    objRS.MoveFirst
    While Not objRS.EOF
        intCount = intCount + 1
        strBody = strBody & vbCRLF & "    " & intCount & ". " & cStr(objRS.Fields("cn")) & " (pit\" & cStr(objRS.Fields("sAMAccountName")) & ")"
    objRS.MoveNext
    Wend
End If

strBody = strBody & vbCRLF & vbCRLF & "ѕри необходимости продлите эти учетные записи на один год."
strSubject = strSubject & intCount

If intCount > 0 Then
    Set objEmail = CreateObject("CDO.Message")
    objEmail.Configuration.Fields.Item("http://schemas.microsoft.com/cdo/configuration/sendusing") = 2
    objEmail.Configuration.Fields.Item("http://schemas.microsoft.com/cdo/configuration/smtpserver") = "194.186.104.60"
    objEmail.Configuration.Fields.Item("http://schemas.microsoft.com/cdo/configuration/smtpserverport") = 25
    objEmail.Configuration.Fields.Update
    objEmail.From = "info@campomos.ru"
    objEmail.To = "alerts@atriarussia.ru"
    objEmail.Subject = strSubject
    objEmail.Textbody = strBody
    objEmail.TextBodyPart.Charset = "windows-1251"
    objEmail.Send
End If