SELECT idsites AS id, sitename AS value FROM webutility.sites WHERE sitename LIKE :term AND zonecode='Y' ORDER BY sitename