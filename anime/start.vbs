Set WshShell = CreateObject("WScript.Shell")

' Start XAMPP (hidden, walang CMD)
WshShell.Run """C:\xampp\xampp_start.exe""", 0, False

' Hintayin mag-start Apache & MySQL
WScript.Sleep 6000

' Open login page ng system
WshShell.Run "http://localhost/anime/login.php", 0, False