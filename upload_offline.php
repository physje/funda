<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__ .'/include/HTML_TopBottom.php');
include_once($cfgGeneralIncludeDirectory.'class.phpPushover.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = 'auth/';
include($cfgProgDir. "secure.php");

if(isset($_POST['opslaan'])) {
	$files = array_filter($_FILES['upload']['name']);
	$total = count($_FILES['upload']['name']);
	
	// Loop through each file
	for( $i=0 ; $i < $total ; $i++ ) {
		//Get the temp file path
		$tmpFilePath = $_FILES['upload']['tmp_name'][$i];
		
		//Make sure we have a file path
		if ($tmpFilePath != ""){
			//Setup our new file path
			$newFilePath = $offlineDir.date('YmdHis').'_'.$_FILES['upload']['name'][$i];
			
			//Upload the file into the temp dir
			if(move_uploaded_file($tmpFilePath, $newFilePath)) {
				$Links[] = $_FILES['upload']['name'][$i] .' is geupload<br>';
			}
    }
  }
  
  //$String[] = "<p>&nbsp;</p>";
  $Rechts[] = "Ga door naar het <a href='check_offline.php'>inladen</a> in de database van deze pagina's<br>\n";
  $Rechts[] = "<a href='". $_SERVER['PHP_SELF']."'>Upload</a> nog meer pagina's<br>\n";
} else {
	$Links[] = "Omdat via de RSS-feed niet alle informatie op te halen is, is er ook de mogelijkheid om HTML-pagina's van funda.nl aan het script te voeden zodat deze de informatie eruit kan halen.";
	$Links[] = "<p>";
	$Links[] = "Er kunnen 2 soorten pagina's die ingeladen kunnen worden :<ul>";
	$Links[] = "<li>Overzichtpagina van een zoekopdracht, dus overzicht van tekoopstaande of verkochte huizen.</li>";
	$Links[] = "<li>Pagina van individueel huis</li>";
	$Links[] = "</ul>";
	$Links[] = "Het script leest vervolgens deze pagina's in en verwerkt deze informatie in de database.</ul>";
	
	//$Rechts[] = "<p>&nbsp;</p>";
	$Rechts[] = "<form method='post' action='". $_SERVER['PHP_SELF']."' enctype='multipart/form-data'>";
	$Rechts[] = "<input name='upload[]' type='file' multiple='multiple'>";
	$Rechts[] = "<input name='opslaan' type='submit' value='Uploaden'>";
}

# Laat de resultaten vam de check netjes op het scherm zien.
$tweeKolom = false;
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("\n", $Links)) ."</td>\n";
echo "<td width='50%' valign='top' align='center'>". showBlock(implode("\n", $Rechts)) ."</td>\n";
echo "</tr>\n";
echo $HTMLFooter;