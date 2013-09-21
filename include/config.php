<?php

$ScriptURL				= '';							# Map waar het script staat, bv http://www.example.com/scripts/funda/
$ScriptTitle			= 'Funda Alert';	# Naam van het script (is naam van afzender in mails)
$ScriptMailAdress = '';							# Mailadres van het script (is mailadres van afzender in mails)
$Version					= '3.9';					# Versie nummer
$SubjectPrefix		= '[funda] ';			# Voorvoegsel bij de onderwerpregel bij het versturen van mails

$debug						= 0;							# Wel (1) of geen (0) debug-info op het scherm tonen
$stapPrijs				= 25000;					# Stapjes in prijs in de Google Maps view
$colPhoto					= 3;							# Aantal kolommen met foto in mail
$rowPhoto					= 3;							# Aantal rijen met foto in mail
$aantalCols				= 4;							# Aantal kolommen met foto in fotoalbum

$cfgLogDebugTime	= mktime(date('H'), date('i'), date('s'), date('m'), date('d')-7, date('Y'));
$cfgLogInfoTime		= mktime(date('H'), date('i'), date('s'), date('m')-1, date('d'), date('Y'));
$cfgLogErrorTime	= mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')-1);

$cfgUserLevels = array(
	1 => 'Volger',
	2 => 'Gebruiker',
	3 => 'Administrator'	
);

# Deze zaken zijn standaard aangevinkt bij een export naar EXCEL
$cfgCSVExport[] = 'Inhoud';
$cfgCSVExport[] = 'Aantal kamers';
$cfgCSVExport[] = 'Tuin';
$cfgCSVExport[] = 'Achtertuin';
$cfgCSVExport[] = 'Ligging tuin';
$cfgCSVExport[] = 'Wonen (= woonoppervlakte)';
$cfgCSVExport[] = 'Badkamervoorzieningen';
$cfgCSVExport[] = 'Bouwjaar';
$cfgCSVExport[] = 'Plaats';
$cfgCSVExport[] = 'Voortuin';

# Zaken die wel belangrijk zijn om te weten, anders dan de kenmerken
$cfgPrefixExport[] = 'ID';
$cfgPrefixExport[] = 'url';
$cfgPrefixExport[] = 'Kadaster';
$cfgPrefixExport[] = 'Huidige Prijs';
$cfgPrefixExport[] = 'Orginele Prijs';
$cfgPrefixExport[] = 'Status';
$cfgPrefixExport[] = 'Makelaar';
$cfgPrefixExport[] = 'Wijk';
$cfgPrefixExport[] = 'Latitude';
$cfgPrefixExport[] = 'Longitude';

# Default bestandsnaam voor de reguliere export naar EXCEL
$cfgXLSFilename = '../extern/export_'. date('mdy') .'_'. md5(strftime ('%A %e %B %G')) .'.xls';

# Strings met meldingen voor het inloggen
#$strNoAccess          = "Toegang geweigerd";
$strNoPassword        = "Geen wachtwoord ingevuld";
$strUserNotAllowed    = "Deze gebruiker heeft geen toegang tot deze pagina";
$strUserNotExist      = "Onbekende inloggegevens";

# Tabel- en veldnamen voor de verschillende tabellen in MySQL
$TableHuizen			= "funda_huizen";
$HuizenID 				= "funda_id";
$HuizenURL	 			= "url";
$HuizenAdres 			= "adres";
$HuizenPC_c 			= "PC_cijfers";
$HuizenPC_l				= "PC_letters";
$HuizenPlaats			= "plaats";
$HuizenWijk				= "wijk";
$HuizenThumb			= "thumb";
$HuizenMakelaar		= "makelaar";				# Op 1 juli in gebruik genomen -> changeTable_01-07-2013.php
$HuizenNdeg 			= "N_deg";					# Op 24 april buiten gebruik gesteld -> changeTable_24-04-2013.php
$HuizenNdec 			= "N_dec";					# Op 24 april buiten gebruik gesteld
$HuizenOdeg 			= "O_deg";					# Op 24 april buiten gebruik gesteld
$HuizenOdec				= "O_dec";					# Op 24 april buiten gebruik gesteld
$HuizenLat				= "latitude";				# Op 24 april in gebruik genomen
$HuizenLon				= "longitude";			# Op 24 april in gebruik genomen
$HuizenStart			= "start";
$HuizenEind				= "eind";
$HuizenVerkocht		= "verkocht";
$HuizenOffline		= "offline";
$HuizenOpenHuis		= "open_huis";			# Op 8 augustus in gebruik genomen -> changeTable_08-08-2013.php

$TableKenmerken 	= "funda_kenmerken";
$KenmerkenKey			= "id";
$KenmerkenID			= "funda_id";
$KenmerkenKenmerk	= "kenmerk";
$KenmerkenValue		= "omschrijving";

$TablePrijzen 		= "funda_prijzen";
$PrijzenKey				= "id";
$PrijzenID				= "funda_id";
$PrijzenPrijs			= "prijs";
$PrijzenTijd			= "tijd";

$TableResultaat 	= "funda_resultaat";
$ResultaatZoekID	= "zoek_id";
$ResultaatID			= "funda_id";
$ResultaatPrijs		= "prijs";
$ResultaatVerkocht= "verkocht";					# Op 27 april in gebruik genomen -> changeTable_27-04-2013.php
$ResultaatOpenHuis= "open_huis";				# Op 8 augustus in gebruik genomen -> changeTable_08-08-2013.php

$TableZoeken			= "funda_zoeken";
$ZoekenKey				= "id";
$ZoekenUser				= "user";							# Op 1 mei in gebruik genomen -> changeTable_01-05-2013.php
$ZoekenActive 		= "active";
$ZoekenMail				= "mail";							# Op 5 mei buiten gebruik gesteld
$ZoekenAdres			= "adres";						# Op 5 mei buiten gebruik gesteld
$ZoekenNaam				= "naam";
$ZoekenURL				= "url";

$TableLog					= "funda_log";
$LogID						= "id";
$LogTime					= "tijd";
$LogType					= "type";
$LogOpdracht			= "opdracht";
$LogHuis					= "huis";
$LogMessage				= "message";

$TableList				= "funda_lists";
$ListID						= "id";
$ListUser					= "user";							# Op 1 mei in gebruik genomen -> changeTable_01-05-2013.php
$ListActive 			= "active";
$ListNaam					= "name";

$TableListResult	= "funda_list_resultaat";
$ListResultList		= "list";
$ListResultHuis		= "huis";

$TableUsers				= "funda_members";		# Op 1 mei in gebruik genomen -> changeTable_01-05-2013.php
$UsersID					= "id";								# Op 1 mei in gebruik genomen
$UsersName				= "name";							# Op 1 mei in gebruik genomen
$UsersUsername		= "username";					# Op 1 mei in gebruik genomen
$UsersPassword		= "password";					# Op 1 mei in gebruik genomen
$UsersLevel				= "level";						# Op 1 mei in gebruik genomen
$UsersAdres				= "mail";							# Op 1 mei in gebruik genomen
$UsersAccount			= "account";					# Op 1 mei in gebruik genomen
$UsersLastLogin		= "lastLogin";				# Op 1 mei in gebruik genomen

$TableAbo					= "funda_abonnement";	# Op 5 mei in gebruik genomen -> changeTable_05-05-2013.php
$AboZoekID				= "zoek_id";					# Op 5 mei in gebruik genomen
$AboUserID				= "member_id";				# Op 5 mei in gebruik genomen

$TableCalendar		= "funda_kalender";
$CalendarHuis			= "huis";
$CalendarStart		= "start";
$CalendarEnd			= "einde";

// http://photos-f.ak.fbcdn.net/photos-ak-snc7/v43/218/226836780678110/app_2_226836780678110_6214.gif

?>