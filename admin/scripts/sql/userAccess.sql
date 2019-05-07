SELECT wzde_write,wzde_publish,wzde_commit,wzde_read,wzde_checkout,wzde_git
FROM access_site
WHERE user_oid=:oid AND site_id=:id