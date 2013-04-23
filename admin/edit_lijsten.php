<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$autocomplete = true;
include('../include/HTML_TopBottom.php');

if(isset($_POST['save_list'])) {
	if(isset($_REQUEST['list']) AND $_REQUEST['list'] != 0) {
		$sql = "UPDATE $TableList SET $ListActive = '". ($_POST['actief'] == '1' ? '1' : '0') ."', $ListNaam = '". urlencode($_POST['naam']) ."' WHERE $ListID = ". $_POST['list'];
	} else {
		$sql = "INSERT INTO $TableList ($ListActive, $ListNaam) VALUES ('". ($_POST['actief'] == '1' ? '1' : '0') ."', '". urlencode($_POST['naam']) ."')";
	}
			
	if(!mysql_query($sql)) {
		$Page_1 .= $sql;
	}
	
	$Page_1 .= "<a href='?'>Start</a>";
} elseif(isset($_POST['save_houses'])) {
	$sql_delete = "DELETE FROM $TableListResult WHERE $ListResultList like ". $_POST['list'];
	mysql_query($sql_delete);
	
	foreach($_POST['huis'] as $huis) {
		switch(addHouse2List($huis, $_POST['lijst'])) {
			case 0:
				echo '<b>'. $huis .' niet toegevoegd</b><br>';
				break;
			case 1:
				echo $huis .' toegevoegd<br>';
				break;
			case 2:
				break;
			default:
				echo 'Ongeldige output<br>';
		}			
	}
} elseif(isset($_POST['add_house'])) {
	$elementen = getString('[', ']', $_POST['extra_huis'], 0);	
	$output = addHouse2List($elementen[0], $_POST['lijst']);
	echo $huis .' toegevoegd<br>';
} elseif(isset($_REQUEST['list'])) {
	$list = $_REQUEST['list'];
	
	$Page_1 ="<form method='post' name='editform'>\n";
	
	if($list != 0) {
		$data = getLijstData($list);
		$Page_1 .= "<input type='hidden' name='list' value='$list'>\n";
		
		$InputPage = "<input type='hidden' name='addHouses' value='true'>\n";
		$InputPage .= "<input type='hidden' name='chosenList' value='$list'>\n";
				
		$Page_3 .="<form method='post' action='../gallery.php'>\n";
		$Page_3 .= $InputPage;
		$Page_3 .= "<input type='submit' value='Voeg huizen toe in foto-album'>";
		$Page_3 .= "</form>";
		
		$Page_3 .="<form method='post' action='../TimeLine.php'>\n";
		$Page_3 .= $InputPage;
		$Page_3 .= "<input type='submit' value='Voeg huizen toe in tijdslijn'>";
		$Page_3 .= "</form>";
		
		$Page_3 .="<form method='post' action='../PrijsDaling.php'>\n";
		$Page_3 .= $InputPage;
		$Page_3 .= "<input type='submit' value='Voeg huizen toe in prijsdaling'>";
		$Page_3 .= "</form>";
	}
		
	$Page_1 .= "<table>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td><input type='checkbox' name='actief' value='1' ". ($data['active'] == 1 ? ' checked' : '') ."></td>\n";
	$Page_1 .= "	<td>Actief</td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td>Naam :</td>\n";
	$Page_1 .= "	<td><input type='text' name='naam' value='". $data['naam'] ."'></td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td colspan='2'>&nbsp;</td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td colspan='2'><input type='submit' name='save_list' value='Lijst-details opslaan'></td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "</table>\n";
	$Page_1 .= "</form>\n";
		
	if($list != 0) {		
		$Huizen = getLijstHuizen($list);
		
		if(count($Huizen) > 0) {
			$Page_2 ="<form method='post' name='editform'>\n";
			$Page_2 .= "<input type='hidden' name='list' value='$list'>\n";
		
			foreach($Huizen as $huis) {
				$data = getFundaData($huis);
				$Page_2 .= "<input type='checkbox' name='huis[]' value='$huis' checked> <a href='http://www.funda.nl". $data['url'] ."' target='_blank' class='$class'>". $data['adres'] ."</a><br>\n";
			}
				
			$Page_2 .= "<br>\n";
			$Page_2 .= "<input type='submit' name='save_houses' value='Huizen opslaan'>\n";
			$Page_2 .= "</form>\n";
		}
		
		$Page_4 ="<form method='post' name='addform'>\n";
		$Page_4 .= "<input type='hidden' name='lijst' value='$list'>\n";
		$Page_4 .= "Voer adres of funda_id in om handmatig een huis toe te voegen.<br>\n";
		$Page_4 .= "<input type='text' name='extra_huis' id=\"tags\" size='50'><br>";
		$Page_4 .= "<br>\n";
		$Page_4 .= "<input type='submit' name='add_house' value='Huis toevoegen'>\n";
		$Page_4 .= "</form>\n";		
	} else {
		$Page_2 = "";
		$Page_4 = "";
	}
} else {
	$Lijsten = getLijsten('');
		
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		
		if($LijstData['active'] == 0) {
			$class = 'offline';
		} else {
			$class = 'online';
		}
		
		$Page_1 .= "<a href='?list=$LijstID' title='wijzig lijst' class='$class'>". $LijstData['naam'] ."</a>";	
		$Page_1 .= "<br>\n";
	}
	$Page_1 .= "<p>\n<a href='?list=0'>Nieuw</a><br>\n";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "	<td width='50%' valign='top' align='center'>\n";
echo showBlock($Page_1);
if($Page_3 != '') {
	echo "<br>\n";
	echo showBlock($Page_3);
}
echo "	</td>\n";
echo "	<td width='50%' valign='top' align='center'>\n";
if($Page_2 != '') {
	echo showBlock($Page_2);
}
if($Page_4 != '') {
	echo "<br>\n";
	echo showBlock($Page_4);
}
echo "	</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

//echo $Page_4;
?>