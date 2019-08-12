<?php
include_once(__DIR__.'/../include/config.php');
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();

if(isset($_POST['save_wijk'])) {
	$sql = "UPDATE $TableHuizen SET $HuizenWijk = '". urlencode($_POST['insert_wijk']) ."' WHERE $HuizenPC_c = '". $_POST['insert_c'] ."' AND $HuizenPC_l like '". $_POST['insert_l'] ."'";
	$dummy = mysqli_query($db, $sql);
	sleep(1);
}

$userInteraction = false;

$sql = "SELECT * FROM $TableHuizen WHERE $HuizenWijk like '' AND $HuizenPC_c != 0 AND $HuizenPC_l NOT LIKE '' GROUP BY $HuizenPC_c, $HuizenPC_l LIMIT 0,1";
$result = mysqli_query($db, $sql);
if($row = mysqli_fetch_array($result)) {
	$cijfers = $row[$HuizenPC_c];
	$letters = $row[$HuizenPC_l];
	
  # Wat vind de site postcodebijadres.nl er van
  $WijkPCinfo = scrapePCInfo($cijfers.$letters);
	
  # We vragen eerst eens even op : is een bepaalde postcode-wijk-combinatie al bekend in de database
  $sql_2 = "SELECT * FROM $TableHuizen WHERE $HuizenWijk NOT like '' AND $HuizenPC_c = '$cijfers' AND $HuizenPC_l like '$letters' GROUP BY $HuizenWijk";
  $result_2 = mysqli_query($db, $sql_2);
  $row_2 = mysqli_fetch_array($result_2);

  # Bij deze postcode staat nog niks in de database, dus nemen wij de voorzet van 
  # postcodebijadres.nl over
  if(mysqli_num_rows($result_2) == 0) {
  	$insertWijk = $WijkPCinfo;
  	$insertCijfers = $cijfers;
  	$insertLetters = $letters;


  # Bij deze postcode staat dus al een wijk in de database
  } elseif(mysqli_num_rows($result_2) == 1) {
  	# Even vergelijken met postcodebijadres.nl
  	# Als het gelijk is is het die naam
  	if(urldecode($row_2[$HuizenWijk]) == $WijkPCinfo) {
  		$insertWijk = $WijkPCinfo;
  		$insertCijfers = $cijfers;
  		$insertLetters = $letters;
	
  	# Als het niet gelijk is moet de gebruiker kiezen
  	} else {
  		$keuzeWijk[] = urldecode($row_2[$HuizenWijk]);
  		$keuzeWijk[] = $WijkPCinfo;
  		$userInteraction = true;
  	}
	
	
  # Deze postcode-wijk-combinatie is ambigue
  # De gebruiker moet kiezen
  } elseif(mysqli_num_rows($result_2) > 1) {
  	$keuzeWijk[] = $WijkPCinfo;
  	
  	do {
  		if(urldecode($row_2[$HuizenWijk]) != $WijkPCinfo) {
  			$keuzeWijk[] = urldecode($row_2[$HuizenWijk]);
  		}
  	} while($row_2 = mysqli_fetch_array($result_2));
		
	  $userInteraction = true;
  }

  if($userInteraction) {
  	$deel_1[] = "Bij de postcode $cijfers$letters zijn meerdere wijken gevonden.";
  	$deel_1[] = "postcodebijadres.nl stelt <i>$WijkPCinfo</i> voor.";
  	$deel_1[] = "Maak aan de rechterkant een keuze.";
  		
  	$deel_2[] = "<form method='post' action='". $_SERVER['PHP_SELF'] ."'>";
  	$deel_2[] = "<input type='hidden' name='insert_c' value='$cijfers'>";
  	$deel_2[] = "<input type='hidden' name='insert_l' value='$letters'>";
  	
  	foreach($keuzeWijk as $keuze) {
  		$deel_2[] = "<input type='radio' name='insert_wijk' value='$keuze'". ($keuze == $WijkPCinfo ? ' selected' :'') ."> $keuze<br>";
  	}
	
  	$deel_2[] = "<br>";
  	$deel_2[] = "<input type='submit' name='save_wijk' value='Voeg toe aan $cijfers$letters'>";
  	$deel_2[] = "</form>";
  } else {
  	$deel_1[] = "Bij de postcode $cijfers$letters is de wijk <i>$insertWijk</i> gevonden";

  	$sql_insert = "UPDATE $TableHuizen SET $HuizenWijk = '". urlencode($insertWijk) ."' WHERE $HuizenPC_c = '$cijfers' AND $HuizenPC_l like '$letters'";
  	if(!mysqli_query($db, $sql_insert)) {
  		$deel_2[] = "Wegschrijven ging niet goed";
  	} else {
  		$deel_2[] = "<a href='". $_SERVER['PHP_SELF'] ."'>Volgende zoeken</a>";
  	}
  }
} else {
	$deel_1[] = "Klaar !";
	$userInteraction = true;
}

include_once('../include/HTML_TopBottom.php');
echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock(implode("<br>\n", $deel_1));
if($deel_2 != "") {
	echo "</td><td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $deel_2));
}
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
