<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

# Kijken welke huizen uit de kalender-database gisteren open huis gehad hebben.
# Deze huizen moeten klaar gemaakt worden zodat er weer een trigger komt mochten zij open huis hebben 
$start	= mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
$end		= mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));

$sql		= "SELECT * FROM $TableCalendar	WHERE $CalendarEnd BETWEEN $start AND $end";
$result	= mysql_query($sql);
$row		= mysql_fetch_array($result);
do {
	removeOpenHuis($row[$CalendarHuis]);	
} while($row = mysql_fetch_array($result));


$header[] = "BEGIN:VCALENDAR";
$header[] = "PRODID:-//Google Inc//Google Calendar 70.9054//EN";
$header[] = "VERSION:2.0";
$header[] = "CALSCALE:GREGORIAN";
$header[] = "METHOD:PUBLISH";
$header[] = "X-WR-CALNAME:". $ScriptTitle;
$header[] = "X-WR-TIMEZONE:Europe/Amsterdam";
$header[] = "X-WR-CALDESC:Huizen met open huis";
$header[] = "BEGIN:VTIMEZONE";
$header[] = "TZID:Europe/Amsterdam";
$header[] = "X-LIC-LOCATION:Europe/Amsterdam";
$header[] = "BEGIN:DAYLIGHT";
$header[] = "TZOFFSETFROM:+0100";
$header[] = "TZOFFSETTO:+0200";
$header[] = "TZNAME:CEST";
$header[] = "DTSTART:19700329T020000";
$header[] = "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU";
$header[] = "END:DAYLIGHT";
$header[] = "BEGIN:STANDARD";
$header[] = "TZOFFSETFROM:+0200";
$header[] = "TZOFFSETTO:+0100";
$header[] = "TZNAME:CET";
$header[] = "DTSTART:19701025T030000";
$header[] = "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU";
$header[] = "END:STANDARD";
$header[] = "END:VTIMEZONE";

$footer[] = "END:VCALENDAR";

$maandGeleden	= mktime(0, 0, 0, date('m')-1, date('d'), date('Y'));
$Users = getUsers();

foreach($Users as $user) {
	$UserData	= getMemberDetails($user);
	
	$sql		= "SELECT * FROM $TableCalendar, $TableResultaat, $TableZoeken WHERE $TableCalendar.$CalendarHuis = $TableResultaat.$ResultaatID AND $TableResultaat.$ResultaatZoekID = $TableZoeken.$ZoekenKey AND $TableZoeken.$ZoekenUser = $user AND $TableCalendar.$CalendarStart > $maandGeleden";
	$result = mysql_query($sql);
	$row		= mysql_fetch_array($result);
	
	do {
		$start		= $row[$CalendarStart];
		$einde		= $row[$CalendarEnd];
		$fundaID	= $row[$CalendarHuis];
		$data		= getFundaData($fundaID);
	
		$description	= array();
		$description[] = 'X-ALT-DESC;FMTTYPE=text/html:<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">';
		$description[] = '<HTML>';
		$description[] = '<BODY>';
		$description[] = '<a href="http://www.funda.nl/'. $fundaID .'">'. $data['adres'] .'</a>';
		$description[] = '</BODY>';
		$description[] = '</HTML>';
	
		$ics[] = "BEGIN:VEVENT";	
		$ics[] = "UID:FUNDA_OPEN_HUIS-". $fundaID .'-'. date("Ymd", $start);
		$ics[] = "DTSTART;VALUE=DATE:". date("Ymd\THis\Z", $start);
		$ics[] = "DTEND;VALUE=DATE:". date("Ymd\THis\Z", $einde);	
		$ics[] = "LAST-MODIFIED:". date("Ymd\THis\Z", time());
		$ics[] = "SUMMARY:Open Huis '". $data['adres'] ."'";
		$ics[] = "LOCATION:". $data['adres'] .", ". $data['plaats'];
		$ics[] = "DESCRIPTION: ". implode('\n', $description);	
		$ics[] = "STATUS:CONFIRMED";	
		$ics[] = "TRANSP:TRANSPARENT";
		$ics[] = "END:VEVENT";
	} while($row = mysql_fetch_array($result));
	
	$filename = '../extern/'. str_replace(' ', '-', $ScriptTitle) .'_Open-Huis_'. $UserData['naam'] .'.ics';
	
	$file = fopen($filename, 'w+');
	fwrite($file, implode("\r\n", $header));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $ics));
	fwrite($file, "\r\n");
	fwrite($file, implode("\r\n", $footer));
	fclose ($file);
	
	echo "<a href='$filename'>". $UserData['naam'] ."</a>";
	echo "\n<p>\n";
}

?>