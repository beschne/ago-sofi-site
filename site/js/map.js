// Initialisiert eine Leaflet-Karte mit OpenTopoMap-Kacheln und Standort-Markern.
// statuses === null zeigt alle Standorte, sonst nur die übergebenen Status-Werte
// (und nur veröffentlichte Standorte).
function initStandorteKarte(elementId, statuses) {
    fetch("js/standorte.json")
        .then(function (response) { return response.json(); })
        .then(function (standorte) {
            var gefiltert = statuses
                ? standorte.filter(function (s) {
                    return s.veroeffentlicht && statuses.indexOf(s.status) !== -1;
                })
                : standorte;

            var karte = L.map(elementId);
            var kacheln = L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
                maxZoom: 17,
                attribution: "Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: © OpenTopoMap (CC-BY-SA)"
            }).addTo(karte);

            // OpenTopoMap ist ein ehrenamtlich betriebener Dienst; einzelne Kacheln
            // schlagen gelegentlich fehl (Timeout/Überlastung). Leaflet versucht
            // das standardmäßig nicht erneut, daher: bis zu 3x mit Verzögerung
            // automatisch neu laden, bevor die Kachel leer bleibt.
            kacheln.on("tileerror", function (fehler) {
                var tile = fehler.tile;
                var versuche = (tile.dataset.ladeversuche | 0) + 1;
                if (versuche > 3) {
                    return;
                }
                tile.dataset.ladeversuche = versuche;
                var url = tile.src.split("?")[0];
                setTimeout(function () {
                    tile.src = url + "?retry=" + versuche + "-" + Date.now();
                }, 1000 * versuche);
            });

            if (gefiltert.length === 0) {
                karte.setView([50.3, 8.5], 9);
                return;
            }

            var marker = gefiltert.map(function (standort) {
                return L.marker([standort.lat, standort.lon])
                    .bindPopup("<strong>" + standort.name + "</strong><br>" + standort.status)
                    .addTo(karte);
            });

            var gruppe = L.featureGroup(marker);
            karte.fitBounds(gruppe.getBounds().pad(0.15));
        });
}
