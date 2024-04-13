<?php
include_once(__DIR__.'/../include/config.php');

$db = connect_db();

toLog('error', '0', '0', 'De PBK-data van CBS is gewijzigd. Run het andere CBS-script');

echo "Deze pagina werkt niet meer. De PBK-data van CBS is gewijzigd (normalisatiejaar is aangepast).<br>Run eerst <a href='../onderhoud/onderhoud_2024-04-13.php'>dit script</a> en ga daarna naar <a href='readCBS2020PBK.php'>het nieuwe script voor CBS-data</a>.";

?>
