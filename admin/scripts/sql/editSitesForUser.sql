UPDATE webutility.access_site
SET wzde_write=:wzde_write, wzde_publish=:wzde_publish, 
wzde_commit=:wzde_commit, wzde_read=:wzde_read, 
wzde_checkout=:wzde_checkout, wzde_git=:wzde_git 
WHERE user_oid=:user_oid AND 
site_id in (