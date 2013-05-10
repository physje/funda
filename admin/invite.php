<?php
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../../general_include/class.phpmailer.php');
include_once('../../general_include/class.html2text.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
$minUserLevel = 2;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
connect_db();

if(isset($_POST['invite'])) {
	$dataOntvanger	= getMemberDetails($_POST['gebruiker']);
	$dataZender			= getMemberDetails($_SESSION['UserID']);
	$OpdrachtID			= substr($_POST['selectie'], 1);	
	$dataOpdracht		= getOpdrachtData($OpdrachtID);
	
	$Mail[] = "Beste ". $dataOntvanger['naam'] .",<br>";
	$Mail[] = "<br>";
	$Mail[] = $dataZender['naam'] ." heeft je uitgenodigd voor de zoekopdracht '<a href='". $dataOpdracht['url'] ."'>". $dataOpdracht['naam'] ."</a>'.<br>";
	$Mail[] = "Klik <a href='". $ScriptURL ."admin/edit_account.php?action=add&opdracht=". $OpdrachtID ."'>hier</a> als je voortaan ook mails voor deze opdracht wilt ontvangen.<br>";
	$Mail[] = "<br>";
	$Mail[] = "Hiervoor is het wel nodig een account voor $ScriptTitle $Version te hebben. Mocht je die niet hebben dan kan je de gegevens aanvragen via <a href='". $ScriptURL ."auth/wachtwoord.php'>deze link</a>.<br>";
	$Mail[] = "<br>";
	$Mail[] = "Met groet<br>";
	$Mail[] = $dataZender['naam']. "<br>";

	$HTMLMail = implode("\n", $Mail);
		
	$html =& new html2text($HTMLMail);
	$html->set_base_url($ScriptURL);
	$PlainText = $html->get_text();
		
	$mail = new PHPMailer;
	$mail->AddAddress($dataOntvanger['mail'], $dataOntvanger['naam']);
	$mail->From     = $ScriptMailAdress;
	$mail->FromName = $ScriptTitle;
	$mail->Subject	= $SubjectPrefix ." uitnodiging voor de opdracht '". $dataOpdracht['naam'] ."'";
	$mail->IsHTML(true);
	$mail->Body			= $HTMLMail;
	$mail->AltBody	= $PlainText;
		
	if(!$mail->Send()) {
		toLog('error', '', '', "Kon geen uitnodiging versturen naar ". $dataOntvanger['naam']);
		$text[] = "Uitnodiging kon helaas niet verstuurd worden";
		
		echo $HTMLMail;
		
	} else {
		toLog('info', '', '', "Uitnodiging verstuurd naar ". $data['naam']);
		$text[] = "Uitnodiging is verstuurd";
	}
} else {
	$selection = makeSelectionSelection(true, false, $_REQUEST['OpdrachtID']);

	$text[] = "<form action='$_SERVER[PHP_SELF]' method='post'>\n";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td colspan='2'>Geef hieronder aan wie je wilt uitnodigen om zich te abbonneren op welke zoekopdracht ?</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Opdracht</td>";
	$text[] = "	<td>". $selection ."</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>Gebruiker</td>";
	$text[] = "	<td>";
	
	$sql		= "SELECT * FROM $TableUsers WHERE $UsersAccount like '". $_SESSION['UserID']."'";
	$result	= mysql_query($sql);
	if(mysql_num_rows($result) > 0) {
		$text[] = "<select name='gebruiker'>";
		do {
			$text[] = "<option value='". $row[$UsersID] ."'>". $row[$UsersName] ."</option>";
		} while($row = mysql_fetch_array($result));
		$text[] = "</select>";
	} else {
		$text[] = "Er zijn geen volgers bij jouw account.";
	}	
	$text[] = "</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td align='center'><input type='submit' name='invite' value='Uitnodigen'></td>";
	$text[] = "</tr>";
	$text[] = "</table>";
	$text[] = "</form>";
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='25%'>&nbsp;</td>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("\n", $text));
echo "</td>\n";
echo "<td width='25%' valign='top' align='center'>\n";
echo "</tr>\n";
echo $HTMLFooter;


?>