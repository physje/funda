<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/config.php');

connect_db();

if(isset($_REQUEST['id_1']) AND isset($_REQUEST['id_2'])) {
	$key_1[0] = $_REQUEST['id_1'];
	$key_2[0] = $_REQUEST['id_2'];
} else {
	$i = 1;
	$KeyArray = array();
	$grens = time() - (2*24*60*60);

	$sql		= "SELECT * FROM $TableHuizen WHERE $HuizenEind < $grens AND $HuizenVerkocht like '0' ORDER BY $HuizenAdres, $HuizenStart";
	$result	= mysql_query($sql);
	$row = mysql_fetch_array($result);

	do {
		$adres		= $row[$HuizenAdres];
		$PC				= $row[$HuizenPC_c];
		$id_oud		= $row[$HuizenID];
		$sql_2		= "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '$adres' AND $HuizenPC_c like '$PC' AND $HuizenID NOT like '$id_oud'";
		$result_2	= mysql_query($sql_2);
		
		if(mysql_num_rows($result_2) == 1 AND !array_key_exists($id_oud, $KeyArray)) {
			$row_2	= mysql_fetch_array($result_2);
			$id_new	= $row_2[$HuizenID];
		
			$key_1[$i] = $id_oud;//$row[$HuizenID];
			$key_2[$i] = $id_new;//$row_2[$HuizenID];
		
			$i++;
			$KeyArray[$id_new] = $id_oud;
		}
	} while($row = mysql_fetch_array($result));
}

if(is_array($key_1)) {
	foreach($key_1 as $key => $value) {
		$id_oud = $key_1[$key];
		$id_new = $key_2[$key];
		
		$data_oud = getFundaData($id_oud);
		$data_new = getFundaData($id_new);
		
		//echo $key .'). '. $data_1['adres'] .' -> '. $data_2['adres'] .'<br>';
		//echo $id_1 .' - '. date("d-m-Y", $data_1['start']) .' t/m '. date("d-m-Y", $data_1['eind']) .' ['. $data_1['start'] .'|'. $data_1['eind'] .']<br>';
		//echo $id_2 .' - '. date("d-m-Y", $data_2['start']) .' t/m '. date("d-m-Y", $data_2['eind']) .' ['. $data_2['start'] .'|'. $data_2['eind'] .']<br>';	
						
		# De begin- en eindtijd voor het nieuwe huis in tabel met huizen updaten
		$sql_update_1 = "UPDATE $TableHuizen SET $HuizenStart = ". $data_oud['start'] .", $HuizenEind = ". $data_new['eind'] ." WHERE $HuizenID like '". $id_new ."'";
		if(!mysql_query($sql_update_1)) {
			echo "[$sql_update]<br>";		
			toLog('error', '', $id_oud, "Error verplaatsen data van $id_oud naar $id_new");
		} else {
			toLog('info', '', $id_oud, "Data van $id_oud verplaatst naar $id_new");
			toLog('info', '', $id_new, "Data van $id_oud toegevoegd.");
		}
		
		# Tabel met prijzen updaten
		$sql_update_2 = "UPDATE $TablePrijzen SET $PrijzenID = '$id_new' WHERE $PrijzenID like '$id_oud'";
		if(!mysql_query($sql_update_2)) {
			echo "[$sql_update]<br>";
			toLog('error', '', $id_oud, "Error toewijzen prijzen aan $id_new");
		} else {
			toLog('info', '', $id_oud, "Prijzen toewijzen aan $id_new");
		}
		
		# Tabel met lijsten updaten
		$sql_update_3 = "UPDATE $TableListResult SET $ListResultHuis = '$id_new' WHERE $ListResultHuis like '$id_oud'";
		if(!mysql_query($sql_update_3)) {
			echo "[$sql_update]<br>";
			toLog('error', '', $id_oud, "Error toewijzen $id_new op lijst");
		} else {
			toLog('info', '', $id_oud, "$id_new toegewezen op lijst");
		}
				
		# Het oude huis uit de tabel met huizen halen
		$sql_delete_1	= "DELETE FROM $TableHuizen WHERE $HuizenID like '$id_oud'";
		if(!mysql_query($sql_delete_1)) {
			echo "[$sql_delete_1]<br>";
			toLog('error', '', $id_oud, "Error verwijderen huis (is identiek aan $id_new)");
		} else {
			toLog('info', '', $id_oud, "Verwijderen huis (is identiek aan $id_new)");
		}
		
		# Het oude huis uit de tabel met kenmerken halen (de nieuwe staan er al in)
		$sql_delete_2	= "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '$id_oud'";
		if(!mysql_query($sql_delete_2)) {
			echo "[$sql_delete_2]<br>";
			toLog('error', '', $id_oud, "Error verwijderen kenmerken (zijn identiek aan $id_new)");
		} else {
			toLog('info', '', $id_oud, "Kenmerken verwijderd (zijn identiek aan $id_new)");
		}
		
		# Het oude huis uit de tabel met resultaten halen (de nieuwe staat er al in)
		$sql_delete_3 = "DELETE FROM $TableResultaat WHERE $ResultaatID like '$id_oud'";
		if(!mysql_query($sql_delete_3)) {
			echo "[$sql_update]<br>";
			toLog('error', '', $id_oud, "Error verwijderen van $id_oud in opdracht");
		} else {
			toLog('info', '', $id_oud, "Verwijderd uit opdracht (is nu $id_new)");
		}
		
		echo '<br>';
		
		$Item  = "<table width='100%'>\n";
		$Item .= "<tr>\n";
		$Item .= "	<td align='center'><img src='". changeThumbLocation(urldecode($data_oud['thumb'])) ."'></td>\n";
		$Item .= "	<td align='center'><img src='". changeThumbLocation(urldecode($data_new['thumb'])) ."'></td>\n";
		$Item .= "</tr>\n";
		$Item .= "<tr>\n";
		$Item .= "	<td align='center'><a href='http://www.funda.nl". $data_oud['url'] ."'>". urldecode($data_oud['adres']) ."</a>, ". $data_oud['plaats'] ."<br>$id_oud (verwijderd)</td>\n";
		$Item .= "	<td align='center'><a href='http://www.funda.nl". $data_new['url'] ."'>". urldecode($data_new['adres']) ."</a>, ". $data_new['plaats'] ."<br>$id_new (master)</td>\n";
		$Item .= "</tr>\n";
		$Item .= "<tr>\n";
		$Item .= "	<td align='center'>". date("d-m-y", $data_oud['start']) .' t/m '. date("d-m-y", $data_oud['eind']) ."</td>\n";
		$Item .= "	<td align='center'>". date("d-m-y", $data_new['start']) .' t/m '. date("d-m-y", $data_new['eind']) ."</td>\n";
		$Item .= "</tr>\n";
		$Item .= "</table>\n";
		
		$HTMLMessage[] = showBlock($Item);
	}
	
	
	// Vervang begintijd_2 door begintijd_1
	// Verwijder key_1
	// Vervang in prijzen tabel ID_1 door ID_2
	
	if(count($HTMLMessage) > 0) {
		$FooterText = "<a href='http://www.funda.nl/'>funda.nl</a>";
		include('../include/HTML_TopBottom.php');
				
		$omslag = round(count($HTMLMessage)/2);
		$KolomEen = array_slice ($HTMLMessage, 0, $omslag);
		$KolomTwee = array_slice ($HTMLMessage, $omslag, $omslag);
		
		$HTMLMail = $HTMLHeader;
		
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "<td width='50%' valign='top' align='center'>\n";
		$HTMLMail .= implode("\n<p>\n", $KolomEen);
		$HTMLMail .= "</td><td width='50%' valign='top' align='center'>\n";
		if(count($KolomTwee) > 0) {
			$HTMLMail .= implode("\n<p>\n", $KolomTwee);	
		} else {
			$HTMLMail .= "&nbsp;";	
		}
		$HTMLMail .= "</td>\n";
		$HTMLMail .= "</tr>\n";
		$HTMLMail .= "<tr>\n";
		$HTMLMail .= "	<td colspan='2' align='center'>&nbsp;</td>\n";
		$HTMLMail .= "</tr>\n";
		
		$HTMLMail .= $HTMLPreFooter;
		$HTMLMail .= $HTMLFooter;
			
		$mail = new PHPMailer;
		$mail->From     = $ScriptMailAdress;
		$mail->FromName = $ScriptTitle;
		$mail->WordWrap = 90;
		$mail->AddAddress($ScriptMailAdress, 'Matthijs');
		$mail->Subject	= $SubjectPrefix. "Funda opruiming";
		$mail->IsHTML(true);
		$mail->Body			= $HTMLMail;
		
		if(!$mail->Send()) {
			echo "Versturen van mail is mislukt<br>";
			toLog('error', '', '', "Fout met mail nav opschoonwerkzaamheden");			
		} else {
			toLog('info', '', '', "Mail nav opschoonwerkzaamheden verstuurd");
		}
	}
} else {
	echo "Geen werk aan de winkel";
	//toLog('info', '', '', "Geen opschoonwerkzaamheden verricht");
}
?>