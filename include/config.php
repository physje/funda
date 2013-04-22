<?php

$ScriptURL				= "";
$ScriptTitle			= "Funda webchecker";
$ScriptMailAdress = '';
$Version					= '2.3.4';
$SubjectPrefix		= "[funda] ";

$debug						= 0;
$stapPrijs				= 25000;	// Stapjes in prijs in de Google Maps view
$colPhoto					= 3;			// Aantal kolommen met foto in mail
$rowPhoto					= 3;			// Aantal rijen met foto in mail
$aantalCols				= 4;			// Aantal kolommen met foto in fotoalbum

$cfgLogDebugTime	= mktime(date('H'), date('i'), date('s'), date('m'), date('d')-7, date('Y'));
$cfgLogInfoTime		= mktime(date('H'), date('i'), date('s'), date('m')-1, date('d'), date('Y'));
$cfgLogErrorTime	= mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')-1);

$TableHuizen			= "funda_huizen";
$HuizenID 				= "funda_id";
$HuizenURL	 			= "url";
$HuizenAdres 			= "adres";
$HuizenPC_c 			= "PC_cijfers";
$HuizenPC_l				= "PC_letters";
$HuizenPlaats			= "plaats";
$HuizenWijk				= "wijk";
$HuizenThumb			= "thumb";
$HuizenNdeg 			= "N_deg";
$HuizenNdec 			= "N_dec";
$HuizenOdeg 			= "O_deg";
$HuizenOdec				= "O_dec";
$HuizenStart			= "start";
$HuizenEind				= "eind";
$HuizenVerkocht		= "verkocht";
$HuizenOffline		= "offline";

$TableKenmerken 	= "funda_kenmerken"  ;
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

$TableZoeken			= "funda_zoeken";
$ZoekenKey				= "id";
$ZoekenActive 		= "active";
$ZoekenMail				= "mail";
$ZoekenAdres			= "adres";
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
$ListActive 			= "active";
$ListNaam					= "name";

$TableListResult	= "funda_list_resultaat";
$ListResultList		= "list";
$ListResultHuis		= "huis";

?>