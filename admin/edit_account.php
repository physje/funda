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

if(isset($_POST['doorgaan'])) {
	if(saveUpdateMember($_POST['member_id'], $_POST['naam'], $_POST['username'], $_POST['password'], $_POST['mail'], $_POST['level'], $_SESSION['UserID'])) {
		$Page .= "Account opgeslagen";
	}
} else {
	if($_SESSION['level'] == 3 AND $_REQUEST['$id'] != '') {
		$id = $_REQUEST['$id'];
	} elseif(isset($_REQUEST['new']) AND $_SESSION['level'] > 1) {
		$id = 0;
	} else {
		$id = $_SESSION['UserID'];
	}
	
	if($id > 0) {
		$data = getMemberDetails($id);
	}
	
	$Page ="<form method='post' name='editform'>\n";
	$Page .="<input type='hidden' name='member_id' value='$id'>\n";
	$Page .= "<table>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Echte naam :</td>\n";
	$Page .= "	<td><input type='text' name='naam' value='". $data['naam'] ."'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Gebruikersnaam :</td>\n";
	$Page .= "	<td><input type='text' name='username' value='". $data['username'] ."' size='50'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Wachtwoord :</td>\n";
	$Page .= "	<td><input type='password' name='password' size='50'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td>Emailadres :</td>\n";
	$Page .= "	<td><input type='text' name='mail' value='". $data['mail'] ."' size='50'></td>\n";
	$Page .= "</tr>\n";
	
	if(($_SESSION['level'] == 2 AND isset($_REQUEST['new'])) OR $_SESSION['level'] == 3) {
		if($_SESSION['level'] == 2) {
			$levels = array_slice($cfgUserLevels, 0, 1, true);
		} else {
			$levels = $cfgUserLevels;
		}
		
		$Page .= "<tr>\n";
		$Page .= "	<td>Account type :</td>\n";
		$Page .= "	<td><select name='level'>\n";
		
		foreach($levels as $key => $value) {
			$Page .= "	<option value='$key'".( $key == $data['level'] ? ' selected' : '') .">$value</option>\n";
		}
		
		$Page .= "	</select></td>\n";
		$Page .= "</tr>\n";	
	} else {
		$Page .="<input type='hidden' name='level' value='". $data['level'] ."'>\n";
	}
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='2'>&nbsp;</td>\n";
	$Page .= "</tr>\n";
	$Page .= "<tr>\n";
	$Page .= "	<td colspan='2'><input type='submit' name='doorgaan' value='Opslaan'></td>\n";
	$Page .= "</tr>\n";
	$Page .= "</table>\n";
	$Page .= "</form>\n";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td width='50%' valign='top' align='center'>\n";
echo showBlock($Page);
echo "	</td>\n";
echo "	<td width='50%' valign='top' align='center'>\n";
if($Page_2 != '') {
	echo showBlock($Page_2);
}
echo "	</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
?>