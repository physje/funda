<?php
include_once('../general_include/general_functions.php');
include_once('../general_include/general_config.php');
include_once('../general_include/class.phpmailer.php');
include_once('../general_include/class.html2text.php');
include_once('../general_include/class.phpPushover.php');
include_once('include/functions.php');
include_once('include/config.php');
include_once('include/HTML_TopBottom.php');
connect_db();

$data = extractOpenHuisData(48392382);

echo "start : ". date("d-m H:i", $data[0]) ."<br>";
echo "eind : ". date("d-m H:i", $data[1])


?>