<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	
	$data = getFundaData($id);
	$deel_2 = $data['adres'];
	
	if(isset($_REQUEST['zeker'])) {		
		$sql = "DELETE FROM $TableHuizen WHERE $HuizenID like '$id'";
		if(mysql_query($sql)) {
			$deel_1 .= "Huis is verwijderd<br>";
		}
		
		$sql = "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '$id'";
		if(mysql_query($sql)) {
			$deel_1 .= "Kenmerken zijn verwijderd<br>";
		}
		
		$sql = "DELETE FROM $TableResultaat WHERE $ResultaatID like '$id'";
		if(mysql_query($sql)) {
			$deel_1 .= "Huis uit resultaten verwijderd<br>";
		}
	} else {
		$deel_1 = "Weet u zeker dat u dit huis wilt verwijderen?<br><a href='?id=$id&zeker=ja'>JA</a> | <a href='HouseDetails?id=$id'>NEE</a>";
	}
} else {
	$deel_1 = "Geen id bekend<br>";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td><td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_2);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>