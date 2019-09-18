<?php
include_once(__DIR__.'/../include/config.php');

$db = connect_db();

if(isset($_REQUEST['huis']) AND isset($_REQUEST['opdracht'])) {
	$prijzen = getPriceHistory($_REQUEST['huis']);
	$data['id']			= $_REQUEST['huis'];
	$data['prijs']	= current($prijzen);
	
	if(addHouse($data, $_REQUEST['opdracht'])) {
		echo $_REQUEST['opdracht'] .' -> '. $_REQUEST['huis'] .' ('. current($prijzen) .')';
	} else {
		echo "mislukt";
	}
} else {	
	$opdrachten = getZoekOpdrachten(1, '');
	
	echo "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	echo "<table>\n";
	echo "<tr>";
	echo "	<td>Opdracht</td>";
	echo "	<td>&nbsp;</td>";
	echo "	<td>Huis</td>";	
	echo "</tr>";	
	echo "<tr>";
	echo "	<td><select name='opdracht'>";
	
	foreach($opdrachten as $opdracht) {
		$OpdrachtData = getOpdrachtData($opdracht);
		echo "<option value='$opdracht'>". $OpdrachtData['naam'] ."</option>";
	}
		
	echo "	</select></td>";
	echo "	<td>&nbsp;</td>";
	echo "	<td><select name='huis'>";
	
	$sql		= "SELECT * FROM $TableHuizen ORDER BY $HuizenAdres, $HuizenStart";
	$result	= mysqli_query($db, $sql);
	$row = mysqli_fetch_array($result);
		
	do {
		echo "<option value='". $row[$HuizenID] ."'>". urldecode($row[$HuizenAdres]) ."; ". urldecode($row[$HuizenPlaats]) ." (". $row[$HuizenID] .")</option>";
	} while($row = mysqli_fetch_array($result));
	
	echo "	</select></td>";	
	echo "</tr>";
	echo "</table>\n";
	echo "<input type='submit' name='uitvoeren' value='uitvoeren'>\n";
	echo "</form>\n";
	
}