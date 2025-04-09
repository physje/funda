<?php
include_once(__DIR__.'/../include/config.php');
include_once($cfgGeneralIncludeDirectory.'class.phpmailer.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

$manual = false;
$counter = 0;
$HTMLMessageNeg = $HTMLMessage = $key_1 = $key_2 = array();

if(isset($_REQUEST['id_1']) AND isset($_REQUEST['id_2'])) {
	$key_1[0] = $_REQUEST['id_1'];
	$key_2[0] = $_REQUEST['id_2'];
	$manual = true;
} else {
	$i = 1;
	$KeyArray			= array();

	$sql  = "SELECT * ";
	$sql .= "FROM $TableHuizen ";
	$sql .= "WHERE ";
	$sql .= "$HuizenVerkocht like '0' AND ";
	$sql .= "$HuizenNummer != '' AND ";
	$sql .= "$HuizenOffline like '1'";
	
	$result	= mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$id_oud     = $row[$HuizenID];
			$data       = getFundaData($id_oud);
			$jaarLater  = $data['eind']+(175*24*60*60);
			$jaarEerder = $data['start']-(175*24*60*60);
			
			$sql_2	 = "SELECT * FROM $TableHuizen WHERE ";
			$sql_2  .= "$HuizenStraat like '". urlencode($data['straat']) ."' AND ";
			$sql_2  .= "$HuizenNummer = ". $data['nummer'] ." AND ";
			$sql_2  .= "$HuizenLetter like '". urlencode($data['letter']) ."' AND ";
			$sql_2  .= "$HuizenToevoeging = '". $data['toevoeging'] ."' AND ";
			$sql_2  .= "$HuizenPlaats like '". urlencode($data['plaats']) ."' AND ";
			$sql_2  .= "(($HuizenStart BETWEEN ". $data['eind'] ." AND $jaarLater) OR ($HuizenEind BETWEEN $jaarEerder AND ". $data['start'] .")) AND ";
			$sql_2  .= "$HuizenID NOT like '$id_oud'";
			
			$result_2	= mysqli_query($db, $sql_2);
			
			if(mysqli_num_rows($result_2) > 0 AND !in_array($id_oud, $KeyArray) AND !ignoreHouse4Combine($id_oud)) {
				$row_2	= mysqli_fetch_array($result_2);
				$id_new	= $row_2[$HuizenID];
					
				if(!in_array($id_new, $KeyArray)) {
					$key_1[$i] = $id_oud;
					$key_2[$i] = $id_new;
				
					$i++;
					$KeyArray[] = $id_oud;
					$KeyArray[] = $id_new;
				}
			}
		} while($row = mysqli_fetch_array($result));
	} else {
		echo "Geen offline huizen die niet verkocht zijn<br>";
	}
}


if(count($key_1) > 0) {
	foreach($key_1 as $key => $value) {
		$verwijderd = false;
		$id_oud = $key_1[$key];
		$id_new = $key_2[$key];
		
		$data_oud = getFundaData($id_oud);
		$data_new = getFundaData($id_new);
				
		# Huizen die niet een paar dagen offline zijn geweest zijn 'verdacht' en worden dus niet automatisch samengevoegd
		if(($data_new['start'] - $data_oud['eind']) > 0 OR $manual) {
	
			# De begin- en eindtijd voor het nieuwe huis in tabel met huizen updaten
			# Neem de vroegst bekende starttijd en de laatst bekende eindtijd
			$sql_update_1 = "UPDATE $TableHuizen SET $HuizenStart = ". min($data_oud['start'], $data_new['start']) .", $HuizenEind = ". max($data_oud['eind'], $data_new['eind']) ." WHERE $HuizenID like '". $id_new ."'";
			if(!mysqli_query($db, $sql_update_1)) {
				echo "[$sql_update_1]<br>";		
				toLog('error', '0', $id_oud, "Error verplaatsen data van $id_oud naar $id_new");
			} else {
				toLog('info', '0', $id_oud, "Data van $id_oud verplaatst naar $id_new");
				toLog('info', '0', $id_new, "Data van $id_oud toegevoegd.");
			}
			
			# Tabel met prijzen updaten
			$sql_update_2 = "UPDATE $TablePrijzen SET $PrijzenID = '$id_new' WHERE $PrijzenID like '$id_oud'";
			if(!mysqli_query($db, $sql_update_2)) {
				echo "[$sql_update_2]<br>";
				toLog('error', '0', $id_oud, "Error toewijzen prijzen aan $id_new");
			} else {
				toLog('info', '0', $id_oud, "Prijzen toewijzen aan $id_new");
			}
			
			# Tabel met lijsten updaten
			$sql_update_3 = "UPDATE $TableListResult SET $ListResultHuis = '$id_new' WHERE $ListResultHuis like '$id_oud'";
			if(!mysqli_query($db, $sql_update_3)) {
				echo "[$sql_update_3]<br>";
				toLog('error', '0', $id_oud, "Error toewijzen $id_new op lijst");
			} else {
				toLog('info', '0', $id_oud, "$id_new toegewezen op lijst");
			}
			
			# Tabel met open huizen updaten
			$sql_update_4 = "UPDATE $TableCalendar SET $CalendarHuis = '$id_new' WHERE $CalendarHuis like '$id_oud'";
			if(!mysqli_query($db, $sql_update_4)) {
				echo "[$sql_update_4]<br>";
				toLog('error', '0', $id_oud, "Error toewijzen open huis aan $id_new");
			} else {
				toLog('info', '0', $id_oud, "Open huizen toewijzen aan $id_new");
			}

			# Tabel met WOZ-waardes updaten
			$sql_update_5 = "UPDATE $TableWOZ SET $WOZFundaID = '$id_new' WHERE $WOZFundaID like '$id_oud'";
			if(!mysqli_query($db, $sql_update_5)) {
				echo "[$sql_update_5]<br>";
				toLog('error', '0', $id_oud, "Error toewijzen WOZ-waardes aan $id_new");
			} else {
				toLog('info', '0', $id_oud, "WOZ-waardes toewijzen aan $id_new");
			}
					
			# Het oude huis uit de tabel met huizen halen
			$sql_delete_1	= "DELETE FROM $TableHuizen WHERE $HuizenID like '$id_oud'";
			if(!mysqli_query($db, $sql_delete_1)) {
				echo "[$sql_delete_1]<br>";
				toLog('error', '0', $id_oud, "Error verwijderen huis (is identiek aan $id_new)");
			} else {
				toLog('info', '0', $id_oud, "Verwijderen huis (is identiek aan $id_new)");
			}
			
			# Het oude huis uit de tabel met kenmerken halen (de nieuwe staan er al in)
			$sql_delete_2	= "DELETE FROM $TableKenmerken WHERE $KenmerkenID like '$id_oud'";
			if(!mysqli_query($db, $sql_delete_2)) {
				echo "[$sql_delete_2]<br>";
				toLog('error', '0', $id_oud, "Error verwijderen kenmerken (zijn identiek aan $id_new)");
			} else {
				toLog('info', '0', $id_oud, "Kenmerken verwijderd (zijn identiek aan $id_new)");
			}
			
			# Het oude huis uit de tabel met resultaten halen (de nieuwe staat er al in)
			$sql_delete_3 = "DELETE FROM $TableResultaat WHERE $ResultaatID like '$id_oud'";
			if(!mysqli_query($db, $sql_delete_3)) {
				echo "[$sql_delete_3]<br>";
				toLog('error', '0', $id_oud, "Error verwijderen van $id_oud in opdracht");
			} else {
				toLog('info', '0', $id_oud, "Verwijderd uit opdracht (is nu $id_new)");
			}
			$verwijderd = true;
			$counter++;
		}
		
		$Item  = "<table width='100%'>\n";
		if(!$verwijderd) {
			$Item .= "<tr>\n";
			$Item .= "	<td align='center' colspan='2'><b>Niet verwijderd, negatief aantal dagen offline geweest</b></td>\n";
			$Item .= "</tr>\n";
		}		
		$Item .= "<tr>\n";
		$Item .= "	<td align='center'><img src='". changeThumbLocation(urldecode($data_oud['thumb'])) ."'></td>\n";
		$Item .= "	<td align='center'><img src='". changeThumbLocation(urldecode($data_new['thumb'])) ."'></td>\n";
		$Item .= "</tr>\n";
		$Item .= "<tr>\n";
		# Een hyperlink naar huizen die offline zijn heeft geen zin, dus alleen bij het bewaarde huis staat een link
		$Item .= "	<td align='center'>". urldecode($data_oud['adres']) ."<br>$id_oud</td>\n"; 
		$Item .= "	<td align='center'><a href='http://www.funda.nl/". $id_new ."'>". urldecode($data_new['adres']) ."</a><br>$id_new</td>\n";
		$Item .= "</tr>\n";
		$Item .= "<tr>\n";
		$Item .= "	<td align='center'>". date("d-m-y", $data_oud['start']) .' t/m '. date("d-m-y", $data_oud['eind']) ."</td>\n";
		$Item .= "	<td align='center'>". date("d-m-y", $data_new['start']) .' t/m '. date("d-m-y", $data_new['eind']) ."</td>\n";
		$Item .= "</tr>\n";
		$Item .= "<tr>\n";
		$Item .= "	<td align='center'>". $data_oud['makelaar'] ."</td>\n";
		$Item .= "	<td align='center'>". $data_new['makelaar'] ."</td>\n";
		$Item .= "</tr>\n";
		
		if(!$verwijderd) {
			$Item .= "<tr>\n";
			$Item .= "	<td align='center'><a href='". $ScriptURL ."admin/combine_manual.php?id_1=$id_oud&id_2=$id_new'>deze verwijderen</a> | <a href='". $ScriptURL ."admin/changeState.php?state=ignore&id=$id_oud'>deze negeren</a></td>\n";
			$Item .= "	<td align='center'><a href='". $ScriptURL ."admin/combine_manual.php?id_1=$id_new&id_2=$id_oud'>deze verwijderen</a> | <a href='". $ScriptURL ."admin/changeState.php?state=ignore&id=$id_new'>deze negeren</a></td>\n";
			$Item .= "</tr>\n";
		}
		$Item .= "</table>\n";
		
		if(!$verwijderd) {
			$HTMLMessageNeg[] = showBlock($Item);
		} else {
			$HTMLMessage[] = showBlock($Item);
		}
	}
	
	if(count($HTMLMessage) > 0 AND !$manual) {
		$HTMLMessage = array_merge($HTMLMessage, $HTMLMessageNeg);
		
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
		$mail->AddAddress($ScriptMailAdress, 'Matthijs');
		$mail->Subject	= $SubjectPrefix. "Funda opruiming";
		$mail->IsHTML(true);
		$mail->Body			= $HTMLMail;
		
		if(!$mail->Send()) {
			echo "Versturen van mail is mislukt<br>";
			toLog('error', '0', '0', "Fout met mail nav opschoonwerkzaamheden");		
		} else {
			toLog('info', '0', '0', "Mail nav opschoonwerkzaamheden verstuurd");
		}
		
		send2Pushover(array('title' => 'Opschoonwerkzaamheden', 'message' => $counter .' huizen opgeruimd'), array(1));
	}
} else {
	echo "Geen werk aan de winkel<br>";
}
