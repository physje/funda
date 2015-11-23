<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>\n";

if(isset($_REQUEST['action'])) {
	if($_REQUEST['action'] == 'add') {
		addMember2Opdracht($_REQUEST['opdracht'], $_SESSION['account']);
	} else {
		removeMember4Opdracht($_REQUEST['opdracht'], $_SESSION['account']);
	}
}

if(isset($_POST['doorgaan'])) {
	if(isset($_REQUEST['id']) AND $_REQUEST['id'] != 0) {
		$sql_opdracht = "UPDATE $TableZoeken SET $ZoekenUser = '". $_SESSION['account'] ."', $ZoekenNaam = '". urlencode($_POST['naam']) ."', $ZoekenURL = '". urlencode($_POST['url']) ."' WHERE $ZoekenKey = ". $_POST['id'];
		mysql_query("DELETE FROM $TableVerdeling WHERE $VerdelingOpdracht = ". $_REQUEST['id']);
	} else {
		$sql_opdracht = "INSERT INTO $TableZoeken ($ZoekenUser, $ZoekenNaam, $ZoekenURL) VALUES ('". $_SESSION['account'] ."', '". urlencode($_POST['naam']) ."', '". urlencode($_POST['url']) ."')";
	}
			
	if(!mysql_query($sql_opdracht)) {
		$Page .= $sql_opdracht;
	} else {
		$OpdrachtID = mysql_insert_id();
		addMember2Opdracht($OpdrachtID, $_SESSION['account']);
	}
	
	if(isset($_REQUEST['lichting'])) {
		foreach($_REQUEST['lichting'] as $key => $value) {
			if($value == 1) {
				mysql_query("INSERT INTO $TableVerdeling ($VerdelingUur, $VerdelingOpdracht) VALUES ($key, ". $_REQUEST['id'] .")");
			}
		}
	}
	
	$Page .= "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
} elseif(isset($_REQUEST['delete_opdracht'])) {
	if(isset($_POST['delete_yes'])) {				
		$Huizen = getHuizen($_POST['opdracht']);
		
		foreach($Huizen as $huis) {
			$sql_check_unique = "SELECT * FROM $TableResultaat WHERE $ResultaatID like '$huis' AND $ResultaatZoekID NOT like ". $_POST['opdracht'];
			$result	= mysql_query($sql_check_unique);
						
			if(mysql_num_rows($result) == 0) {
				$sql_delete_huis		= "DELETE FROM $TableHuizen WHERE $HuizenID like ". $huis;
				if(!mysql_query($sql_delete_huis)) $Page .= $sql_delete_huis.'<br>';
				
				$sql_delete_kenmerk	= "DELETE FROM $TableKenmerken WHERE $KenmerkenID like ". $huis;
				if(!mysql_query($sql_delete_kenmerk)) $Page .= $sql_delete_kenmerk.'<br>';
				
				$sql_delete_prijs		= "DELETE FROM $TablePrijzen WHERE $PrijzenID like ". $huis;
				if(!mysql_query($sql_delete_prijs)) $Page .= $sql_delete_prijs.'<br>';
				
				$sql_delete_list		= "DELETE FROM $TableListResult WHERE $ListResultHuis like ". $huis;
				if(!mysql_query($sql_delete_list)) $Page .= $sql_delete_list.'<br>';
			}			
		}
		
		$sql_delete_huizen = "DELETE FROM $TableResultaat WHERE $ResultaatZoekID like ". $_POST['opdracht'];
		if(!mysql_query($sql_delete_huizen)) $Page .= $sql_delete_huizen.'<br>';	
		
		$sql_delete_abbo = "DELETE FROM $TableAbo WHERE $AboZoekID like ". $_POST['opdracht'];
		if(!mysql_query($sql_delete_abbo)) $Page .= $sql_delete_abbo.'<br>';	
		
		$sql_delete_opdracht = "DELETE FROM $TableZoeken WHERE $ZoekenKey like ". $_POST['opdracht'];
		if(!mysql_query($sql_delete_opdracht)) $Page .= $sql_delete_opdracht.'<br>';	
				
		$Page .= "De lijst incl. huizen is verwijderd";
	} elseif(isset($_POST['delete_no'])) {	
		$Page = "Gelukkig !";
		
	# Weet je het heeeel zeker
	} else {
		$Page = "Weet u zeker dat u deze opdracht wilt verwijderen ?";
		$Page .= "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
		$Page .= "<input type='hidden' name='delete_opdracht' value='true'>\n";
		$Page .= "<input type='hidden' name='opdracht' value='". $_REQUEST['id'] ."'>\n";
		$Page .= "<input type='submit' name='delete_yes' value='Ja'> <input type='submit' name='delete_no' value='Nee'>";
		$Page .= "</form>";
	}
	
	if(isset($_POST['delete_yes']) || isset($_POST['delete_no'])) {
		$Page .= "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
	}
} elseif(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	$uren = getOpdrachtUren($id);
	
	$Page ="<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	
	if($id != 0) {
		$data = getOpdrachtData($id);
		$Page .= "<input type='hidden' name='id' value='$id'>\n";
	}
		
	$Page .= "<table border=0>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Naam :</td>\n";
	$Page .= "	<td colspan='6'><input type='text' name='naam' value='". $data['naam'] ."'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>URL :</td>\n";
	$Page .= "	<td colspan='6'><input type='text' name='url' value='". $data['url'] ."' size='125'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='7'>&nbsp;</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>&nbsp;</td>\n";
	$Page .= "	<td colspan='6'>Pagina controleren om :</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>&nbsp;</td>\n";
	$Page .= "	<td>";
	
	for($h=0; $h<24; $h++) {
		$Page .= "<input type='checkbox' name='lichting[$h]' value='1'". (in_array($h, $uren) ? ' checked' : '') ."> $h uur<br>\n";
		
		if(fmod(($h+1),4) == 0) {
			$Page .= "</td><td>";
		}
	}
	
	$Page .= "</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='7'>&nbsp;</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='7'><table width='100%'><tr><td><input type='submit' name='doorgaan' value='Opslaan'></td><td align='right'><input type='submit' name='delete_opdracht' value='Verwijderen'></td></tr></table></td>\n";
	$Page .= "</tr>\n";
	$Page .= "</table>\n";
	$Page .= "</form>\n";
} else  {
	if($_SESSION['level'] > 1) {
		$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', false);
	} else {
		$Opdrachten = getZoekOpdrachten($_SESSION['account'], '');
	}
	
	$Page .= "<table>\n";
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		$Abonnees = getMembers4Opdracht($OpdrachtID);
						
		if(count(getOpdrachtUren($OpdrachtID)) == 0) {
			$active = false;
			$class = 'offline';
		} else {
			$active = true;
			$class = 'online';
		}
		
		$Page .= "<tr>\n";
		$Page .= "	<td>". ($_SESSION['level'] > 1 ? "<a href='?id=$OpdrachtID' title=\"wijzig '". $OpdrachtData['naam'] ."'\" class='$class'>" : '' ) . $OpdrachtData['naam'] . ($_SESSION['level'] > 1 ? "</a>" : '') ."</td>";
				
		if($active) {
			$Page .= "	<td>&nbsp;</td>";
			
			if($_SESSION['level'] > 1) {
				$Page .= "	<td><a href='../check.php?OpdrachtID=$OpdrachtID'><img src='http://www.funda.nl/img/favicon/funda.ico' width='16' height='16' title=\"Voer '". $OpdrachtData['naam'] ."' uit\"></a></td>";
				$Page .= "	<td>&nbsp;</td>";
				$Page .= "	<td><a href='renewData.php?selectie=Z$OpdrachtID'><img src='http://www.marinusjansen.nl/images/icon-funda.png' width='16' height='16' title=\"Haal alle data voor '". $OpdrachtData['naam'] ."' opnieuw op\"></a></td>";
				$Page .= "	<td>&nbsp;</td>";
				$Page .= "	<td><a href='getVerkochteHuizen.php?OpdrachtID=$OpdrachtID'><img src='http://www.vuister.com/favicon.ico' title=\"Zoek naar verkochte huizen voor '". $OpdrachtData['naam'] ."'\"></a></td>";				
				$Page .= "	<td>&nbsp;</td>";
				$Page .= "	<td><a href='invite.php?OpdrachtID=$OpdrachtID'><img src='http://www.lessthanfour.org/resources/images/icons/16-inviteGroup.png' title=\"Nodig iemand uit voor '". $OpdrachtData['naam'] ."'\"></a></td>";
				$Page .= "	<td>&nbsp;</td>";
				$Page .= "	<td><a href='bekijkHuizenZoeker.php?selectie=Z$OpdrachtID'><img src='http://cache.websitegegevens.nl/favicons/www.huizenzoeker.nl.png' title=\"Zoek naar ontbrekende gegevens voor '". $OpdrachtData['naam'] ."' op huizenzoeker.nl\"></a></td>";
				$Page .= "	<td>&nbsp;</td>";
				$Page .= "	<td><a href='../../../download/". str_replace(' ', '-', $ScriptTitle) .'_Open-Huis_'. removeFilenameCharacters($OpdrachtData['naam']) .".ics'><img src='http://p2pcanada.ca/wp-content/plugins/oak-events/images/ical.png' title=\"Bekijk de huizen met Open Huis in iCal-formaat voor '". $OpdrachtData['naam'] ."'\"></a></td>";
				$Page .= "	<td>&nbsp;</td>";
				
			}
			if(in_array($_SESSION['account'] ,$Abonnees)) {
				$Page .= "	<td><a href='". $_SERVER["PHP_SELF"] ."?action=remove&opdracht=$OpdrachtID'><img src='http://www.alpem.net/appli/includes/classeLogon/img/mail_yes.gif' title=\"Ik wil géén mails meer ontvangen voor '". $OpdrachtData['naam'] ."'\"></a></td>";
			} else {
				$Page .= "	<td><a href='". $_SERVER["PHP_SELF"] ."?action=add&opdracht=$OpdrachtID'><img src='http://www.alpem.net/appli/includes/classeLogon/img/mail_no.gif' title=\"Ik wil mails ontvangen voor '". $OpdrachtData['naam'] ."'\"></a></td>";
			}
		} else {
			$Page .= "	<td colspan='8'>&nbsp;</td>";
		}
		
		$Page .= "</tr>\n";
	}	
	$Page .= "</table>\n";
	
	if($_SESSION['level'] > 1) {
		$Page .= "<p>\n<a href='?id=0' title='Maak een nieuwe opdracht aan'>Nieuw</a><br>\n";
	}
}

echo showBlock($Page);
echo "	</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>
