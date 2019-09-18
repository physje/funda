# funda

## Twee branches
Funda vindt mij niet meer zo lief dus heeft de boel dichtgegooid.
Heb de code tot dantoe in de branch 'scraper' ondergebracht voor wie geintereseerd is. Maar die branch werkt bij mij dus niet meer.... misschien bij jou nog wel, maar ik kan het dus niet meer testen en onderhoud die dus ook niet.

De 'master-branch' heb ik omgebouwd, die werkt nu op basis van de RSS-feeds van funda.nl. Die feeds zijn echter veel minder uitgebreid dus die branch heeft veel minder functionaliteiten.

## Introductie
Funda Alert is een script om funda.nl in de gaten te houden en daar "statistiek" op te doen.

## Configureren
Om te beginnen moeten alle bestanden op een server geplaatst worden die verbinding heeft met internet (duh). Vergeet niet de bestanden uit de map MOVE_THIS_FOLDER naar de juiste plek te verplaatsen op de server. Als alle bestanden op de juiste plek staan moeten de variabelen in /include/config.php en ../general_include/general_config.php worden aangepast naar de in jouw geval geldende waarden. Verder moeten de MySQL-tabellen worden aangemaakt, de SQL-queries hiervoor staan in /onderhoud/tabel_{datum}.sql. Vervolgens moeten een aantal cronjobs worden ingesteld zodat het geheel automatisch kan functioneren.
De tijdstippen kan je zelf varieëren, maar ik heb de volgende jobs draaien :
- */6	* 	* 	* 	* 	wget -q -O /dev/null https://www.example.com/funda/check.php
- 42 	2 	* 	* 	0 	wget -q -O /dev/null https://www.example.com/funda/admin/cleanUp.php
- 11	5	*/3	*	*	wget -q -O /dev/null https://www.example.com/funda/admin/readKadasterPBK.php
- 42 	1 	*/5 	* 	*	wget -q -O /dev/null http://www.example.com/funda/admin/combine_batch.php

## Aan de slag
Standaard bestaat er een admin-account ('Admin'/'admin') waarmee je als administrator kunt inloggen. Vervolgens kan je op de index-pagina inloggen en 'van alles' regelen. Afhankelijk van je rechten kan je verschillende zaken wijzigen op de site.
* Om te beginnen kan je een zoekopdracht ingeven ("Zoekopdrachten" -> Nieuw). Het veld 'Naam' is hier de naam van de opdracht (bv 'Huizen in Amsterdam'), het veld 'URL' de url van de zoekopdracht (bv 'http://www.funda.nl/koop/amsterdam/') en de check-boxen onder 'Pagina controleren om :' op welke uren de opdracht wordt uitgevoerd. Als er geen checkboxen worden aangevinkt is de opdracht in feite inactieve. Door een opdracht inactief te zetten blijft de data wel bewaard maar wordt de opdracht niet uitgevoerd door het script.
* Bij alle zoekopdrachten kan je aangeven of je bij een nieuw huis hier een pushover-bericht van wil krijgen. In de overzichtspagina kan je pushover aan- en uitvinken.
* Vervolgens kan je een lijst aanmaken ("Lijsten" -> Nieuw). Hiermee kan je zelf een selectie maken uit de huizen die het script gevonden heeft. Het veld 'Naam' is hier de naam van de opdracht (bv 'Mooie huizen', 'Klushuizen' of 'Afvallers') en de check-box 'Actief' of de lijst actief is. Ook hier kan je een lijst inactief zetten om te zorgen dat hij niet in lijsten verschijnt maar de data niet verwijderd wordt.
* Het is ook mogelijk een lijst aan te maken van huizen rond een bepaald coordinaat. Dat kan met de optie 'Selecteer huizen obv coordinaten'. Door hier een punt op de kaart aan te klikken en bij 'Maximale afstand' een afstand in kilometers in te voeren maakt het script een lijst aan van alle huizen die in deze cirkel passen (bv 'alle huizen binnen een straal van 1 km rondom station' of 'alle huizen binnen een straal van 1 km rondom voetbalstadion').
* Als je verschillende zoekopdrachten en lijsten hebt, kan je met behulp van de optie 'Maak combinaties van lijsten & opdrachten' combinatie maken (bv huizen die zowel op de lijst 'Mooie huizen' als op de lijst 'Afvallers' voorkomen of huizen die wel op de lijst 'Mooie huizen' staan, maar niet op 'alle huizen binnen een straal van 1 km rondom voetbalstadion'). Het script zal een lijst aanmaken met alle huizen die aan deze combinatie voldoen.
* Data van van een specifiek huis (bv. dat ene huis aan de Prinsengracht) kan worden opgezocht met 'Bekijk details van een huis'. Als je daar begint met typen zal het script automatisch tonen welke huizen bekend zijn met deze tekst in adres, plaats of id.
* De huizen van zoekpdrachten of lijsten kunnen op verschillende manier getoond worden
  * Tijdslijn : hier wordt van alle huizen met een balk getoond hoe lang deze huizen al te koop staan.
  * Prijs-afname : hier wordt van alle huizen met een balk getoond hoeveel zij al in prijs gedaald zijn.
  * Fotoalbum : hier wordt van alle huizen een kleine foto getoond met daaronder adres, huidige prijs, totale prijsdaling en hoe lang ze al te koop staan.
  * Google Maps (wijk) : hiermee wordt een bestand voor Google Maps of Google Earth gegenereerd waarin alle huizen op wijk gegroepeerd zijn.
  * Google Maps (prijs) : hiermee wordt een bestand voor Google Maps of Google Earth gegenereerd waarin alle huizen op prijs-klasse gegroepeerd zijn.
  * POI-Edit XML-file : Dit is een XML-file die gebruikt kan worden om POI-edit (http://www.poiedit.com/) te vertellen welke POI-files er beschikbaar zijn voor TomTom.

## Onderhoud (alleen beschikbaar voor Administrator)
Als het script enige tijd draait zal de database vervuild raken (dat ligt niet aan het script, dat ligt aan het feit dat makelaars huizen soms meerdere keren er opzetten of huizen er afhalen en dan na een paar dagen weer op zetten).
Het script heeft verschillende mogelijkheden waarmee de database opgeschoond kan worden :
* Dubbele huizen kunnen automatisch (via "voeg hits automatisch samen") of handmatig (via "voeg hits handmatig samen") worden samengevoegd. Op basis van straatnaam, huisnummer en woonplaats zoekt het script huizen die waarschijnlijk hetzelfde zijn. Data van deze huizen wordt samengevoegd.
* Het script kan alleen opvragen welke huizen te koop staan. Opvragen welke huizen verkocht zijn is dus niet mogelijk. Als een huis niet meer te koop staat kan het dus zijn dat deze verkocht is of dat deze door de makelaar offline gehaald is. Om dat te achterhalen is enig handwerk noodzakelijk: met "Huizen die al even van de radar zijn" krijg je een overzicht van huizen die verdwenen zijn waarbij je kan aangeven of deze verkocht zijn of dat ze offline zijn.
* Als bij een zoekopdrachten is aangevinkt dat hier een pushover-melding voor verstuurd moet worden, nemen we aan dat dit een belangrijke zoekopdracht, is moet zoveel mogelijk data verzameld worden. Deze data kan het script niet zelf opvragen en vereist dus weer enig handwerk: met "Overzicht van huizen waar de details van ontbreken" krijg je een overzicht van de huizen waarbij deze data ontbreekt. Het script heeft de mogelijkheid om de HTML-code van individuele huis-pagina's te ontleden en deze data eruit te halen. Dit vereist echter wel dat de HTML-code van deze huizen wordt opgeslagen en op de server in de map 'offline/huis' wordt geplaatst.
* Met "Check de offline opgeslagen huis-pagina's" worden de HTML-pagina's uit de map 'offline/huis' ingelezen.
* Het kan zijn dat door bovenstaande opschoonacties doublures in de prijs-history of kenmerken zijn geïntroduceerd. Met "prijzen opschonen", "kenmerken opschonen" en "Check de verschillende databases" kunnen deze doublures verholpen worden.
* De optie "verwijder oude log-items" spreekt voor zich : oude log-entries (debug, info, error) kunnen worden verwijderd.
