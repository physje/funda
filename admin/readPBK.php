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

$url = "http://statline.cbs.nl/StatWeb/publication/?DM=SLNL&PA=81884NED";

$content	= file_get_contents_retry($url);
$tabel		= getString('<table Class=', '</table></span>', $content, 0);
$rijen		= explode('<Tr>', $tabel[0]);
$rijen		= array_slice ($rijen, 4);
array_pop($rijen);

# Wel of geen mail sturen, that's the question
$newEntry = false;

foreach($rijen as $key => $rij) {
	# Even opslaan voor zometeen
	$oud_perc	= $perc[0];
	
	$tijd	= getString('<Th Class="PubGridStubItem">', '</Th>', $rij, 0);
	$perc	= getString('<Td Class="PubGridCell">', '</Td>', str_replace(',', '.', $rij), 0);
		
	if(strlen($tijd[0]) > 4) {
		$jaar	= getString('', ' ', $tijd[0], 0);
		$maand = getString(' ', '', $tijd[0], 0);		
	
		switch ($maand[0]) {
			case "december";
				$Mnd = 12;
				break;
			case "november";
				$Mnd = 11;
				break;
			case "oktober";
				$Mnd = 10;
				break;
			case "september";
				$Mnd = 9;
				break;
			case "augustus";
				$Mnd = 8;
				break;
			case "juli";
				$Mnd = 7;
				break;
			case "juni";
				$Mnd = 6;
				break;
			case "mei";
				$Mnd = 5;
				break;
			case "april";
				$Mnd = 4;
				break;
			case "maart";
				$Mnd = 3;
				break;
			case "februari";
				$Mnd = 2;
				break;
			case "januari";
				$Mnd = 1;
				break;
		}
		
		$sql_check = "SELECT * FROM $TablePBK WHERE $PBKComment like '". $maand[0] .' '. $jaar[0] ."'";
		$result = mysql_query($sql_check);
		if(mysql_num_rows($result) == 0) {
			$sql = "DELETE FROM $TablePBK WHERE $PBKStart = '". mktime(0,0,0,$Mnd,1,$jaar[0]) ."'";			
			mysql_query($sql);
			
			$sql = "INSERT INTO $TablePBK ($PBKStart, $PBKEind, $PBKWaarde, $PBKComment) VALUES ('". mktime(0,0,0,$Mnd,1,$jaar[0]) ."', '". mktime(23,59,59,($Mnd+1),0,$jaar[0]) ."', '". $perc[0] ."', '". $maand[0] .' '. $jaar[0] ."')";
			mysql_query($sql);
			$newEntry = true;
		}

		$mailMaand	= $maand[0];
		$mailMnd		= $Mnd;
		$mailJaar 	= $jaar[0];
		$percentage	= $perc[0];
		
		echo $mailMaand .' '. $mailJaar .' -> '. $percentage ."<br>\n";
		
	}
}

toLog('info', '', '', 'Kadaster PBK-ingelezen');

# Als de ingelezen data "nieuwer" is dan de data in de dB, is er nieuwe data en moet er een mail worden gestuurd.
if($newEntry) {
	$melding[] = "<a href='$url'>Prijsindex Bestaande Woningen</a> is ingelezen.";
	$melding[] = "";
	$melding[] = "<b>$mailMaand $mailJaar</b> : $percentage, was $oud_perc (". number_format ((100*($percentage-$oud_perc))/$percentage,1) ."%)";	
	
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
