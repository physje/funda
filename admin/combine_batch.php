<?php
include_once(__DIR__. '/../include/config.php');
include_once(__DIR__. '/../general_includes/class.phpmailer.php');
include_once(__DIR__. '/../general_includes/class.html2text.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

$manual = false;
$HTMLMessageNeg = $HTMLMessage = array();
$key_1 = null;
$key_2 = null;
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
	//$sql .= "$TableHuizen.$HuizenEind < $eindGrens AND ";
	$sql .= "$TableHuizen.$HuizenOffline like '1'";
	
	$result	= mysql_query($sql);
	$row = mysql_fetch_array($result);

	do {
		$adres		= $row[$HuizenAdres];
		$PC				= $row[$HuizenPC_c];
		$id_oud		= $row[$HuizenID];		
		$sql_2		= "SELECT * FROM $TableHuizen WHERE $HuizenAdres like '$adres' AND $HuizenPC_c like '$PC' AND $HuizenID NOT like '$id_oud'";
		$result_2	= mysql_query($sql_2);
		
		if(mysql_num_rows($result_2) >= 1 AND !in_array($id_oud, $KeyArray) AND !ignoreHouse4Combine($id_oud)) {
			$row_2	= mysql_fetch_array($result_2);
			$id_new	= $row_2[$HuizenID];
				
			if(!in_array($id_new, $KeyArray)) {
				$key_1[$i] = $id_oud;
				$key_2[$i] = $id_new;
			
				$i++;
				$KeyArray[] = $id_oud;
				$KeyArray[] = $id_new;
			}
		}
	} while($row = mysql_fetch_array($result));
}

if(is_array($key_1)) {
	foreach($key_1 as $key => $value) {
		$verwijderd = false;
		$id_oud = $key_1[$key];
		$id_new = $key_2[$key];
		
		$data_oud = getFundaData($id_oud);
		$data_new = getFundaData($id_new);
						
		# Huizen die niet een paar dagen offline zijn geweest zijn 'verdacht' en worden dus niet automatisch samengevoegd
		if(($data_new['start'] - $data_oud['eind']) > 0 OR $manual) {
			# Actie-lijst :
			#		Vervang begintijd_2 door begintijd_1		
			#		Vervang ID_1 door ID_2 in prijzen-, open huis- en lijsten-tabel
			# 	Verwijder key_1 in huizen-, kenmerken- en resultaten-tabel
						
			# De begin- en eindtijd voor het nieuwe huis in tabel met huizen updaten
			# Neem de vroegst bekende starttijd en de laatst bekende eindtijd
			$sql_update_1 = "UPDATE $TableHuizen SET $HuizenStart = ". min($data_oud['start'], $data_new['start']) .", $HuizenEind = ". max($data_oud['eind'], $data_new['eind']) ." WHERE $HuizenID like '". $id_new ."'";
			if(!mysql_query($sql_update_1)) {
				echo "[$sql_update_1]<br>";		
				toLog('error', '', $id_oud, "Error verplaatsen data van $id_oud naar $id_new");
			} else {
				toLog('info', '', $id_oud, "Data van $id_oud verplaatst naar $id_new");
				toLog('info', '', $id_new, "Data van $id_oud toegevoegd.");
			}
			
			# Tabel met prijzen updaten
			$sql_update_2 = "UPDATE $TablePrijzen SET $PrijzenID = '$id_new' WHERE $PrijzenID like '$id_oud'";
			if(!mysql_query($sql_update_2)) {
				echo "[$sql_update_2]<br>";
				toLog('error', '', $id_oud, "Error toewijzen prijzen aan $id_new");
			} else {
				toLog('info', '', $id_oud, "Prijzen toewijzen aan $id_new");
			}
			
			# Tabel met lijsten updaten
			$sql_update_3 = "UPDATE $TableListResult SET $ListResultHuis = '$id_new' WHERE $ListResultHuis like '$id_oud'";
			if(!mysql_query($sql_update_3)) {
				echo "[$sql_update_3]<br>";
				toLog('error', '', $id_oud, "Error toewijzen $id_new op lijst");
			} else {
				toLog('info', '', $id_oud, "$id_new toegewezen op lijst");
			}
			
			# Tabel met open huizen updaten
			$sql_update_4 = "UPDATE $TableCalendar SET $CalendarHuis = '$id_new' WHERE $CalendarHuis like '$id_oud'";
			if(!mysql_query($sql_update_4)) {
				echo "[$sql_update_4]<br>";
				toLog('error', '', $id_oud, "Error toewijzen open huis aan $id_new");
			} else {
				toLog('info', '', $id_oud, "Open huizen toewijzen aan $id_new");
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
				echo "[$sql_delete_3]<br>";
				toLog('error', '', $id_oud, "Error verwijderen van $id_oud in opdracht");
			} else {
				toLog('info', '', $id_oud, "Verwijderd uit opdracht (is nu $id_new)");
			}
			$verwijderd = true;
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
			$Item .= "	<td align='center'><a href='". $ScriptURL ."admin/combine_manual.php?id_1=$id_oud&id_2=$id_new'>deze verwijderen</a></td>\n";
			$Item .= "	<td align='center'><a href='". $ScriptURL ."admin/combine_manual.php?id_1=$id_new&id_2=$id_oud'>deze verwijderen</a></td>\n";
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
		
		$html =& new html2text($HTMLMail);
		$html->set_base_url($ScriptURL);
		$PlainText = $html->get_text();
			
		$mail = new PHPMailer;
		$mail->From     = $ScriptMailAdress;
		$mail->FromName = $ScriptTitle;
		$mail->AddAddress($ScriptMailAdress, 'Matthijs');
		$mail->Subject	= $SubjectPrefix. "Funda opruiming";
		$mail->IsHTML(true);
		$mail->Body			= $HTMLMail;
		$mail->AltBody	= $PlainText;
		
		//echo $HTMLMail;
		
		if(!$mail->Send()) {
			echo "Versturen van mail is mislukt<br>";
			toLog('error', '', '', "Fout met mail nav opschoonwerkzaamheden");		
		} else {
			toLog('info', '', '', "Mail nav opschoonwerkzaamheden verstuurd");
		}
		
		send2Pushover(array('title' => 'Opschoonwerkzaamheden', 'message' => count($HTMLMessage) .' huizen opgeruimd'), array(1));
	}
} else {
	echo "Geen werk aan de winkel";
	//toLog('info', '', '', "Geen opschoonwerkzaamheden verricht");
}
