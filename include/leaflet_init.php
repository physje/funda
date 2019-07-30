<?php

$Leaflet[] = "		<div id='map'></div>";
$Leaflet[] = "		<script>";
$Leaflet[] = "";
$Leaflet[] = "		// API token goes here";
$Leaflet[] = "		var key = '$leafletAPI';";
$Leaflet[] = "";
$Leaflet[] = "		// Add layers that we need to the map";
$Leaflet[] = "		var streets = L.tileLayer.Unwired({key: key, scheme: \"streets\"});";
$Leaflet[] = "";

# https://stackoverflow.com/questions/9394190/leaflet-map-api-with-google-satellite-layer
$Leaflet[] = "		var googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',{";
$Leaflet[] = "		    maxZoom: 20,";
$Leaflet[] = "		    subdomains:['mt0','mt1','mt2','mt3']";
$Leaflet[] = "		});";
$Leaflet[] = "";
$Leaflet[] = "		googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',{";
$Leaflet[] = "		    maxZoom: 20,";
$Leaflet[] = "		    subdomains:['mt0','mt1','mt2','mt3']";
$Leaflet[] = "		});";
$Leaflet[] = "";
$Leaflet[] = "		googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{";
$Leaflet[] = "		    maxZoom: 20,";
$Leaflet[] = "		    subdomains:['mt0','mt1','mt2','mt3']";
$Leaflet[] = "		});";
$Leaflet[] = "";
$Leaflet[] = "		googleTerrain = L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',{";
$Leaflet[] = "		    maxZoom: 20,";
$Leaflet[] = "		    subdomains:['mt0','mt1','mt2','mt3']";
$Leaflet[] = "		});";
$Leaflet[] = "";
$Leaflet[] = "		var baseMaps = {";
$Leaflet[] = "		    \"OpenStreetMap\": streets,";
$Leaflet[] = "		    \"Google Street\": googleStreets,";
$Leaflet[] = "		    \"Google Hybrid\": googleHybrid,";
$Leaflet[] = "		    \"Google Satellite\": googleSat,";
$Leaflet[] = "		    \"Google Terrain\": googleTerrain";
$Leaflet[] = "		};";		
$Leaflet[] = "";		
$Leaflet[] = "		// Initialize the map";
$Leaflet[] = "		var map = L.map('map', {";
$Leaflet[] = "		        layers: [googleStreets] // Show 'streets' by default";
$Leaflet[] = "		});";
$Leaflet[] = "";
$Leaflet[] = "		// Zorg dat het past";
$Leaflet[] = "		map.fitBounds([[". $maxLat .",". $maxLon ."],[". $minLat .",". $minLon ."]]);";
$Leaflet[] = "";
$Leaflet[] = "		// Add the 'scale' control";
$Leaflet[] = "		L.control.scale().addTo(map);";

$leaflet_init = implode("\n", $Leaflet);