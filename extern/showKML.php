<?
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
connect_db();

$bDag		= getParam('bDag', date("d"));
$bMaand	= getParam('bMaand', date("m"));
$bJaar	= getParam('bJaar', date("Y"));
$eDag		= getParam('eDag', date("d"));
$eMaand = getParam('eMaand', date("m"));
$eJaar	= getParam('eJaar', date("Y"));
$regio	= getParam('regio', 1);

if($_REQUEST['datum'] == 0) {	
	echo "<form method='post' action='$_SERVER[PHP_SELF]'>\n";
	echo "<input type='hidden' name='datum' value='1'>\n";
	echo "<table>\n";
	echo "<tr>\n";
	echo "	<td>Begin Datum</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td>Eind Datum</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td>Regio</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td rowspan='2'><input type='submit' name='submit' value='Weergeven'></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "	<td><select name='bDag'>\n";
	for($d=1 ; $d<=31 ; $d++)	echo "	<option value='$d'". ($d == $bDag ? ' selected' : '') .">$d</option>\n";
	echo "	</select>\n";	
	echo "	<select name='bMaand'>\n";
	for($m=1 ; $m<=12 ; $m++)	echo "	<option value='$m'". ($m == $bMaand ? ' selected' : '') .">$m</option>\n";
	echo "	</select>\n";
	echo "	<select name='bJaar'>\n";
	for($j=2004 ; $j<=date("Y") ; $j++)	echo "	<option value='$j'". ($j == $bJaar ? ' selected' : '') .">$j</option>\n";
	echo "	</select>\n";
	echo "	</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td><select name='eDag'>\n";
	for($d=1 ; $d<=31 ; $d++)	echo "	<option value='$d'". ($d == $eDag ? ' selected' : '') .">$d</option>\n";
	echo "	</select>\n";	
	echo "	<select name='eMaand'>\n";
	for($m=1 ; $m<=12 ; $m++)	echo "	<option value='$m'". ($m == $eMaand ? ' selected' : '') .">$m</option>\n";
	echo "	</select>\n";
	echo "	<select name='eJaar'>\n";
	for($j=2004 ; $j<=date("Y") ; $j++)	echo "	<option value='$j'". ($j == $eJaar ? ' selected' : '') .">$j</option>\n";
echo "	</select>\n";
	echo "	</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "	<td><select name='regio'>\n";
	
	$Opdrachten = getZoekOpdrachten(1);
	foreach($Opdrachten as $OpdrachtID) {
		$OpdrachtData = getOpdrachtData($OpdrachtID);
		echo "	<option value='$OpdrachtID'". ($OpdrachtID == $regio ? ' selected' : '') .">". $OpdrachtData['naam'] ."</option>\n";
	}
	echo "	</select>\n";	
	echo "	</td>\n";
	echo "	<td>&nbsp;</td>\n";
	echo "</tr>\n";
	echo "	<td colspan=7><input type=checkbox name=link value=1>Open direct in GoogleMaps ipv downloaden KML-file</td>\n";
	echo "</tr>\n";
	echo "<table>\n";
	echo "</form>\n";
} elseif($_REQUEST['link'] == '1') {
	//echo "<a href='http://maps.google.nl/maps?q=". urlencode($ScriptURL .'extern/showKML.php?datum=1&regio='. $_POST[regio] .'&bDag='. $_POST[bDag] .'&bMaand='. $_POST[bMaand] .'&bJaar='. $_POST[bJaar] .'&eDag='. $_POST[eDag] .'&eMaand='. $_POST[eMaand] .'&eJaar='. $_POST[eJaar]) ."'>link</a>";
	$redirect = "http://maps.google.nl/maps?q=". urlencode($ScriptURL .'extern/showKML.php?datum=1&regio='. $_POST[regio] .'&bDag='. $_POST[bDag] .'&bMaand='. $_POST[bMaand] .'&bJaar='. $_POST[bJaar] .'&eDag='. $_POST[eDag] .'&eMaand='. $_POST[eMaand] .'&eJaar='. $_POST[eJaar]);
	$url="Location: ". $redirect;
	header($url);
} else {
	$BeginTijd	= mktime(0, 0, 1, $bMaand, $bDag, $bJaar);
	$EindTijd		= mktime(23, 59, 59, $eMaand, $eDag, $eJaar);
	$data				= getOpdrachtData($regio);
	
	$KMLTitle = 'Nieuwe huizen in '. $data['naam'] .' van '. date("d-m-Y", $BeginTijd) .' t/m '. date("d-m-Y", $EindTijd);
	include('../include/KML_TopBottom.php');

	//$sql_wijk		= "SELECT * FROM $TableHuizen WHERE (($HuizenEind BETWEEN $BeginTijd AND $EindTijd) OR ($HuizenStart BETWEEN $BeginTijd AND $EindTijd)) AND $HuizenOpdracht = $regio GROUP BY $HuizenWijk ORDER BY $HuizenPC_c, $HuizenPC_l";
	$sql_wijk		= "SELECT * FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $regio AND (($TableHuizen.$HuizenEind BETWEEN $BeginTijd AND $EindTijd) OR ($TableHuizen.$HuizenStart BETWEEN $BeginTijd AND $EindTijd)) GROUP BY $TableHuizen.$HuizenWijk ORDER BY $TableHuizen.$HuizenPC_c, $TableHuizen.$HuizenPC_l";
	
	$result_wijk= mysql_query($sql_wijk);
	$row_wijk		= mysql_fetch_array($result_wijk);
	
	do {
		$wijk = trim($row_wijk[$HuizenWijk]);
		
		$KML_file[] = '<Folder>';
		$KML_file[] = '<open>0</open>';
		//$KML_file[] = '<visibility>0</visibility>';
		$KML_file[] = '	<name>'. urldecode($wijk) .'</name>';
			
		//$sql_huis			= "SELECT * FROM $TableHuizen WHERE (($HuizenEind BETWEEN $BeginTijd AND $EindTijd) OR ($HuizenStart BETWEEN $BeginTijd AND $EindTijd)) AND $HuizenWijk like '$wijk' AND $HuizenOpdracht = $regio ORDER BY $HuizenPC_c, $HuizenPC_l";
		$sql_huis			= "SELECT * FROM $TableResultaat, $TableHuizen WHERE $TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND $TableResultaat.$ResultaatZoekID = $regio AND $TableHuizen.$HuizenWijk like '$wijk' AND (($TableHuizen.$HuizenEind BETWEEN $BeginTijd AND $EindTijd) OR ($TableHuizen.$HuizenStart BETWEEN $BeginTijd AND $EindTijd)) ORDER BY $TableHuizen.$HuizenPC_c, $TableHuizen.$HuizenPC_l";
		
		//echo $sql_huis ."\n";
		
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
	header('Content-Disposition: attachment; filename="'.  str_replace(' ', '_', $data['naam'] .'-'. date("d_M-H\hi\m")) .'.kml"');
	echo $KML_header.implode("\n", $KML_file).$KML_footer;
}
?>