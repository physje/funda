<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

echo '<meta http-equiv="refresh" content="0; url='. $_SERVER['PHP_SELF'] .'" />';

$opdrachten = getZoekOpdrachten(1, '', false);

$sql = "SELECT $ResultaatZoekID, $ResultaatID FROM $TableResultaat WHERE $ResultaatZoekID NOT like ". implode(" AND $ResultaatZoekID NOT like ", $opdrachten) ." LIMIT 0,20";
$result = mysql_query($sql);	
if($row = mysql_fetch_array($result)) {
	do {
		$fundaID = $row[$ResultaatID];
		$zoekID = $row[$ResultaatZoekID];

		$sql_check = "SELECT * FROM $TableResultaat WHERE $ResultaatID like $fundaID AND $ResultaatZoekID NOT like $zoekID";
		$result_check	= mysql_query($sql_check);
		if(mysql_num_rows($result_check) > 0) {
			echo $fundaID .' komt vaker voor<br>';
		} else {
			$sql_huizen = "DELETE FROM $TableHuizen WHERE $HuizenID like $fundaID";
			mysql_query($sql_huizen);
			
			$sql_calendar = "DELETE FROM $TableCalendar WHERE $CalendarHuis like $fundaID";
			mysql_query($sql_calendar);
			
			$sql_kenmerken = "DELETE FROM $TableKenmerken WHERE $KenmerkenID like $fundaID";
			mysql_query($sql_kenmerken);
			
			$sql_prijzen = "DELETE FROM $TablePrijzen WHERE $PrijzenID like $fundaID";
			mysql_query($sql_prijzen);
			
			$sql_resultaat = "DELETE FROM $TableResultaat WHERE $ResultaatID like $fundaID";
			mysql_query($sql_resultaat);
			echo $fundaID .' verwijderd<br>';
		}
		$sql_delete = "DELETE FROM $TableResultaat WHERE $ResultaatID like $fundaID AND $ResultaatZoekID like $zoekID";
		mysql_query($sql_delete);
				
	} while($row = mysql_fetch_array($result));
}
