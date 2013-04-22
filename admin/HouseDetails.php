<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

if(isset($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	$links['getVerkochteHuizen.php?id='. $id] = 'Haal verkoop-gegevens op';
	$links['edit.php?id='. $id]								= 'Wijzig gegevens';
	$links['delete.php?id='. $id]							= 'Verwijder';
	$links['cleanPrice.php?id='. $id]					= 'Prijs opschonen';
	
	if(isset($_REQUEST['selectie'])) {
		$selectie = $_REQUEST['selectie'];
		$links['../TimeLine.php?selectie='. $selectie .'#'. $id]		= 'Timeline';
		$links['../PrijsDaling.php?selectie='. $selectie .'#'. $id]	= 'Prijsdaling';		
	} else {
		$links['../TimeLine.php']			= 'Timeline';
		$links['../PrijsDaling.php']	= 'Prijsdaling';
	}

	foreach($links as $url => $titel) {
		$deel_1 .= "<a href='$url'>$titel</a><br>\n";
	}
	
	$data = getFundaData($id);
	$deel_2 = $data['adres'];
	
	echo $HTMLHeader;
	echo "<tr>\n";
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock($deel_1);
	echo "</td><td width='50%' valign='top' align='center'>\n";
	echo showBlock($deel_2);
	echo "</td>\n";
	echo "</tr>\n";
	echo $HTMLFooter;
}

?>