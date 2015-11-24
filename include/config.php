<?php

$ScriptURL		= '';			# Map waar het script staat, bv http://www.example.com/scripts/funda/
$ScriptTitle		= 'Funda Alert';	# Naam van het script (is naam van afzender in mails)
$ScriptMailAdress	= '';			# Mailadres van het script (is mailadres van afzender in mails)
$Version		= '4.2';		# Versie nummer
$SubjectPrefix		= '[funda] ';		# Voorvoegsel bij de onderwerpregel bij het versturen van mails

$debug			= 0;			# Wel (1) of geen (0) debug-info op het scherm tonen
$stapPrijs		= 25000;		# Stapjes in prijs in de Google Maps view
$colPhoto		= 3;			# Aantal kolommen met foto in mail
$rowPhoto		= 3;			# Aantal rijen met foto in mail
$aantalCols		= 4;			# Aantal kolommen met foto in fotoalbum
$addSoldHouses		= true;		# Wel (true) of geen (false) verkochte nieuwe huizen toevoegen

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
//$cfgCSVExport[] = 'Energielabel (D)';
//$cfgCSVExport[] = 'Energielabel (V)';
$cfgCSVExport[] = 'Energielabel';
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
$cfgPrefixExport[] = 'Open Huis';
$cfgPrefixExport[] = 'Makelaar';
$cfgPrefixExport[] = 'Wijk';
$cfgPrefixExport[] = 'Latitude';
$cfgPrefixExport[] = 'Longitude';

# Default bestandsnaam voor de reguliere export naar EXCEL
$cfgXLSFilename = '../extern/export_'. date('mdy') .'_'. md5(strftime ('%A %e %B %G')) .'.xls';

# Strings met meldingen voor het inloggen
#$strNoAccess          = "Toegang geweigerd";
$strNoPassword        	= "Geen wachtwoord ingevuld";
$strUserNotAllowed    	= "Deze gebruiker heeft geen toegang tot deze pagina";
$strUserNotExist      	= "Onbekende inloggegevens";

# Tabel- en veldnamen voor de verschillende tabellen in MySQL
$TableAbo		= "funda_abonnement";
$AboZoekID		= "zoek_id";
$AboUserID		= "member_id";
$AboType		= "soort";

$TableHuizen		= "funda_huizen";
$HuizenID 		= "funda_id";
$HuizenURL	 	= "url";
$HuizenAdres 		= "adres";
$HuizenPC_c 		= "PC_cijfers";
$HuizenPC_l		= "PC_letters";
$HuizenPlaats		= "plaats";
$HuizenWijk		= "wijk";
$HuizenThumb		= "thumb";
$HuizenMakelaar		= "makelaar";
$HuizenLat		= "latitude";
$HuizenLon		= "longitude";
$HuizenStart		= "start";
$HuizenEind		= "eind";
$HuizenAfmeld		= "afgemeld";
$HuizenVerkocht		= "verkocht";
$HuizenOffline		= "offline";
$HuizenOpenHuis		= "open_huis";

$TableCalendar		= "funda_kalender";
$CalendarHuis		= "huis";
$CalendarStart		= "start";
$CalendarEnd		= "einde";

$TableKenmerken 	= "funda_kenmerken";
$KenmerkenKey		= "id";
$KenmerkenID		= "funda_id";
$KenmerkenValue		= "omschrijving";
$KenmerkenKenmerk	= "kenmerk";

$TableList		= "funda_lists";
$ListID			= "id";
$ListUser		= "user";
$ListActive 		= "active";
$ListNaam		= "name";

$TableListResult	= "funda_list_resultaat";
$ListResultList		= "list";
$ListResultHuis		= "huis";

$TableLog		= "funda_log";
$LogID			= "id";
$LogTime		= "tijd";
$LogType		= "type";
$LogOpdracht		= "opdracht";
$LogHuis		= "huis";
$LogMessage		= "message";

$TableUsers		= "funda_members";
$UsersID		= "id";
$UsersName		= "name";
$UsersUsername		= "username";
$UsersPassword		= "password";
$UsersLevel		= "level";
$UsersAdres		= "mail";
$UsersPOKey		= "userkey";
$UsersPOToken		= "api_token";
$UsersAccount		= "account";
$UsersLastLogin		= "lastLogin";

$TablePBK		= "funda_PBK";
$PBKStart		= "start";
$PBKEind		= "eind";
$PBKWaarde		= "waarde";
$PBKComment		= "comment";

$TablePrijzen 		= "funda_prijzen";
$PrijzenKey		= "id";
$PrijzenID		= "funda_id";
$PrijzenPrijs		= "prijs";
$PrijzenTijd		= "tijd";

$TableIgnore		= "funda_ignore";
$IgnoreKey		= "id";
$IgnoreID		= "funda_id";

$TableResultaat 	= "funda_resultaat";
$ResultaatZoekID	= "zoek_id";
$ResultaatID		= "funda_id";
$ResultaatPrijs		= "prijs";
$ResultaatVerkocht	= "verkocht";
$ResultaatOpenHuis	= "open_huis";

$TableVerdeling		= "funda_verdeling";
$VerdelingUur		= "uur";
$VerdelingOpdracht	= "opdracht";

$TableZoeken		= "funda_zoeken";
$ZoekenKey		= "id";
$ZoekenUser		= "user";
$ZoekenNaam		= "naam";
$ZoekenURL		= "url";

?>
