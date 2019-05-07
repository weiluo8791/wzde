UPDATE webutility.access_site 
SET wzde_write=:wzdeWrite, wzde_publish=:wzdePublish, 
wzde_commit=:wzdeCommit, wzde_read=:wzdeRead, 
wzde_checkout=:wzdeCheckout, wzde_git=:wzdeGit 
WHERE user_oid=:oid AND site_id=:id