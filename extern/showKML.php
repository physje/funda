<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

$bDag		= getParam('bDag', date("d"));
$bMaand	= getParam('bMaand', date("m"));
$bJaar	= getParam('bJaar', date("Y"));
$eDag		= getParam('eDag', date("d"));
$eMaand = getParam('eMaand', date("m"));
$eJaar	= getParam('eJaar', date("Y"));
$selectie	= getParam('selectie', '');

if($_REQUEST['datum'] == 0) {	
	$HTML[] = "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	$HTML[] = "<input type='hidden' name='datum' value='1'>\n";
	$HTML[] = "<table>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td>Begin Datum</td>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "	<td>Eind Datum</td>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "	<td>Groep</td>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "	<td rowspan='2'><input type='submit' name='submit' value='Weergeven'></td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<tr>\n";
	$HTML[] = "	<td><select name='bDag'>\n";
	for($d=1 ; $d<=31 ; $d++)	$HTML[] = "	<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>\n";
	$HTML[] = "	</select>\n";	
	$HTML[] = "	<select name='bMaand'>\n";
	for($m=1 ; $m<=12 ; $m++)	$HTML[] = "	<option value='$m'". ($m == $bMaand ? ' selected' : '') .">$m</option>\n";
	$HTML[] = "	</select>\n";
	$HTML[] = "	<select name='bJaar'>\n";
	for($j=2004 ; $j<=date("Y") ; $j++)	$HTML[] = "	<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>\n";
	$HTML[] = "	</select>\n";
	$HTML[] = "	</td>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "	<td><select name='eDag'>\n";
	for($d=1 ; $d<=31 ; $d++)	$HTML[] = "	<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>\n";
	$HTML[] = "	</select>\n";	
	$HTML[] = "	<select name='eMaand'>\n";
	for($m=1 ; $m<=12 ; $m++)	$HTML[] = "	<option value='$m'". ($m == $eMaand ? ' selected' : '') .">$m</option>\n";
	$HTML[] = "	</select>\n";
	$HTML[] = "	<select name='eJaar'>\n";
	for($j=2004 ; $j<=date("Y") ; $j++)	$HTML[] = "	<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>\n";
	$HTML[] = "	</select>\n";
	$HTML[] = "	</td>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "	<td><select name='selectie'>\n";
	$HTML[] = "	<optgroup label='Zoekopdrachten'>\n";
	
	$Opdrachten = getZoekOpdrachten(1);
	$Lijsten		= getLijsten(1);	
	
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		$HTML[] = "	<option value='Z$OpdrachtID'>". $OpdrachtData['naam'] ."</option>\n";
	}
	
	$HTML[] = "	</optgroup>\n";
	$HTML[] = "	<optgroup label='Lijsten'>\n";
	
	foreach($Lijsten as $LijstID) {
		$LijstData = getLijstData($LijstID);
		$HTML[] = "	<option value='L$LijstID'>". $LijstData['naam'] ."</option>\n";
	}
	
	$HTML[] = "	</optgroup>\n";
	$HTML[] = "	</select>\n";	
	$HTML[] = "	</td>\n";
	$HTML[] = "	<td>&nbsp;</td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "	<td colspan=7><input type=checkbox name=link value=1>Open direct in GoogleMaps ipv downloaden KML-file</td>\n";
	$HTML[] = "</tr>\n";
	$HTML[] = "<table>\n";
	$HTML[] = "</form>\n";
	
	echo $HTMLHeader;
	//echo "<tr>\n";
	//echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("", $HTML));
	//echo "</td><td width='50%' valign='top' align='center'>\n";
	//echo showBlock($deel_3);
	//echo "</td>\n";
	//echo "</tr>\n";
	echo $HTMLFooter;	
} elseif($_REQUEST['link'] == '1') {
	//echo "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL .'extern/showKML.php?datum=1&regio='. $_POST[regio] .'&bDag='. $_POST[bDag] .'&bMaand='. $_POST[bMaand] .'&bJaar='. $_POST[bJaar] .'&eDag='. $_POST[eDag] .'&eMaand='. $_POST[eMaand] .'&eJaar='. $_POST[eJaar]) ."'>link</a>";
	$redirect = "http://maps.google.nl/maps?q=". urlencode($ScriptURL .'extern/showKML.php?datum=1&selectie='. $_POST[selectie] .'&bDag='. $_POST[bDag] .'&bMaand='. $_POST[bMaand] .'&bJaar='. $_POST[bJaar] .'&eDag='. $_POST[eDag] .'&eMaand='. $_POST[eMaand] .'&eJaar='. $_POST[eJaar]);
	$url="Location: ". $redirect;
	header($url);
} else {
	$BeginTijd	= mktime(0, 0, 1, $bMaand, $bDag, $bJaar);
	$EindTijd		= mktime(23, 59, 59, $eMaand, $eDag, $eJaar);
	
	$groep	= substr($selectie, 0, 1);
	$id			= substr($selectie, 1);
	
	if($groep == 'Z') {		
		$opdrachtData	= getOpdrachtData($id);
		$Name					= $opdrachtData['naam'];
		$from					= "$TableResultaat, $TableHuizen";
		$where				= "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $id AND (($TableHuizen.$HuizenEind BETWEEN $BeginTijd AND $EindTijd) OR ($TableHuizen.$HuizenStart BETWEEN $BeginTijd AND $EindTijd))";
	} else {
		$LijstData		= getLijstData($id);
		$Name					= $LijstData['naam'];
		$from					= "$TableListResult, $TableHuizen";
		$where				= "$TableListResult.$ListResultHuis = $TableHuizen.$HuizenID AND $TableListResult.$ListResultList = $id AND (($TableHuizen.$HuizenEind BETWEEN $BeginTijd AND $EindTijd) OR ($TableHuizen.$HuizenStart BETWEEN $BeginTijd AND $EindTijd))";
	}
	
	$KMLTitle = "Nieuwe huizen in $Name van ". date("d-m-Y", $BeginTijd) .' t/m '. date("d-m-Y", $EindTijd);
	include('../include/KML_TopBottom.php');

	$sql_wijk		= "SELECT * FROM $from WHERE $where GROUP BY $TableHuizen.$HuizenWijk ORDER BY $TableHuizen.$HuizenPC_c, $TableHuizen.$HuizenPC_l";
	$result_wijk= mysql_query($sql_wijk);
	$row_wijk		= mysql_fetch_array($result_wijk);
	
	do {
		$wijk = trim($row_wijk[$HuizenWijk]);
		
		$KML_file[] = '<Folder>';
		$KML_file[] = '<open>0</open>';
		$KML_file[] = '	<name>'. urldecode($wijk) .'</name>';
			
		$sql_huis			= "SELECT * FROM $from WHERE $where AND $TableHuizen.$HuizenWijk like '$wijk' ORDER BY $TableHuizen.$HuizenPC_c, $TableHuizen.$HuizenPC_l";
				
		$result_huis	= mysql_query($sql_huis);
		$row_huis			= mysql_fetch_array($result_huis);
	
		do {	
			$KML_file[] = makeKMLEntry($row_huis[$HuizenID]);	
		} while($row_huis = mysql_fetch_array($result_huis));
		
		$KML_file[] = '</Folder>';	
	} while($row_wijk = mysql_fetch_array($result_wijk));
	
	header("Expires: Mon, 26 Jul 2001 05:00:00 GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false); 
	header("Pragma: no-cache");
	header("Cache-control: private");
	header('Content-type: application/kml');
	header('Content-Disposition: attachment; filename="'.  str_replace(' ', '_', $Name .'-'. date("d_M-H\hi\m")) .'.kml"');
	echo $KML_header.implode("\n", $KML_file).$KML_footer;
}
?>