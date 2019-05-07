SELECT user_oid AS oid,display.nameValue AS name,wzde_write,wzde_publish,wzde_commit,wzde_read,wzde_checkout,wzde_git
FROM access_site AS access
INNER JOIN sites ON sites.idsites=access.site_id
INNER JOIN coredata_read.coreUser_name AS display ON display.oid=access.user_oid
INNER JOIN coredata_read.coreUser_name AS sort ON sort.oid=access.user_oid
WHERE site_id=:id AND display.nameType="display" AND sort.nameType="sort" AND sites.zonecode='Y'
ORDER BY sort.nameValue