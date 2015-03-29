<?php
include_once(__DIR__. '../include/config.php');

include_once('../../general_include/class.phpmailer.php');
include_once('../../general_include/class.html2text.php');

include_once('../include/HTML_TopBottom.php');
connect_db();

if(isset($_POST['opvragen'])) {
	$invoer	= $_POST['invoer'];
	$sql		= "SELECT * FROM $TableUsers WHERE $UsersUsername like '$invoer' OR $UsersAdres like '$invoer'";
	$result = mysql_query($sql);
	
	if(mysql_num_rows($result) == 0) {
		$text[] = "Er is helaas niks gevonden met '$invoer'";
	} else {
		$row	= mysql_fetch_array($result);
		$id		= $row[$UsersID];
		$data	= getMemberDetails($id);
		
		$nieuwPassword = generatePassword(12);
		
		saveUpdateMember($id, $data['naam'], $data['username'], $nieuwPassword, $data['mail'], $data['level'], $data['account']);
		
		$Mail[] = "Beste ". $data['naam'] .",<br>";
		$Mail[] = "<br>";
		$Mail[] = "je hebt een nieuw wachtwoord aangevraagd voor $ScriptTitle $Version.<br>";
		$Mail[] = "Je kan inloggen met :<br>";
		$Mail[] = "<br>";
		$Mail[] = "Loginnaam : ". $data['username'] ."<br>";
		$Mail[] = "Wachtwoord : ". $nieuwPassword ."<br>";
		$Mail[] = "<br>";
		$Mail[] = "Met deze gegevens kan je via <a href='". $ScriptURL ."admin/edit_account.php'>". $ScriptURL ."admin/edit_account.php</a> je eigen wachtwoord instellen<br>";	
		$HTMLMail = implode("\n", $Mail);
		
		$html =& new html2text($HTMLMail);
		$html->set_base_url($ScriptURL);
		$PlainText = $html->get_text();
		
		$mail = new PHPMailer;
		$mail->AddAddress($data['mail'], $data['naam']);
		$mail->From     = $ScriptMailAdress;
		$mail->FromName = $ScriptTitle;
		$mail->Subject	= $SubjectPrefix ."Nieuw wachtwoord voor $ScriptTitle";
		$mail->IsHTML(true);
		$mail->Body			= $HTMLMail;
		$mail->AltBody	= $PlainText;
		
		if(!$mail->Send()) {
			toLog('error', '', '', "Kon geen inloggegevens versturen naar ". $data['naam']);
			$text[] = "Inloggegevens konden helaas niet verstuurd worden";
		} else {
			toLog('info', '', '', "Inloggegevens verstuurd naar ". $data['naam']);
			$text[] = "Inloggegevens zijn verstuurd";
		}		
	}	
} else {
	$text[] = "<form action='$_SERVER[PHP_SELF]' method='post'>\n";
	$text[] = "<table>";
	$text[] = "<tr>";
	$text[] = "	<td>Voer uw loginnaam of email-adres in. Het systeem zal dan een nieuw wachtwoord mailen.</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td><input type='text' name='invoer' size='75'></td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td>&nbsp;</td>";
	$text[] = "</tr>";
	$text[] = "<tr>";
	$text[] = "	<td align='center'><input type='submit' name='opvragen' value='Opvragen'></td>";
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