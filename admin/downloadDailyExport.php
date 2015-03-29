<?php
include_once(__DIR__. '../include/config.php');

header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache");
header("Cache-control: private");
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename="FundaExport_'. strftime ('%Y.%m.%d-%H.%M') .'.xls"');

$fp = fopen($cfgXLSFilename, "r");
echo fread($fp,filesize($cfgXLSFilename));
fclose($fp);
?>