// Farben exakt wie die Status-Tags (siehe .badge-* in css/style.css).
var STATUS_MARKER_FARBEN = {
    "Geeignet": { rand: "#2c6b23", flaeche: "#dcf3d8" },
    "Eingeschränkt geeignet": { rand: "#7a5d05", flaeche: "#fbf0c9" },
    "Vor Ort geprüft": { rand: "#106354", flaeche: "#d3f0e8" }
};

// Initialisiert eine Leaflet-Karte mit OpenTopoMap-Kacheln und Standort-Markern.
// filter: "geprueft" (nur veröffentlichte, geprüfte/empfohlene Standorte) oder
// "alle" (alle gemeldeten Standorte, unabhängig vom Status).
function initStandorteKarte(elementId, filter) {
    fetch("api/standorte.php?filter=" + encodeURIComponent(filter))
        .then(function (response) { return response.json(); })
        .then(function (standorte) {
            var karte = L.map(elementId, { scrollWheelZoom: false });
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

            if (standorte.length === 0) {
                karte.setView([50.3, 8.5], 9);
                return;
            }

            var marker = standorte.map(function (standort) {
                var popup = document.createElement("div");

                var name = document.createElement("strong");
                name.textContent = standort.name;
                popup.appendChild(name);
                popup.appendChild(document.createElement("br"));

                popup.appendChild(document.createTextNode(standort.status));
                popup.appendChild(document.createElement("br"));

                var link = document.createElement("a");
                link.href = "/standort/" + encodeURIComponent(standort.slug);
                link.textContent = "Details";
                popup.appendChild(link);

                var farben = filter === "geprueft" ? STATUS_MARKER_FARBEN[standort.status] : null;
                var punkt = farben
                    ? L.circleMarker([standort.lat, standort.lon], {
                        radius: 9,
                        weight: 3,
                        color: farben.rand,
                        fillColor: farben.flaeche,
                        fillOpacity: 1
                    })
                    : L.marker([standort.lat, standort.lon]);

                return punkt.bindPopup(popup).addTo(karte);
            });

            var gruppe = L.featureGroup(marker);
            karte.fitBounds(gruppe.getBounds().pad(0.15));
        });
}
