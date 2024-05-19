<?php
include_once(__DIR__.'/../include/config.php');
$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");
$db = connect_db();
 
// Get search term 
$searchTerm = $_GET['term']; 
 
// Fetch matched data from the database 
$query = $db->query("SELECT * FROM $TableHuizen WHERE $HuizenAdres LIKE '%".$searchTerm."%' ORDER BY $HuizenStraat, $HuizenNummer ASC"); 
 
// Generate array with skills data 
$skillData = array(); 
if($query->num_rows > 0){ 
    while($row = $query->fetch_assoc()){ 
        $data['id'] = $row['funda_id']; 
        $data['value'] = urldecode($row['adres']).', '.urldecode($row['plaats']).' ['. $row['funda_id'] .']';
        array_push($skillData, $data); 
    } 
} 
 
// Return results as json encoded array 
echo json_encode($skillData); 
?>