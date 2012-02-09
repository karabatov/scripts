'Set correct DNS suffix search list
' © 2009 Yuri Karabatov

SET WSHShell = CreateObject("WScript.Shell")
WSHShell.RegWrite "HKLM\System\CurrentControlSet\Services\TCPIP\Parameters\SearchList", "campomos.group,pitproduct.ru", "REG_SZ"