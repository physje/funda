<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();


//if(isset($_REQUEST['uitvoeren'])) {
//	$id_oud = $_REQUEST['id_1'];
//	$id_new = $_REQUEST['id_2'];
//	
//	$data_oud = getFundaData($id_oud);
//	$data_new = getFundaData($id_new);
//	
//	//echo $key .'). '. $data_1['adres'] .' -> '. $data_2['adres'] .'<br>';
//	//echo $id_1 .' - '. date("d-m-Y", $data_1['start']) .' t/m '. date("d-m-Y", $data_1['eind']) .' ['. $data_1['start'] .'|'. $data_1['eind'] .']<br>';
//	//echo $id_2 .' - '. date("d-m-Y", $data_2['start']) .' t/m '. date("d-m-Y", $data_2['eind']) .' ['. $data_2['start'] .'|'. $data_2['eind'] .']<br>';	
//	
//	//$sql_update_1 = "UPDATE $TableHuizen SET $HuizenStart = ". min(array($data_1['start'], $data_2['start'])) .", $HuizenEind = ". max(array($data_1['eind'], $data_2['eind'])) ." WHERE $HuizenID like '". $id_2 ."'";
//	
//	$sql_update_1 = "UPDATE $TableHuizen SET $HuizenStart = ". $data_oud['start'] .", $HuizenEind = ". $data_new['eind'] ." WHERE $HuizenID like '". $id_new ."'";
//	if(!mysql_query($sql_update_1)) {
//		echo "[$sql_update]<br>";		
//		toLog('error', '', $id_oud, "Error verplaatsen data van $id_oud naar $id_new");
//	} else {
//		toLog('info', '', $id_oud, "Data van $id_oud verplaatst naar $id_new");
//		toLog('info', '', $id_new, "Data van $id_oud toegevoegd.");
//	}
//	
//	$sql_update_2 = "UPDATE $TablePrijzen SET $PrijzenID = '$id_new' WHERE $PrijzenID like '$id_oud'";
//	if(!mysql_query($sql_update_2)) {
//		echo "[$sql_update]<br>";
//		toLog('error', '', $id_oud, "Error toewijzen prijzen aan $id_new");
//	} else {
//		toLog('info', '', $id_oud, "Prijzen toewijzen aan $id_new");
//	}
//	
//	$sql_update_3 = "UPDATE $TableResultaat SET $ResultaatID = '$id_new' WHERE $ResultaatID like '$id_oud'";
//	if(!mysql_query($sql_update_3)) {
//		echo "[$sql_update]<br>";
//		toLog('error', '', $id_new, "Error verplaaten van $id_oud naar $id_new in opdracht");
//	} else {
//		toLog('info', '', $id_new, "$id_new toevoegen aan opdracht (was $id_oud)");
//	}
//	
//	$sql_delete_1	= "DELETE FROM $TableHuizen WHERE $HuizenID like '$id_oud'";
//	if(!mysql_query($sql_delete_1)) {
//		echo "[$sql_delete_1]<br>";
//		toLog('error', '', $id_oud, "Error verwijderen huis (is identiek aan $id_new)");
//	} else {
//		toLog('info', '', $id_oud, "Verwijderen huis (is identiek aan $id_new)");
//	}
//	
//	$sql_delete_2	= "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '$id_oud'";
//	if(!mysql_query($sql_delete_2)) {
//		echo "[$sql_delete_2]<br>";
//		toLog('error', '', $id_oud, "Error verwijderen kenmerken (zijn identiek aan $id_new)");
//	} else {
//		toLog('info', '', $id_oud, "Kenmerken verwijderd (zijn identiek aan $id_new)");
//	}
//} else {
	$sql		= "SELECT * FROM $TableHuizen ORDER BY $HuizenAdres, $HuizenStart";
	$result	= mysql_query($sql);
	$row = mysql_fetch_array($result);
	
	$i = 1;
	$KeyArray = array();
	
	do {
		$option[] = "<option value='". $row[$HuizenID] ."'>". urldecode($row[$HuizenAdres]) ."; ". urldecode($row[$HuizenPlaats]) ." (". $row[$HuizenID] .")</option>";
		/*
		$adres		= $row[$HuizenAdres];
		$id				= $row[$HuizenID];
		$PC				= $row[$HuizenPC_c];
		$sql_2		= "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '$adres' AND $HuizenPC_c like $PC AND $HuizenID NOT like '$id'";
		$result_2	= mysql_query($sql_2);
		
		//echo mysql_num_rows($result_2);
		
		if(mysql_num_rows($result_2) == 1 AND !array_key_exists($id, $KeyArray)) {
			$row_2	= mysql_fetch_array($result_2);
			$id_2		= $row_2[$HuizenID];
			
			echo "<tr>";
			echo "	<td><input type='checkbox' name='rij[$i]' value='1' checked><input type='hidden' name='key_1[$i]' value='". $row[$HuizenID] ."'><input type='hidden' name='key_2[$i]' value='". $row_2[$HuizenID] ."'></td>";
			echo "	<td><a href='http://www.funda.nl". urldecode($row[$HuizenURL]) ."' target='_blank'>". urldecode($row[$HuizenAdres]) ."</a> (". date("d-m", $row[$HuizenStart]) ." t/m ". date("d-m", $row[$HuizenEind]) .")</td>";
			echo "	<td> -> </td>";
			echo "	<td><a href='http://www.funda.nl". urldecode($row_2[$HuizenURL]) ."' target='_blank'>". urldecode($row_2[$HuizenAdres]) ."</a> (". date("d-m", $row_2[$HuizenStart]) ." t/m ". date("d-m", $row_2[$HuizenEind]) .")</td>";
			echo "</tr>";
			
			$i++;
			$KeyArray[$id_2] = $id;
			
		}*/
	} while($row = mysql_fetch_array($result));

	echo "<form method='post' action='combine_batch.php'>\n";
	echo "<table>\n";
	echo "<tr>";
	echo "	<td>Verwijderen</td>";
	echo "	<td>&nbsp;</td>";
	echo "	<td>Master</td>";	
	echo "</tr>";	
	echo "<tr>";
	echo "	<td><select name='id_1'>";
	echo implode("\n", $option);
	echo "	</td>";
	echo "	<td> -> </td>";
	echo "	<td><select name='id_2'>";
	echo implode("\n", $option);
	echo "	</td>";	
	echo "</tr>";
	echo "</table>\n";
	echo "<input type='submit' name='uitvoeren' value='uitvoeren'>\n";
	echo "</form>\n";
	
	echo "Starttijd van huis 1 toevoegen aan huis 2";
//}
?>