<?php
// real simple: things in '`' (back tics?) get sent to the shell
// so we're gonna run svn, collect the output
// to a variable in xml
$svn = `svn --config-dir E:/Subversion --trust-server-cert --non-interactive --username ratservice --password GEODE list --xml https://wpltools.meditech.com/svn/corpapps/WZDE_Repo/`;
//load xml string into object
$xml = simplexml_load_string($svn);
//encode result into json
$json = json_encode($xml);
$array = json_decode($json,TRUE);

//if no entry return empty json
if ($array["list"]["entry"]) {
    echo $json;
}
else {
    echo json_encode (new stdClass);
}

?>

