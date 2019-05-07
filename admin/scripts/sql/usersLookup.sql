SELECT master.oid AS id, display.nameValue AS value FROM coredata_read.coreUser_master AS master 
INNER JOIN coredata_read.coreUser_name AS ename ON ename.oid=master.oid
INNER JOIN coredata_read.coreUser_name AS display ON display.oid=master.oid
INNER JOIN coredata_read.coreUser_name AS sort ON sort.oid=master.oid
WHERE (display.nameValue LIKE :term OR ename.nameValue LIKE :term) AND ename.nameType="ename" AND display.nameType="display" AND sort.nameType="sort" AND master.userIsActive='Y'
ORDER BY sort.nameValue