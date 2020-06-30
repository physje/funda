# funda

## Twee branches
Funda vindt mij niet meer zo lief dus heeft de boel dichtgegooid.
Heb de code tot dantoe in de branch 'scraper' ondergebracht voor wie geintereseerd is. Maar die branch werkt bij mij dus niet meer.... misschien bij jou nog wel, maar ik kan het dus niet meer testen en onderhoud die dus ook niet.
Dat Funda mij blokkeert heeft er volgens mij mee te maken dat het script geen JavaScript oid ondersteunt en zij daarom 'ontdekken' dat het een script is en geen echt persoon. Mocht jij een manier hebben om dat te omzeilen : hoor het graag :)

De 'master-branch' heb ik omgebouwd, die werkt nu op basis van de RSS-feeds van funda.nl. Die feeds zijn echter veel minder uitgebreid dus die branch heeft veel minder functionaliteiten.

## Introductie
Funda Alert is een script om funda.nl in de gaten te houden en daar "statistiek" op te doen.

## Concept
Zoals gezegd werkt de master-branch op basis van de RSS-feed. Dat maakt wel dat enige creatieviteit nodig is om aantal zaken op te lossen.
Daarom eerst even het concept: De RSS-feed bevat maximaal 15 huizen, dat is bij de meeste zoekopdrachten veel te weinig om compleet te zijn. Daarom kan check.php op 2 of 3 verschillende manieren checken.
De eerste manier is de reguliere zoekopdracht: als je deze met zo'n tijdsinterval runt dat er sinds de vorige keer niet meer dan 15 nieuwe huizen zijn bijgekomen, kan je alle nieuwe huizen opmerken.
De tweede en derde manier is door op straat- en wijkniveau funda te checken. Van alle huizen die op de 1ste manier gevonden worden, wordt de straat en wijk weggeschreven in een tabel. De 2de en 3de manier vraagt deze straten en wijken op, en checkt bij funda of alle huizen in deze straat/wijk nog actief zijn. Op deze manier kan dus toch van meer dan 15 huizen per zoekopdracht worden bijgehouden of ze nog te koop staan.
Beide manieren van checken gebeuren in check.php. Afhankelijk van het aantal minuten na het hele uur wordt of de 1ste of de 2de/3de manier gekozen.

## Configureren
Om te beginnen moeten alle bestanden op een server geplaatst worden die verbinding heeft met internet (duh). Vergeet niet de bestanden uit de map MOVE_THIS_FOLDER naar de juiste plek te verplaatsen op de server. Als alle bestanden op de juiste plek staan moeten de variabelen in /include/config.php en ../general_include/general_config.php worden aangepast naar de in jouw geval geldende waarden. Verder moeten de MySQL-tabellen worden aangemaakt (of bijgewerkt als je al een keer een fork gemaakt hebt), de SQL-queries hiervoor staan in /onderhoud/tabel_{datum}.sql en /onderhoud/onderhoud_{datum}.php. Afhankelijk van de huidige datum kan je op basis van de bestandsdatum bepalen welke queries voor jou van belang zijn.
Vervolgens moeten een aantal cronjobs worden ingesteld zodat het geheel automatisch kan functioneren.
De tijdstippen kan je zelf varieëren, maar ik heb de volgende jobs draaien :
- */6	* 	* 	* 	* 	wget -q -O /dev/null https://www.example.com/funda/check.php
- 42 	2 	* 	* 	0 	wget -q -O /dev/null https://www.example.com/funda/admin/cleanUp.php
- 33 	* 	* 	* 	0 	wget -q -O /dev/null https://www.example.com/funda/admin/WOZ.php
- 11	5	*/3	*	*	wget -q -O /dev/null https://www.example.com/funda/admin/readKadasterPBK.php
- 42 	1 	*/5 	* 	*	wget -q -O /dev/null http://www.example.com/funda/admin/combine_batch.php

Let op: zoals bij 'Concept' uitgelegd, bepaalt check.php op basis van het aantal minuten na het hele uur of een reguliere opdracht wordt uitgevoerd of dat er een check van straten of wijken gedaan moet worden (regel 25 ev). Als je deze cronjob dus niet of anders overneemt moet je even kijken of het script nog wel juist functioneert.

## Aan de slag
Standaard bestaat er een admin-account ('Admin'/'admin') waarmee je als administrator kunt inloggen. Vervolgens kan je op de index-pagina inloggen en 'van alles' regelen. Afhankelijk van je rechten kan je verschillende zaken wijzigen op de site.
* Om te beginnen kan je een of meer zoekopdrachten ingeven ("Zoekopdrachten" -> Nieuw). Het veld 'Naam' is hier de naam van de opdracht (bv 'Huizen in Amsterdam'), het veld 'URL' de url van de zoekopdracht (bv 'http://www.funda.nl/koop/amsterdam/') en de check-boxen onder 'Pagina controleren om :' op welke uren de opdracht wordt uitgevoerd. Als er geen checkboxen worden aangevinkt is de opdracht in feite inactieve. Door een opdracht inactief te zetten blijft de data wel bewaard maar wordt de opdracht niet uitgevoerd door het script.
* Bij zoekopdrachten kan je aangeven of je bij een nieuw huis hier een pushover-bericht van wil krijgen. Dat kan je op de overzichtspagina voor elke opdracht aan- en uitvinken.
* Je kan ook een lijst aanmaken ("Lijsten" -> Nieuw). Hiermee kan je zelf een selectie maken uit de huizen die het script gevonden heeft. Het veld 'Naam' is hier de naam van de opdracht (bv 'Mooie huizen', 'Klushuizen' of 'Afvallers') en de check-box 'Actief' of de lijst actief is. Ook hier kan je een lijst inactief zetten om te zorgen dat hij niet in het overzicht met lijsten verschijnt maar de data niet verwijderd wordt.
* Het is ook mogelijk een lijst aan te maken van huizen rond een bepaald coordinaat. Dat kan met de optie 'Selecteer huizen obv coordinaten'. Door hier een punt op de kaart aan te klikken en bij 'Maximale afstand' een afstand in kilometers in te voeren maakt het script een lijst aan van alle huizen die in deze cirkel passen (bv 'alle huizen binnen een straal van 1 km rondom station' of 'alle huizen binnen een straal van 1 km rondom voetbalstadion').
* Als je verschillende zoekopdrachten en lijsten hebt, kan je met behulp van de optie 'Maak combinaties van lijsten & opdrachten' combinatie maken (bv huizen die zowel op de lijst 'Mooie huizen' als op de lijst 'Afvallers' voorkomen of huizen die wel op de lijst 'Mooie huizen' staan, maar niet op 'alle huizen binnen een straal van 1 km rondom voetbalstadion'). Het script zal een lijst aanmaken met alle huizen die aan deze combinatie voldoen.
* Data van van een specifiek huis (bv. dat ene huis aan de Prinsengracht) kan worden opgezocht met 'Bekijk details van een huis'. Als je daar begint met typen zal het script automatisch tonen welke huizen bekend zijn met deze tekst in adres, plaats of id. Zodra het juiste huis gevonden is en je klikt op 'Huis bekijken' komen er verschillende opties om gegevens voor dat huis op te halen, te corrigeren of te presenteren.
* De huizen van zoekpdrachten of lijsten kunnen op verschillende manier getoond worden
  * Tijdslijn : hier wordt van alle huizen met een balk getoond hoe lang deze huizen al te koop staan.
  * Prijs-afname : hier wordt van alle huizen met een balk getoond hoeveel zij al in prijs gedaald zijn.
  * Fotoalbum : hier wordt van alle huizen een kleine foto getoond met daaronder adres, huidige prijs, totale prijsdaling en hoe lang ze al te koop staan.
  * Google Maps (wijk) : Het heet Google Maps, maar eigenlijk gebruik ik LeafletJS (locationiq.com / leafletjs.com) om een kaart te tonen waarin alle huizen op wijk gegroepeerd zijn.
  * Google Maps (prijs) : idem, maar dan waarin alle huizen op prijs-klasse gegroepeerd zijn.
  * POI-Edit XML-file : Dit is een XML-file die gebruikt kan worden om POI-edit (http://www.poiedit.com/) te vertellen welke POI-files er beschikbaar zijn voor TomTom.

## Onderhoud (alleen beschikbaar voor Administrator)
Als het script enige tijd draait zal de database vervuild raken (dat ligt niet aan het script, dat ligt aan het feit dat makelaars huizen soms meerdere keren er opzetten of huizen er afhalen en dan na een paar dagen weer op zetten).
Het script heeft verschillende mogelijkheden waarmee de database opgeschoond kan worden :
* Dubbele huizen kunnen automatisch (via "voeg hits automatisch samen") of handmatig (via "voeg hits handmatig samen") worden samengevoegd. Op basis van straatnaam, huisnummer en woonplaats zoekt het script huizen die waarschijnlijk hetzelfde zijn. Data van deze huizen wordt samengevoegd.
* Het script kan alleen opvragen welke huizen te koop staan. Opvragen welke huizen verkocht zijn is dus niet mogelijk. Als een huis niet meer te koop staat kan het dus zijn dat deze verkocht is of dat deze door de makelaar offline gehaald is. Om dat te achterhalen is helaas enig handwerk noodzakelijk: met "Huizen die al even van de radar zijn" krijg je een overzicht van huizen die verdwenen zijn waarbij je kan aangeven of deze verkocht zijn of dat ze offline zijn.
* Als bij een zoekopdrachten is aangevinkt dat hier een pushover-melding voor verstuurd moet worden, nemen we aan dat dit een belangrijke zoekopdracht, is moet zoveel mogelijk data verzameld worden. Deze data kan het script niet zelf opvragen en vereist dus weer enig handwerk: met "Overzicht van huizen waar de details van ontbreken" krijg je een overzicht van de huizen waarbij deze data ontbreekt. Het script heeft de mogelijkheid om de HTML-code van individuele huis-pagina's te ontleden en deze data eruit te halen. Dit vereist echter wel dat de HTML-code van deze huizen wordt opgeslagen en op de server. Dat kan via "Upload files met funda-HTML" waarna je na het uploaden kan doorklikken naar 'inladen'. Als het goed is herkent het script zelf op basis van de HTML-code over welk huis het gaat.
* Het kan zijn dat door bovenstaande opschoonacties doublures in de prijs-history of kenmerken zijn geïntroduceerd. Met "prijzen opschonen", "kenmerken opschonen" en "Check de verschillende databases" kunnen deze doublures verholpen worden.
* De optie "verwijder oude log-items" spreekt voor zich : oude log-entries (debug, info, error) kunnen worden verwijderd.
* Als je regelmatig de PBK (prijsindex bestaande koopwoningen) inleest (via cronjob of mbv "Lees de prijs-index van het Kadaster in") van het Kadaster, kan je met "Bepaal gecorrigeerde prijs op specifieke datum" de prijs van een huis corrigeren voor de datum : wat zou een bepaald huis wat een jaar geleden zoveel kostte, nu gekost hebben.

## Data corrigeren (alleen beschikbaar voor Administrator)
Soms komt het voor, meestal door een bugje/foutje van mijn kant/lay-out wijziging van funda, dat er data ontbreekt in de database die niet meer op te halen is van funda (vaak omdat pagina al offline zijn). Hiervoor zijn ook een aantal scripts geschreven, deze staan echter niet op de index-pagina.
* addPostcode.php
* addWijk.php