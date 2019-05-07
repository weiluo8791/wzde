UPDATE webutility.access_site
SET wzde_write=:wzde_write, wzde_publish=:wzde_publish, 
wzde_commit=:wzde_commit, wzde_read=:wzde_read, 
wzde_checkout=:wzde_checkout, wzde_git=:wzde_git 
WHERE site_id=:siteID AND
user_oid in (