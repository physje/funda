<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

# Lijst verwijderen
if(isset($_POST['delete_list'])) {
	if(isset($_POST['delete_yes'])) {
		$sql_delete_list = "DELETE FROM $TableList WHERE $ListID like ". $_POST['list'];
		mysql_query($sql_delete_list);
		
		$sql_delete_result = "DELETE FROM $TableListResult WHERE $ListResultList like ". $_POST['list'];
		mysql_query($sql_delete_result);
		
		$Page_1 = "De lijst incl. huizen is verwijderd";
	} elseif(isset($_POST['delete_no'])) {	
		$Page_1 = "Gelukkig !";
		
	# Weet je het heeeel zeker
	} else {
		$Page_1 = "Weet u zeker dat u deze lijst wilt verwijderen ?";
		$Page_1 .= "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
		$Page_1 .= "<input type='hidden' name='delete_list' value='true'>\n";
		$Page_1 .= "<input type='hidden' name='list' value='". $_POST['list'] ."'>\n";
		$Page_1 .= "<input type='submit' name='delete_yes' value='Ja'> <input type='submit' name='delete_no' value='Nee'>";
		$Page_1 .= "</form>";
	}
	
	if(isset($_POST['delete_yes']) || isset($_POST['delete_no'])) {
		$Page_1 .= "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";
	}

# Wijzigingen in lijst-gegevens opslaan
} elseif(isset($_POST['save_list'])) {
	$actie = saveUpdateList($_POST['list'], $_SESSION['UserID'], $_POST['actief'], $_POST['naam']);
	
	if(is_numeric($actie) OR $actie != false) {
		$Page_1 .= "Lijst opgeslagen";
	}

	$Page_1 .= "<p><a href='". $_SERVER["PHP_SELF"] ."'>Start</a>";

# Array met huizen voor lijst toekennen aan lijst nadat eerst de lijst 'geleegd' is
} elseif(isset($_POST['save_houses'])) {
	$sql_delete = "DELETE FROM $TableListResult WHERE $ListResultList like ". $_POST['list'];
	mysql_query($sql_delete);
	
	foreach($_POST['huis'] as $huis) {
		$Page_1 .= addHouse2List($huis, $_POST['list']);
	}
	
# Huis op basis van ingevoerd adres toevoegen
} elseif(isset($_POST['add_house'])) {
	$elementen = getString('[', ']', $_POST['extra_huis'], 0);	
	$Page_1 .= addHouse2List($elementen[0], $_POST['list']);
	
# Wijzigingsformulier tonen
} elseif(isset($_REQUEST['list'])) {
	$list = $_REQUEST['list'];
	
	# Door de boolean $autocomplete op true te zetten wordt javascript-code opgenomen op de pagina.
	$autocomplete = true;
	
	$Page_1 ="<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	
	# Als er een lijst-id bekend is kunnen er knoppen getoond worden om hier huizen aan toe te kennen
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

		$Page_3 .="<a href='bekijkHuizenzoeker.php?selectie=L$list'>Voer aan HuizenZoeker.nl</a>";
		$Page_3 .="<p>";
		$Page_3 .="<a href='renewData.php?selectie=L$list'>Haal alle funda-gegevens opnieuw op</a>";

	}
	
	# Formulier om lijst-gegevens te wijzigen
	$Page_1 .= "<table border=0>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td><input type='checkbox' name='actief' value='1' ". ($data['active'] == 1 ? ' checked' : '') ."></td>\n";
	$Page_1 .= "	<td>Actief</td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td>Naam :</td>\n";
	$Page_1 .= "	<td><input type='text' name='naam' value='". $data['naam'] ."' size='45'></td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td colspan='2'>&nbsp;</td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "<tr>\n";
	$Page_1 .= "	<td colspan='2'><table width='100%'><tr><td><input type='submit' name='save_list' value='Lijst-details opslaan'></td><td align='right'><input type='submit' name='delete_list' value='Lijst verwijderen'></td></tr></table></td>\n";
	$Page_1 .= "</tr>\n";
	$Page_1 .= "</table>\n";
	$Page_1 .= "</form>\n";
	
	# Als er een lijst-id bekend is kunnen huizen op de lijst getoond worden en kunnen er nieuwe huizen worden toegevoegd.
	if($list != 0) {		
		$Huizen = getLijstHuizen($list);
		
		# Als er huizen op deze lijst staan moeten die getoond worden
		if(count($Huizen) > 0) {
			$Page_2 ="<form method='post' action='$_SERVER[PHP_SELF]'>\n";
			$Page_2 .= "<input type='hidden' name='list' value='$list'>\n";
		
			foreach($Huizen as $huis) {
				$data = getFundaData($huis);
				$Page_2 .= "<input type='checkbox' name='huis[]' value='$huis' checked> <a href='http://funda.nl/$huis' target='_blank' class='$class'>". $data['adres'] ."</a><br>\n";
			}
				
			$Page_2 .= "<br>\n";
			$Page_2 .= "<input type='submit' name='save_houses' value='Huizen opslaan'>\n";
			$Page_2 .= "</form>\n";
		}
		
		# Formulier met autocomplete-textveld om handmatig een adres in te voeren		
		$Page_4 ="<form method='post' action='$_SERVER[PHP_SELF]'>\n";
		$Page_4 .= "<input type='hidden' name='list' value='$list'>\n";
		$Page_4 .= "Voer adres of funda_id in om handmatig een huis toe te voegen.<br>\n";
		$Page_4 .= "<input type='text' name='extra_huis' id=\"huizen\" size='50'><br>";
		$Page_4 .= "<br>\n";
		$Page_4 .= "<input type='submit' name='add_house' value='Huis toevoegen'>\n";
		$Page_4 .= "</form>\n";		
	} else {
		$Page_2 = "";
		$Page_4 = "";
	}
	
# Overzicht van alle lijsten tonen
} else {
	$Lijsten = getLijsten($_SESSION['UserID'], '');
		
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

include('../include/HTML_TopBottom.php');

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
?>
