<?php
include_once(__DIR__.'/../include/config.php');

$db = connect_db();

toLog('error', '0', '0', 'De PBK-data komt niet meer van het Kadaster. Run het CBS-script');

echo "Deze pagina werkt niet meer, ga naar <a href='readCBSPBK.php'>het script voor CBS-data</a>.";

?>
