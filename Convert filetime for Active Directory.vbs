' ConvertFileTime.vbs
' VBScript doesn't support 64-bit integers, so it can't handle the number of 100 nanosecond intervals since 01/01/1601
' http://msdn.microsoft.com/library/default.asp?url=/library/en-us/dnclinic/html/scripting09102002.asp

' Either use ADSI provider and the IADs/IADsLargeInteger object
' LargeIntValue = objLargeInt.HighPart * 2^32 + objLargeInt.LowPart
' http://msdn.microsoft.com/library/default.asp?url=/library/en-us/adsi/adsi/iadslargeinteger.asp'

' Or WMI, which handles the conversion between 64-bit datetime structure / UTC / and VB var datetime

If Wscript.Arguments.UnNamed.Count > 0 Then
 strDateTime = Wscript.Arguments.UnNamed(0)

 Set objDateTime = CreateObject("WbemScripting.SWbemDateTime")

 If IsDate(strDateTime) Then
  Call objDateTime.SetVarDate(strDateTime, False)
  wscript.echo objDateTime.GetFileTime
 Else
  Call objDateTime.SetFileTime(strDateTime, False)
  wscript.echo objDateTime.GetVarDate
 End If

 intReturn = 0
Else
 WScript.Echo "Specify a filetime or a date to convert, eg 127076450620627215, or ""11/04/2006 11:17:10 AM"""
 intReturn = 2
End If

WScript.Quit(intReturn)