<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

# Omdat deze via een cronjob door de server wordt gedraaid is deze niet beveiligd
# Iedereen kan deze pagina dus in principe openen.

$url = "https://app.kpilibrary.com/a/governanceobserver/perf-kpi-modal.asp?id=6049725&tc=wr%2FjQMkX18DQka%2FMt1cM82KsdarjlBs9SHeykeXeBco%3D&db=171667&print=1";

$content = file_get_contents_retry($url);
$tabel	= getString('align="left">Note</th></tr>', '</table></td></tr></table><br />', $content, 0);
$rijen = explode('"><td class="d-tbl-', $tabel[0]);
$rijen = array_slice ($rijen, 1);

# Omdat ik het verloop wil weten moet ik de laatste en een-na-laatste weten.
# Om dat bij te houden definieer ik even 2 variabelen.
$first = true;
$second = false;

# Wel of geen mail sturen, that's the question
$newEntry = false;

foreach($rijen as $rij) {
	$tijd	= getString('" align="left">', '</td>', $rij, 0);
	$perc	= getString('" align="right">', '</td>', $rij, 0);
	
	$maand = getString('', ', ', $tijd[0], 0);
	$jaar	= getString(', ', '', $tijd[0], 0);
	
	switch ($maand[0]) {
		case "December";
			$Mnd = 12;
			break;
		case "November";
			$Mnd = 11;
			break;
		case "October";
			$Mnd = 10;
			break;
		case "September";
			$Mnd = 9;
			break;
		case "August";
			$Mnd = 8;
			break;
		case "July";
			$Mnd = 7;
			break;
		case "June";
			$Mnd = 6;
			break;
		case "May";
			$Mnd = 5;
			break;
		case "April";
			$Mnd = 4;
			break;
		case "March";
			$Mnd = 3;
			break;
		case "February";
			$Mnd = 2;
			break;
		case "January";
			$Mnd = 1;
			break;
	}
	
	$sql_check = "SELECT * FROM $TablePBK WHERE $PBKComment like '". $maand[0] .' '. $jaar[0] ."'";
	$result = mysql_query($sql_check);
	if(mysql_num_rows($result) == 0) {
		$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKWaarde, $PBKComment) VALUES ('". mktime(0,0,0,$Mnd,1,$jaar[0]) ."', '". mktime(23,59,59,($Mnd+1),0,$jaar[0]) ."', '". $perc[0] ."', '". $maand[0] .' '. $jaar[0] ."')";
		mysql_query($sql);
		$newEntry = true;
	}
	
	if($second) {
		$second = false;
		$oud_perc	= $perc[0];
	}
	
	if($first) {
		$first = false;
		$second = true;
		$mailMaand	= $maand[0];
		$mailMnd		= $Mnd;
		$mailJaar 	= $jaar[0];
		$percentage	= $perc[0];
	}
}

toLog('info', '', '', 'Kadaster PBK-ingelezen');

# Als de ingelezen data "nieuwer" is dan de data in de dB, is er nieuwe data en moet er een mail worden gestuurd.
if($newEntry) {
	$melding[] = "<a href='$url'>Prijsindex Bestaande Woningen</a> is ingelezen.";
	$melding[] = "";
	$melding[] = "<b>$mailMaand $mailJaar</b> : $percentage, was $oud_perc (". number_format ((100*($percentage-$oud_perc))/$percentage,1) ."%)";	
	$melding[] = "";
	$melding[] = "<img src='https://app.kpilibrary.com/a/graph/graph.asp?ki=6049725&chk=iWpX%2BqRZUD2CIGQykk2MrVuxLIh7oWNOXM%2BF3ykTHVo%3Did=6049725&tc=wr%2FjQMkX18DQka%2FMt1cM82KsdarjlBs9SHeykeXeBco%3D&db=171667&print=1&pr0=1&mode=print'>";
	
	# Stuur even een mail met de nieuwe cijfers
	include('../include/HTML_TopBottom.php');
	$HTMLMail = $HTMLHeader;
	$HTMLMail .= "<tr>\n";
	$HTMLMail .= "	<td width='25%'>&nbsp;</td>\n";
	$HTMLMail .= "	<td valign='top' align='center' colspan=2>". showBlock(implode("<br>\n", $melding)) ."</td>\n";
	$HTMLMail .= "	<td width='25%'>&nbsp;</td>\n";
	$HTMLMail .= "</tr>\n";
	$HTMLMail .= $HTMLFooter;
		
	$mail = new PHPMailer;
	$mail->From     = $ScriptMailAdress;
	$mail->FromName = $ScriptTitle;
	$mail->AddAddress($ScriptMailAdress, 'Matthijs');
	$mail->Subject	= $SubjectPrefix."PBK van $oud_perc naar $percentage";
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
	$mail->Send();
}
?>
