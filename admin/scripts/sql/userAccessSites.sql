SELECT site_id AS id,sitename AS name,wzde_write,wzde_publish,wzde_commit,wzde_read,wzde_checkout,wzde_git
FROM webutility.access_site AS access
INNER JOIN sites ON sites.idsites=access.site_id
WHERE user_oid=:oid AND sites.zonecode='Y'
ORDER BY sitename