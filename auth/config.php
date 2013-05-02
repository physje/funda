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
/*           Start of phpSecurePages Configuration            */
/**************************************************************/

/****** Installation ******/
$cfgIndexpage = '/index.php';
  // page to go to, if login is cancelled
  // Example: if your main page is http://www.mydomain.com/index.php
  // the value would be $cfgIndexpage = '/index.php'

/****** Admin Email ******/
$admEmail = '';
  // E-mail address of the site administrator
  // (This is being showed to the users on an error, so you can be notified by the users)
  // May be left blank

/****** Password Encryption ******/
$passwordEncryptedWithMD5 = true;          // Set this to true if the passwords are encrypted with the MD5 algorithm

/**************************************************************/
/*             End of phpSecurePages Configuration            */
/**************************************************************/
?>
