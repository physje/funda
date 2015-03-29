<?php

$KML_text[] = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
$KML_text[] = '<kml xmlns="http://earth.google.com/kml/2.2">';
$KML_text[] = '<Document>';
$KML_text[] = '  <name>'. $KMLTitle .'</name>';
$KML_text[] = '  <Style id="style1">';
$KML_text[] = '    <IconStyle>';
$KML_text[] = '      <Icon>';
$KML_text[] = '        <href>http://maps.gstatic.com/mapfiles/ms2/micons/red-dot.png</href>';
$KML_text[] = '      </Icon>';
$KML_text[] = '    </IconStyle>';
$KML_text[] = '  </Style>';
$KML_text[] = '';
$KML_header = implode("\n", $KML_text);

$KML_text = array();
$KML_text[] = '</Document>';
$KML_text[] = '</kml>';
$KML_footer = implode("\n", $KML_text);
$KML_text = array();