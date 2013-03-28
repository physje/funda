<?
include_once('../../general_include/general_functions.php');
include_once('../../general_include/general_config.php');
include_once('../include/functions.php');
include_once('../include/config.php');
include_once('../include/HTML_TopBottom.php');
connect_db();

$sql_clean = "DELETE FROM $TableLog WHERE ($LogTime < $cfgLogDebugTime AND $LogType like 'debug') OR ($LogTime < $cfgLogInfoTime AND $LogType like 'info') OR ($LogTime < $cfgLogErrorTime AND $LogType like 'error')";
//$pagina = $sql_clean;
mysql_query($sql_clean);
$pagina .= "Log-database opgeschoond.<br>\n";

$sql_optimize = "OPTIMIZE TABLE $TableLog";
mysql_query($sql_optimize);
$pagina .= "Log-database geoptimaliseerd.<br>\n";

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($pagina);
//echo "</td><td width='50%' valign='top' align='center'>\n";
//echo showBlock($deel_3);
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
?>