<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>\n";

# Overzicht opvragen van alle zoekopdrachten
if($_SESSION['level'] > 1) {
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '', false);
} else {
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '');
}

# Abbonementen aanpassen
if(isset($_POST['mail']) AND isset($_POST['push'])) {
	foreach($Opdrachten as $opdracht) {
		removeMember4Opdracht($opdracht, $_SESSION['account'], 'mail');
		removeMember4Opdracht($opdracht, $_SESSION['account'], 'push');
		
		if(array_key_exists($opdracht, $_POST['mail']))	addMember2Opdracht($opdracht, $_SESSION['account'], 'mail');
		if(array_key_exists($opdracht, $_POST['push']))	addMember2Opdracht($opdracht, $_SESSION['account'], 'push');
	}
}

if(isset($_POST['doorgaan'])) {
	if(isset($_REQUEST['id']) AND $_REQUEST['id'] != 0) {
		$sql_opdracht = "UPDATE $TableZoeken SET $ZoekenUser = '". $_SESSION['account'] ."', $ZoekenNaam = '". urlencode($_POST['naam']) ."', $ZoekenURL = '". urlencode($_POST['url']) ."' WHERE $ZoekenKey = ". $_POST['id'];
		mysqli_query($db, "DELETE FROM $TableVerdeling WHERE $VerdelingOpdracht = ". $_REQUEST['id']);
	} else {
		$sql_opdracht = "INSERT INTO $TableZoeken ($ZoekenUser, $ZoekenNaam, $ZoekenURL) VALUES ('". $_SESSION['account'] ."', '". urlencode($_POST['naam']) ."', '". urlencode($_POST['url']) ."')";
	}
			
	if(!mysqli_query($db, $sql_opdracht)) {
		$Page .= $sql_opdracht;
	} else {
		$OpdrachtID = mysqli_insert_id();
		addMember2Opdracht($OpdrachtID, $_SESSION['account'], 'mail');
	}
	
	if(isset($_REQUEST['lichting'])) {
		foreach($_REQUEST['lichting'] as $key => $value) {
			if($value == 1) {
				mysqli_query($db, "INSERT INTO $TableVerdeling ($VerdelingUur, $VerdelingOpdracht) VALUES ($key, ". $_REQUEST['id'] .")");
			}
		}
	}
	
	$Page .= "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
} elseif(isset($_REQUEST['delete_opdracht'])) {
	if(isset($_POST['delete_yes'])) {				
		$Huizen = getHuizen($_POST['opdracht']);
		
		foreach($Huizen as $huis) {
			$sql_check_unique = "SELECT * FROM $TableResultaat WHERE $ResultaatID like '$huis' AND $ResultaatZoekID NOT like ". $_POST['opdracht'];
			$result	= mysqli_query($db, $sql_check_unique);
						
			if(mysqli_num_rows($result) == 0) {
				$sql_delete_huis		= "DELETE FROM $TableHuizen WHERE $HuizenID like ". $huis;
				if(!mysqli_query($db, $sql_delete_huis)) $Page .= $sql_delete_huis.'<br>';
				
				$sql_delete_kenmerk	= "DELETE FROM $TableKenmerken WHERE $KenmerkenID like ". $huis;
				if(!mysqli_query($db, $sql_delete_kenmerk)) $Page .= $sql_delete_kenmerk.'<br>';
				
				$sql_delete_prijs		= "DELETE FROM $TablePrijzen WHERE $PrijzenID like ". $huis;
				if(!mysqli_query($db, $sql_delete_prijs)) $Page .= $sql_delete_prijs.'<br>';
				
				$sql_delete_list		= "DELETE FROM $TableListResult WHERE $ListResultHuis like ". $huis;
				if(!mysqli_query($db, $sql_delete_list)) $Page .= $sql_delete_list.'<br>';
			}			
		}
		
		$sql_delete_huizen = "DELETE FROM $TableResultaat WHERE $ResultaatZoekID like ". $_POST['opdracht'];
		if(!mysqli_query($db, $sql_delete_huizen)) $Page .= $sql_delete_huizen.'<br>';	
		
		$sql_delete_abbo = "DELETE FROM $TableAbo WHERE $AboZoekID like ". $_POST['opdracht'];
		if(!mysqli_query($db, $sql_delete_abbo)) $Page .= $sql_delete_abbo.'<br>';	
		
		$sql_delete_opdracht = "DELETE FROM $TableZoeken WHERE $ZoekenKey like ". $_POST['opdracht'];
		if(!mysqli_query($db, $sql_delete_opdracht)) $Page .= $sql_delete_opdracht.'<br>';	
				
		$Page .= "De lijst incl. huizen is verwijderd";
	} elseif(isset($_POST['delete_no'])) {	
		$Page = "Gelukkig !";
		
	# Weet je het heeeel zeker
	} else {
		$Page = "Weet u zeker dat u deze opdracht wilt verwijderen ?";
		$Page .= "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
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
	
	$Page ="<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
	
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
	$MemberData = getMemberDetails($_SESSION['account']);
	if($MemberData['userkey'] != '' AND $MemberData['token'] != '') {
		$disabled = '';
	} else {
		$disabled = ' disabled';
	}
	
	$Page = '';
	$Page .= "<form method='post'>".NL;
	$Page .= "<table border=0>".NL;
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='10'>&nbsp;</td>";
	//$Page .= "	<td align='center'><img src='../images/mail_yes.gif'></td>";
	$Page .= "	<td align='center'><img src='../images/pushover.png'></td>";
	$Page .= "</tr>\n";
		
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		$Abonnees = getMembers4Opdracht($OpdrachtID, 'mail');
		$POMembers = getMembers4Opdracht($OpdrachtID, 'push');
						
		if(count(getOpdrachtUren($OpdrachtID)) == 0) {
			$active = false;
			$class = 'offline';
		} else {
			$active = true;
			$class = 'online';
		}
		
		$Page .= "<tr>".NL;
		$Page .= "	<td>". ($_SESSION['level'] > 1 ? "<a href='?id=$OpdrachtID' title=\"wijzig '". $OpdrachtData['naam'] ."'\" class='$class'>" : '' ) . $OpdrachtData['naam'] . ($_SESSION['level'] > 1 ? "</a>" : '') ."</td>".NL;
				
		if($active) {
			$Page .= "	<td>&nbsp;</td>".NL;
			
			if($_SESSION['level'] > 1) {
				$Page .= "	<td><a href='../check.php?OpdrachtID=$OpdrachtID'><img src='../images/new.ico' width='16' height='16' title=\"Voer '". $OpdrachtData['naam'] ."' uit\"></a></td>".NL;
				$Page .= "	<td>&nbsp;</td>".NL;
				$Page .= "	<td><a href='". $OpdrachtData['url'] ."'><img src='../images/renew.png' width='16' height='16' title=\"Bekijk '". $OpdrachtData['naam'] ."' op funda.nl\"></a></td>".NL;
				$Page .= "	<td>&nbsp;</td>".NL;
				//$Page .= "	<td><a href='getVerkochteHuizen.php?OpdrachtID=$OpdrachtID'><img src='../images/sold.ico' title=\"Zoek naar verkochte huizen voor '". $OpdrachtData['naam'] ."'\"></a></td>".NL;
				//$Page .= "	<td>&nbsp;</td>".NL;
				$Page .= "	<td><a href='invite.php?OpdrachtID=$OpdrachtID'><img src='../images/invite.gif' title=\"Nodig iemand uit voor '". $OpdrachtData['naam'] ."'\"></a></td>".NL;
				$Page .= "	<td>&nbsp;</td>".NL;
				$Page .= "	<td><a href='bekijkHuizenZoeker.php?selectie=Z$OpdrachtID'><img src='../images/huizenzoeker.png' title=\"Zoek naar ontbrekende gegevens voor '". $OpdrachtData['naam'] ."' op huizenzoeker.nl\"></a></td>".NL;
				$Page .= "	<td>&nbsp;</td>".NL;
				//$Page .= "	<td><a href='../../../download/". str_replace(' ', '-', $ScriptTitle) .'_Open-Huis_'. removeFilenameCharacters($OpdrachtData['naam']) .".ics'><img src='../images/ical.png' title=\"Bekijk de huizen met Open Huis in iCal-formaat voor '". $OpdrachtData['naam'] ."'\"></a></td>".NL;
				//$Page .= "	<td>&nbsp;</td>".NL;
				
			}
			
			//$Page .= "	<td><input type='checkbox' name='mail[$OpdrachtID]' value='1'". (in_array($_SESSION['account'] ,$Abonnees) ? ' checked' : '') ." title=\"Aanvinken om mails voor '". $OpdrachtData['naam'] ."' te ontvangen\" onChange=\"this.form.submit()\"></td>".NL;
			$Page .= "	<td><input type='checkbox' name='push[$OpdrachtID]' value='1'". (in_array($_SESSION['account'] ,$POMembers) ? ' checked' : '') ." title=\"Aanvinken om push-berichten voor '". $OpdrachtData['naam'] ."' te ontvangen\" onChange=\"this.form.submit()\"$disabled></td>".NL;
		} else {
			$Page .= "	<td colspan='15'>&nbsp;</td>".NL;
		}
		
		$Page .= "</tr>".NL;
	}	
	$Page .= "</table>".NL;
	$Page .= "</form>".NL;
	
	if($_SESSION['level'] > 1) {
		$Page .= "<p>\n<a href='?id=0' title='Maak een nieuwe opdracht aan'>Nieuw</a><br>".NL;
	}
}

echo showBlock($Page);
echo "	</td>\n";
echo "</tr>\n";
echo $HTMLFooter;