// Randfarben exakt wie die Status-Tags (siehe .badge-* in css/style.css),
// Füllfarben kräftiger/leuchtender als die blassen Tag-Hintergründe, damit
// die Marker auf der Karte gut erkennbar sind (gleicher Farbton, mehr Sättigung).
var STATUS_MARKER_FARBEN = {
    "Vorschlag": { rand: "#0b6577", flaeche: "#3ddfff" },
    "Zu prüfen": { rand: "#14527e", flaeche: "#3dafff" },
    "In Prüfung vor Ort": { rand: "#106354", flaeche: "#3dffdc" },
    "Geeignet": { rand: "#2c6b23", flaeche: "#55ff3d" },
    "Eingeschränkt geeignet": { rand: "#7a5d05", flaeche: "#ffcf3d" },
    "Ungeeignet": { rand: "#8a3c11", flaeche: "#ff823d" },
    "Nicht mehr verfügbar": { rand: "#7a1f1f", flaeche: "#ff3d3d" }
};

// Baut ein Marker-Icon in Pin-Form (wie das Standard-Leaflet-Icon), aber
// eingefärbt in der Status-Farbe des Standorts.
function standortIcon(status) {
    var farben = STATUS_MARKER_FARBEN[status];
    if (!farben) {
        return new L.Icon.Default();
    }
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="38" viewBox="0 0 24 36">' +
        '<path d="M12 0C5.4 0 0 5.4 0 12c0 8.4 12 24 12 24s12-15.6 12-24c0-6.6-5.4-12-12-12z" ' +
        'fill="' + farben.flaeche + '" stroke="' + farben.rand + '" stroke-width="2"/>' +
        '<circle cx="12" cy="12" r="5" fill="' + farben.rand + '"/>' +
        '</svg>';
    return L.divIcon({
        html: svg,
        className: "standort-marker-icon",
        iconSize: [26, 38],
        iconAnchor: [13, 37],
        popupAnchor: [0, -34]
    });
}

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

                return L.marker([standort.lat, standort.lon], { icon: standortIcon(standort.status) })
                    .bindPopup(popup)
                    .addTo(karte);
            });

            var gruppe = L.featureGroup(marker);
            karte.fitBounds(gruppe.getBounds().pad(0.15));
        });
}
