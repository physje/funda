<?php

# Header
$HTMLHeader	 = "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->\n\n";
$HTMLHeader	.= "<html>\n";
$HTMLHeader	.= "<head>\n";
$HTMLHeader	.= "	<title>$ScriptTitle $Version</title>\n";
$HTMLHeader	.= "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."extern/style.css'>\n";

if(isset($userInteraction) AND !$userInteraction) {
	$HTMLHeader	.= "	<meta http-equiv='refresh' content='2; url=".(isset($forwardURL) ? $forwardURL : '') ."' />\n";
}

if(isset($autoCompleteNew)) {
	$HTMLHeader .= "	<!-- jQuery library -->\n";
	$HTMLHeader .= "	<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>\n";
	$HTMLHeader .= "	<!-- jQuery UI library -->\n";
	$HTMLHeader .= "	<link rel='stylesheet' href='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/themes/smoothness/jquery-ui.css'>\n";
	$HTMLHeader .= "	<script src='https://ajax.googleapis.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js'></script>\n";
	$HTMLHeader .= "	<script>\n";
	$HTMLHeader .= "		$(function() {\n";
	$HTMLHeader .= "		    $(\"#adres_input\").autocomplete({\n";
	$HTMLHeader .= "		    	minLength: 3,\n";
	$HTMLHeader .= "		    	source: \"autocomple_adressen.php\",\n";
	$HTMLHeader .= "		    });\n";
	$HTMLHeader .= "		});\n";
	$HTMLHeader .= "		</script>\n";
}

$HTMLHeader	.= "</head>\n";
$HTMLHeader	.= "<body>\n";
$HTMLHeader	.= "<center>\n";
$HTMLHeader	.= "<table width='100%' align='center' border=0>\n";


# PreFooter (alleen gebruikt in mail volgens mij)
$HTMLPreFooter = "<tr>\n";
$HTMLPreFooter .= "	<td colspan='2' align='center'>". (isset($FooterText) ? showBlock($FooterText) : '&nbsp;' ) ."</td>\n";
$HTMLPreFooter .= "</tr>\n";



# Footer
$HTMLFooter = "</table>\n";			
$HTMLFooter .= "</body>\n";
$HTMLFooter .= "</html>\n";
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";