<?php
include_once(__DIR__.'/../include/config.php');
include_once(__DIR__.'/../include/HTML_TopBottom.php');

$ids = explode('|', $_REQUEST['ids']);
$fundaID = array_shift($ids);

echo "<html>\n";
echo "<head>\n";
   
if(count($ids) > 0) {	
	echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAll.php?ids=". implode('|', $ids) ."\" />";
} else {
	echo "<meta http-equiv=\"refresh\" content=\"0;URL=openAll.php?close\" />";
}
   
echo "</head>\n";

if(isset($_REQUEST['close'])) {
	echo "<body onload=\"window.close();\">\n";	
} else {
	echo "<body onload=\"window.open('http://funda.nl/$fundaID', '_blank');\">\n";
}
//echo $fundaID ."<br>\n";
echo "</body>\n";
echo "</html>\n";

?>