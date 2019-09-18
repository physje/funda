<?php

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
echo "<td width='50%' valign='top' align='center'>";

echo "<form method='post' action='". $_SERVER['PHP_SELF']."'>\n";
echo "<input type='hidden' name='interface' value='true'>\n";
echo "<table align='center' border=0>\n";
echo "<tr>\n";
echo "	<td colspan='4'>&nbsp;</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td colspan='4' align='center'><h1>Inlogscherm</h1></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td colspan='4' align='center' class='error'>$phpSP_message</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td rowspan='3'>&nbsp;</td>\n";
echo "	<td width='25'>Loginnaam</td>\n";
echo "  <td width='25'><input type='text' name='entered_login' tabindex='1'></td>\n";
echo "	<td rowspan='3'>&nbsp;</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td>Wachtwoord</td>\n";
echo "  <td><input type='password' name='entered_password' tabindex='1'></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td>&nbsp;</td>\n";
echo "	<td class='small'><a href='". $cfgProgDir ."wachtwoord.php'>wachtwoord vergeten</a></td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td colspan='4' align='center'>&nbsp;</td>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "	<td colspan='4' valign='bottom' align='center'><input type='submit' tabindex='1' name='inloggen' value='Inloggen'></td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";

echo "</td>\n";
echo "<td width='25%' valign='top' align='center'>&nbsp;</td>\n";
echo "</tr>\n";
echo $HTMLFooter;

toLog('info', '', '', 'Inlogpoging vanaf '. $_SERVER['REMOTE_ADDR']);