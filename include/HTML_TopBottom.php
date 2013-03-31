<?php

$HTMLHeader	 = "<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->\n\n";
$HTMLHeader	.= "<html>\n";
$HTMLHeader	.= "<head>\n";
$HTMLHeader	.= "	<link rel='stylesheet' type='text/css' href='". $ScriptURL ."extern/style_mail.css'>\n";
$HTMLHeader	.= "	<title>$ScriptTitle $Version</title>\n";

if($JavaScript) {
	$HTMLHeader .= "	<script>\n";
	$HTMLHeader .= "	function disableField(myField) {\n";
	$HTMLHeader .= "		myField.disabled = true\n";
	$HTMLHeader .= "		return true\n";
	$HTMLHeader .= "	}\n";
	$HTMLHeader .= "	\n";
	$HTMLHeader .= "	function enableField(myField) {\n";
	$HTMLHeader .= "		myField.disabled = false\n";
	$HTMLHeader .= "		return true\n";
	$HTMLHeader .= "	}\n";
	$HTMLHeader .= "	\n";
	//$HTMLHeader .= "	function onfocusField(myField) {\n";
	//$HTMLHeader .= "		if (myField.disabled) {\n";
	//$HTMLHeader .= "			myField.blur()\n";
	//$HTMLHeader .= "			return false\n";
	//$HTMLHeader .= "		}\n";
	//$HTMLHeader .= "		return true;\n";
	//$HTMLHeader .= "	}\n";
	//$HTMLHeader .= "	\n";
	$HTMLHeader .= "	// The above functions are generic, the following function is specific to this page\n";
	$HTMLHeader .= "	function toggleFields() {\n";
	$HTMLHeader .= "		if (document.forms['editform'].actief.checked) {\n";
	$HTMLHeader .= "			enableField(document.forms['editform'].naam)\n";
	$HTMLHeader .= "			enableField(document.forms['editform'].url)\n";
	$HTMLHeader .= "			enableField(document.forms['editform'].mail)\n";
	//$HTMLHeader .= "			if (document.forms['editform'].mail.options[].value != '1') {\n";
	$HTMLHeader .= "				enableField(document.forms['editform'].adres)\n";
	//$HTMLHeader .= "			}\n"; 
	$HTMLHeader .= "		} else {\n";
	$HTMLHeader .= "			disableField(document.forms['editform'].naam)\n";
	$HTMLHeader .= "			disableField(document.forms['editform'].url)\n";
	$HTMLHeader .= "			disableField(document.forms['editform'].mail)\n";
	$HTMLHeader .= "			disableField(document.forms['editform'].adres)\n";
	$HTMLHeader .= "		}\n";
	$HTMLHeader .= "	}\n";
	$HTMLHeader .= "	</script>\n";
}

$HTMLHeader	.= "</head>\n";
$HTMLHeader	.= "<body>\n";
$HTMLHeader	.= "<center>\n";
$HTMLHeader	.= "<table width='100%' align='center' border=0>\n";

$HTMLPreFooter = "<tr>\n";
$HTMLPreFooter .= "	<td colspan='2' align='center'>". showBlock($FooterText) ."</td>\n";
$HTMLPreFooter .= "</tr>\n";

$HTMLFooter = "</table>\n";			
$HTMLFooter .= "</body>\n";
$HTMLFooter .= "</html>\n";
$HTMLFooter .= "\n\n<!--     Deze pagina is onderdeel van $ScriptTitle $Version gemaakt door Matthijs Draijer     -->";
		

?>