<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include('../include/HTML_TopBottom.php');
connect_db();

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td>\n";

if(isset($_POST['doorgaan'])) {
	if(isset($_REQUEST['id']) AND $_REQUEST['id'] != 0) {
		$sql = "UPDATE $TableZoeken SET $ZoekenActive = '". ($_POST['actief'] == '1' ? '1' : '0') ."', $ZoekenMail = '". ($_POST['mail'] == '1' ? '1' : '0') ."', $ZoekenNaam = '". urlencode($_POST['naam']) ."', $ZoekenURL = '". urlencode($_POST['url']) ."', $ZoekenAdres = '". $_POST['adres'] ."' WHERE $ZoekenKey = ". $_POST['id'];
	} else {
		$sql = "INSERT INTO $TableZoeken ($ZoekenActive, $ZoekenMail, $ZoekenNaam, $ZoekenURL, $ZoekenAdres) VALUES ('". ($_POST['actief'] == '1' ? '1' : '0') ."', '". ($_POST['mail'] == '1' ? '1' : '0') ."', '". urlencode($_POST['naam']) ."', '". urlencode($_POST['url']) ."', '". $_POST['adres'] ."')";
	}
			
	if(!mysql_query($sql)) {
		$Page .= $sql;
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
				mysql_query($sql_delete_huis);
				
				$sql_delete_kenmerk	= "DELETE FROM $TableKenmerken WHERE $KenmerkenID like ". $huis;
				mysql_query($sql_delete_kenmerk);
				
				$sql_delete_prijs		= "DELETE FROM $TablePrijzen WHERE $PrijzenID like ". $huis;
				mysql_query($sql_delete_prijs);
				
				$sql_delete_list		= "DELETE FROM $TableListResult WHERE $ListResultHuis like ". $huis;
				mysql_query($sql_delete_list);	
			}				
		}
		
		$sql_delete_opdracht = "DELETE FROM $TableZoeken WHERE $ZoekenKey like ". $_POST['opdracht'];
		mysql_query($sql_delete_opdracht);
		
		$Page = "De lijst incl. huizen is verwijderd";
	} elseif(isset($_POST['delete_no'])) {	
		$Page = "Gelukkig !";
		
	# Weet je het heeeel zeker
	} else {
		$Page = "Weet u zeker dat u deze opdracht wilt verwijderen ?";
		$Page .= "<form method='post'>\n";
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
	
	$Page ="<form method='post' name='editform'>\n";
	
	if($id != 0) {
		$data = getOpdrachtData($id);
		$Page .= "<input type='hidden' name='id' value='$id'>\n";
	}
		
	$Page .= "<table>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td><input type='checkbox' name='actief' value='1' ". ($data['active'] == 1 ? ' checked' : '') ."></td>\n";
	$Page .= "	<td>Actief</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Naam :</td>\n";
	$Page .= "	<td><input type='text' name='naam' value='". $data['naam'] ."'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>URL :</td>\n";
	$Page .= "	<td><input type='text' name='url' value='". $data['url'] ."' size='125'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Email met resultaat :</td>\n";
	$Page .= "	<td><select name='mail'><option value='0'". ($data['mail'] == 0 ? ' selected' : '') .">Nee</option><option value='1'". ($data['mail'] == 1 ? ' selected' : '') .">Ja</option></select></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Emailadres<br>(gescheiden door ;)</td>\n";
	$Page .= "	<td><input type='text' name='adres' value='". ($data['adres'] == '' ? $ScriptMailAdress : $data['adres']) ."' size='125'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='2'>&nbsp;</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='2'><table width='100%'><tr><td><input type='submit' name='doorgaan' value='Opslaan'></td><td align='right'><input type='submit' name='delete_opdracht' value='Verwijderen'></td></tr></table></td>\n";
	$Page .= "</tr>\n";
	$Page .= "</table>\n";
	$Page .= "</form>\n";
} else  {	
	$Opdrachten = getZoekOpdrachten('');
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		
		if($OpdrachtData['active'] == 0) {
			if($OpdrachtData['mail'] == 0) {
				$class = 'offlineVerkocht';
			} else {
				$class = 'offline';
			}
		} elseif($OpdrachtData['mail'] == 0) {
			$class = 'onlineVerkocht';
		} else {
			$class = 'online';
		}
		
		$Page .= "<a href='?id=$OpdrachtID' title='wijzig zoekopdracht' class='$class'>". $OpdrachtData['naam'] ."</a>";
		
		if($OpdrachtData['active'] == 1) {
			$Page .= "&nbsp;<a href='../check.php?OpdrachtID=$OpdrachtID'><img src='http://www.llowlab.nl/wp-content/plugins/tweet-blender/img/ajax-refresh-icon.gif' title='voer zoekopdracht uit'></a>";
		}
		$Page .= "<br>\n";
	}
	$Page .= "<p>\n<a href='?id=0'>Nieuw</a><br>\n";
}

echo showBlock($Page);
echo "	</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>