funda
=====

funda webchecker is een script om funda.nl in de gaten te houden.

Om te beginnen moeten de variabelen in include/config.php worden aangepast naar de in jouw geval geldende waarden.

Vervolgens kan via admin/admin.php 'van alles' geregeld worden waarvan het belangrijkste het ingeven van een zoekopdracht is. Vervolgens kan check.php regulier via een cronjob worden aangeroepen zodat funda gecheckt wordt en de eventuele attenderingsmails binnenstromen.

Als het script enige tijd draait zal de database vervuild raken (dat ligt niet aan het script, dat ligt aan het feit dat makelaars huizen soms meerdere keren er opzetten of huizen er afhalen en dan na een paar dagen weer op zetten).
Via admin/admin.php worden verschillende opties getoond waarmee de database opgeschoond kan worden.
Dubbele huizen kunnen automatisch (via "voeg hits automatisch samen") of handmatig (via "voeg hits handmatig samen") worden samengevoegd.
Zodra huizen verkocht worden verschijnen deze huizen niet meer in de zoekopdracht, maar staat op de pagina van het huis wel de verkoopprijs, aanmelddatum, oorspronkelijke vraagprijs etc.. Deze gegevens zijn op te halen via "werk verkochte huizen bij".
Het kan zijn dat door bovenstaande opschoonacties doublures in de prijs-history of kenmerken zijn geïntroduceerd. Met "prijzen opschonen" en "kenmerken opschonen" kunnen deze doublures verholpen worden.
Sommige huizen verdwijnen ook van de ene op de andere dag van funda waardoor je een 404 krijgt als je deze huizen opvraagt. Regelmatig uitvoeren van "zet pagina's offline" vinkt in de database aan welke huizen niet meer te vinden zijn en waar dus geen aandacht aan hoeft te worden gegeven.
De optie "verwijder oude log-items" spreekt voor zich : oude log-entries (debug, info, error) kunnen worden verwijderd.
