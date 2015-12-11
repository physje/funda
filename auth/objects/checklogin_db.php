<?php
/**************************************************************/
/*              phpSecurePages version 0.42 beta               */
/*              Copyright 2013 Circlex.com, Inc.              */
/*       Versions .30 and earlier coded by Paul Kruyt         */
/*                http://www.phpSecurePages.com               */
/*                                                            */
/*              Free for non-commercial use only.             */
/*               If you are using commercially,               */
/*         or using to secure your clients' web sites,        */
/*   please purchase a license at http://phpsecurepages.com   */
/*                                                            */
/**************************************************************/
/*      There are no user-configurable items on this page     */
/**************************************************************/

// check login with Database
connect_db();

$sql		= "SELECT * FROM $TableUsers WHERE $UsersUsername like '$login' AND $UsersPassword like '$password'";
$result	= mysql_query($sql);

// check user and password
if (mysql_num_rows($result) != 0) {
	// user exist --> continue
	$row = mysql_fetch_array($result);
	$userLevel	= $row[$UsersLevel];
	$UserID			= $row[$UsersID]; 
	
	$_SESSION['level']		= $userLevel;
	$_SESSION['UserID']		= $UserID;
	
	if($row[$UsersAccount] != '' AND $row[$UsersAccount] != 0) {
		$_SESSION['account']	= $row[$UsersAccount];
	} else {
		$_SESSION['account']	= $UserID;
	}
			
	$sql = "UPDATE $TableUsers SET $UsersLastLogin = '". time() ."' WHERE $UsersID = ". $row[$UsersID];
	mysql_query($sql);	
} else {
	// user not present in database
  $phpSP_message = $strUserNotExist;
  include($cfgProgDir . "interface.php");
  exit;
}

if (( isset($requiredUserLevel) && !empty($requiredUserLevel[0])) || isset($minUserLevel) ) {
	if ( empty($UsersLevel) || ( !is_in_array($userLevel, @$requiredUserLevel) && ( !isset($minUserLevel) || empty($minUserLevel) || $userLevel < $minUserLevel ) ) ) {
		// this user does not have the required user level
		$phpSP_message = $strUserNotAllowed;
		include($cfgProgDir . "interface.php");
    exit;
  }
}