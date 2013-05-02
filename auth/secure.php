<?php
/**************************************************************/
/*              phpSecurePages version 0.42 beta              */
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

// Create a constant that can be checked inside the files to be included.
// This gives an indication if secure.php has been loaded correctly.
define('LOADED_PROPERLY', true);

// include configuration
require($cfgProgDir. 'config.php');

// include functions and variables
include($cfgProgDir. 'objects/functions.php');

// choose between login or logout
if (isset($logout) && !(isset($_GET['logout']) || isset($_POST['logout']))) {
	// logout
	include($cfgProgDir. 'objects/logout.php');
} else {
	// make post variables global
	if (isset($_POST['entered_login'])) $entered_login = $_POST['entered_login'];
	if (isset($_POST['entered_password'])) $entered_password = $_POST['entered_password'];
        
  // check if login is necessary
  include($cfgProgDir. "objects/checklogin.php");
  include($cfgProgDir. 'objects/checklogin_db.php');
}


?>
