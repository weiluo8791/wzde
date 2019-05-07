svn status | findstr /R "^!" > missing.list
for /F "tokens=* delims=! " %%A in (missing.list) do (svn delete "%%A")
del missing.list 2>NUL