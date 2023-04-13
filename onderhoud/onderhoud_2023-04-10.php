<?php
include_once('../include/config.php');

$db = connect_db();

$migrate = array(
	'Midden-Nederland' => 'Oost-Nederland',
	'Gravenhage' => 'Den Haag',
	'Utrecht (stad)' => 'Utrecht',
	'2-onder-1-kap' => '2-onder-1-kap woning'
);

foreach($migrate as $old => $new) {
	$sql = "SELECT * FROM `$TablePBK` WHERE $PBKRegio like '$old'";
	$result = mysqli_query($db, $sql);
	if($row = mysqli_fetch_array($result)) {
		do {
			$oudComment = $row[$PBKComment];
			$woningtype = $new;
			$jaar = date('Y', $row[$PBKStart]);
			$maand = date('m', $row[$PBKStart]);
			$kwartaal = ceil($maand/3);
			
			$newComment	= $jaar.'Q'.$kwartaal.', '. $woningtype;
			
			$sql_update = "UPDATE $TablePBK SET $PBKComment = '$newComment' WHERE $PBKComment like '$oudComment'";
			mysqli_query($db, $sql_update);
			
			echo $oudComment .' ->'. $newComment .'<br>';			
		} while($row = mysqli_fetch_array($result));
	}
}

?>