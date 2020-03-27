<?php
include_once(__DIR__.'/../include/config.php');
include_once(__DIR__.'/../include/HTML_TopBottom.php');

$db = connect_db();

if(isset($_REQUEST['offline'])) {
    $sql = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID = ". $_REQUEST['id'];
    mysqli_query($db, $sql);
}

if(isset($_REQUEST['i'])) {
    $i = $_REQUEST['i'];
} else {
    $i = 0;
}

$halfJaar = mktime(0,0,0,(date("n")-8), date("j"), date("Y"));

$sql = "SELECT * FROM $TableHuizen WHERE $HuizenVerkocht = '1' AND $HuizenOffline = '0' AND $HuizenEind < ". $halfJaar ." ORDER BY $HuizenEind ASC LIMIT $i,1";
$result = mysqli_query($db, $sql);	

if($row = mysqli_fetch_array($result)) {
    $fundaID = $row[$HuizenID];
    
    echo $HTMLHeader;
    
    echo "<body onload=\"window.open('http://funda.nl/$fundaID', 'funda');\">\n";
    //echo "<h1>Dit moet ik zien</h1>\n";
    echo $fundaID ."<br>\n";
    echo urldecode($row[$HuizenAdres]) ."<br>\n";
    echo "Laatst gezien :". date('d-m-Y', $row[$HuizenEind]) ."<br>\n";
    echo "<a href='?id=$fundaID&i=$i&offline'>Offline</a> | <a href='?id=$fundaID&i=". ($i+1). "&online'>Online</a>\n";
    echo "</body>\n";
    
    echo $HTMLFooter;
}
