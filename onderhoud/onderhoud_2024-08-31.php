<?php
include_once('../include/config.php');

$db = connect_db();

$sql[] = "ALTER TABLE $TableZoeken ADD $ZoekenLastCheck INT(11) NOT NULL AFTER $ZoekenURL";

foreach($sql as $query) {
	echo $query;
	if(mysqli_query($db, $query)) {
		echo ' -> <b>OK</b><br>';
	} else {
		echo ' -> <b>Error</b><br>';
	}
}

?>