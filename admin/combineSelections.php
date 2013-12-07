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

if(isset($_POST['combine'])) {	
	$groep_1	= substr($_REQUEST['selectie_1'], 0, 1);
	$groep_2	= substr($_REQUEST['selectie_2'], 0, 1);
	$id_1			= substr($_REQUEST['selectie_1'], 1);	
	$id_2			= substr($_REQUEST['selectie_2'], 1);
	
	if($groep_1 == 'Z') {		
		$opdrachtData	= getOpdrachtData($id_1);
		$Name_1				= $opdrachtData['naam'];
		$dataset_1		= getHuizen($id_1);
	} else {
		$LijstData		= getLijstData($id_1);
		$Name_1				= $LijstData['naam'];
		$dataset_1		= getLijstHuizen($id_1);
	}
	
	if($groep_2 == 'Z') {		
		$opdrachtData	= getOpdrachtData($id_2);
		$Name_2				= $opdrachtData['naam'];
		$dataset_2		= getHuizen($id_2);
	} else {
		$LijstData		= getLijstData($id_2);
		$Name_2				= $LijstData['naam'];
		$dataset_2		= getLijstHuizen($id_2);
	}
	
	if($_POST['exclVerkocht'] == '1') {
		$exclVerkocht = true;
	} else {
		$exclVerkocht = false;
	}
	
	if($_POST['exclVerkochtOV'] == '1') {
		$exclVerkochtOV = true;
	} else {
		$exclVerkochtOV = false;
	}
	
	$newDataset = array();
	
	# Huis zowel in selectie 1 als in selectie 2
	if($_POST['combineType'] == '1') {
		foreach($dataset_1 as $huisID_1) {
			if(in_array($huisID_1, $dataset_2)) {
				$data = getFundaData($huisID_1);
				if(($data['verkocht'] == 0) OR ($data['verkocht'] == 1 AND !$exclVerkocht) OR ($data['verkocht'] == 2 AND !$exclVerkochtOV)) {
					$newDataset[] = $huisID_1;
				}
			}			
		}		
		$nieuwNaam = "zowel $Name_1 als $Name_2";
	}
	
	# Huis wél in selectie 1 maar níet in selectie 2
	if($_POST['combineType'] == '2') {
		foreach($dataset_1 as $huisID_1) {
			if(!in_array($huisID_1, $dataset_2)) {
				$data = getFundaData($huisID_1);
				if(($data['verkocht'] == 0) OR ($data['verkocht'] == 1 AND !$exclVerkocht) OR ($data['verkocht'] == 2 AND !$exclVerkochtOV)) {
					$newDataset[] = $huisID_1;
				}
			}	
		}
		$nieuwNaam = "wel $Name_1 niet $Name_2";
	}
	
	# Huis óf in selectie 1 óf in selectie 2
	if($_POST['combineType'] == '3') {
		$newDataset = $dataset_1;
				
		foreach($dataset_2 as $huisID_2) {
			if(!in_array($huisID_2, $newDataset)) {
				$newDataset[] = $huisID_2;
			}			
		}
		
		$nieuwNaam = "$Name_1 of $Name_2";
	}
	
	if(count($newDataset) > 0){		
		$lijstID = saveUpdateList('', $_SESSION['UserID'], 1, $nieuwNaam);
		
		foreach($newDataset as $huis) {		
			$deel_2 .= addHouse2List($huis, $lijstID);
		}
		
		$deel_1 = "<p>Selectie opgeslagen als <a href='edit_lijsten.php?list=$lijstID'>$nieuwNaam</a>";
	} else {
		$deel_1 = "<p>Selectie bevat geen huizen";
	}	
} else {	
	$Opdrachten = getZoekOpdrachten($_SESSION['account'], '');
	$Lijsten		= getLijsten($_SESSION['UserID'], 1, true);

	$list[] = "	<optgroup label='Zoekopdrachten'>";	
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		$list[] = "	<option value='Z$OpdrachtID'>". $OpdrachtData['naam'] ."</option>";
	}
	
	$list[] = "	</optgroup>\n";
	$list[] = "	<optgroup label='Lijsten'>";	
	
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		$list[] = "	<option value='L$LijstID'>". $LijstData['naam'] ."</option>";
	}	
	
	$list[] = "	</optgroup>";

	if($_SESSION['account'] != $_SESSION['UserID']) {
		$Lijsten_2	= getLijsten($_SESSION['account'], 1);
		$MemberData = getMemberDetails($_SESSION['account']);
	
		$list[] = "	<optgroup label='Lijsten van ". $MemberData['naam'] ."'>\n";
	
		foreach($Lijsten_2 as $LijstID) {
			$LijstData = getLijstData($LijstID);
			$list[] = "	<option value='L$LijstID'>". $LijstData['naam'] ."</option>\n";
		}
	
		$list[] = "	</optgroup>\n";
	}

	$deel_1 = "<form method='post' action='$_SERVER[PHP_SELF]'>\n";	
	$deel_1 .= "<table border=0>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td width='450'>Selectie 1</td>\n";	
	$deel_1 .= "	<td width='40'>&nbsp;</td>\n";
	$deel_1 .= "	<td width='450'>Selectie 2</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>\n";
	$deel_1 .= "	<select name='selectie_1'>\n";
	$deel_1 .= implode(NL, $list);	
	$deel_1 .= "	</select>\n";
	$deel_1 .= "	</td>\n";	
	$deel_1 .= "	<td>&nbsp;</td>\n";
	$deel_1 .= "	<td>\n";
	$deel_1 .= "	<select name='selectie_2'>\n";
	$deel_1 .= implode(NL, $list);	
	$deel_1 .= "	</select>\n";
	$deel_1 .= "	</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td colspan='3'>&nbsp;</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td>\n";
	$deel_1 .= " 		<input type='radio' name='combineType' value='1'> Huis zowel in selectie 1 als in selectie 2<br>\n";
	$deel_1 .= "		<input type='radio' name='combineType' value='2'> Huis wél in selectie 1 maar níet in selectie 2<br>\n";
	$deel_1 .= "		<input type='radio' name='combineType' value='3'> Huis óf in selectie 1 óf in selectie 2<br>\n";
	$deel_1 .= "	</td>\n";	
	$deel_1 .= "	<td>&nbsp;</td>\n";
	$deel_1 .= "	<td valign='top'>\n";
	$deel_1 .= "		<input type='checkbox' name='exclVerkocht' value='1' checked> Verkochte huizen excluderen<br>\n";
	$deel_1 .= "		<input type='checkbox' name='exclVerkochtOV' value='1'> Onder voorbehoud verkochte huizen excluderen<br>\n";
	$deel_1 .= "	</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td colspan='3'>&nbsp;</td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "<tr>\n";
	$deel_1 .= "	<td colspan='3' align='center'><input type='submit' name='combine' value='Weergeven'></td>\n";
	$deel_1 .= "</tr>\n";
	$deel_1 .= "</table>\n";
	$deel_1 .= "</form>\n";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
if($deel_2 != "") {
	echo "</td><td width='50%' valign='top' align='center'>\n";
 echo showBlock($deel_2);
}
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

?>