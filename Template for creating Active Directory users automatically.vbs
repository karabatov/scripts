Const ComputerName = "CAMPM-D0002" ' Change this to whatever your computer name is
Const CsvFilename = "userlist.csv" ' Change this to wherever you store your user list

Const ForReading = 1
Const ADS_UF_DONT_EXPIRE_PASSWD = &h10000
Const ADS_UF_PASSWD_CANT_CHANGE = &H0040
 
' Open CSV File and Call CreateUser for each line in file
sub CreateUsers
  set objfs = CreateObject("Scripting.FileSystemObject")
  set fs = objfs.OpenTextFile(CsvFilename, ForReading)
  Do while NOT fs.AtEndOfStream
    arrStr = split(fs.ReadLine,",")
    If arrStr(0) <> "" Then
        strUsername = arrStr(0)
        strName = arrStr(1)
        strPassword = arrStr(2)
        strGroup = arrStr(3)
        CreateUser strUsername, strName, strPassword, strGroup
    End If
  Loop
  fs.Close
end sub
 
' Create User, Set flags and add to Group
sub CreateUser(strUser, strName, strPass, strGroup)
  Set colAccounts = GetObject("WinNT://" & ComputerName & "")
  Set objUser = colAccounts.Create("user", strUser)
  objUser.Put "FullName", strName
  objUser.SetPassword strPass
  objUser.SetInfo
  objUserFlags = objUser.Get("UserFlags")
  objPasswordExpirationFlag = objUserFlags OR ADS_UF_DONT_EXPIRE_PASSWD OR ADS_UF_PASSWD_CANT_CHANGE
  objUser.Put "userFlags", objPasswordExpirationFlag
  objUser.SetInfo
 
  Dim GroupArray(1)
  GroupArray(0) = "Group1"
  GroupArray(1) = "Group2"
  
  Set objGroup = GetObject("WinNT://" & ComputerName & "/" & GroupArray(strGroup))
  objGroup.Add(objUser.ADsPath)
 
  wscript.stdOut.write(strUser & "; ")
end sub
 
'Go forth and create!
CreateUsers