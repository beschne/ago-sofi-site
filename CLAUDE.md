# CLAUDE.md

Diese Datei gibt Claude Code Kontext für die Arbeit in diesem Repository.

## Projekt

AG Orion – Mini-Website zur partiellen Sonnenfinsternis am **12. August 2026**.
Statische Website, lokal entwickelt und veröffentlicht auf **https://sofi.agorion.de** (nginx).

## Tech-Stack

* Statisches HTML5
* CSS (kein Präprozessor)
* Vanilla JavaScript
* **Keine** Build-Tools, **keine** Datenbank, **keine** Serverlogik
* Einzige Ausnahme von "keine Frameworks": **Leaflet** (Kartenbibliothek), per CDN
  eingebunden (`unpkg.com`, mit Subresource-Integrity-Hash), kein Build-Schritt nötig

Standort-Listen werden ausschließlich über **eingebettete Airtable-Views (Embed)** angezeigt
— keine eigene Datenhaltung im Code. Einzige Ausnahme: `site/js/standorte.json`, ein
statischer Snapshot (Name, Status, Koordinaten) für die Kartenmarker — siehe
[Kartendaten](#kartendaten).

## Projektstruktur

```text
/
├── CLAUDE.md
├── README.md
├── DEPLOYMENT.md
└── site/                  ← wird auf den Server deployt (nginx root zeigt hierher)
    ├── index.html
    ├── alle-standorte.html
    ├── beobachten.html
    ├── impressum-datenschutz.html
    ├── css/
    │   └── style.css
    ├── js/
    │   ├── config.js
    │   ├── map.js
    │   └── standorte.json
    └── img/
```

Alles, was ausgeliefert werden soll, liegt unterhalb von `site/`. Dateien außerhalb von `site/`
(README, CLAUDE.md, Deployment-Doku etc.) landen nie auf dem Webserver, siehe `DEPLOYMENT.md`.

## Seiten

* **index.html** — Startseite: kurze Einführung, Leaflet-Karte mit Markern nur für geprüfte
  Standorte (Status Geeignet, Eingeschränkt geeignet, Vor Ort geprüft), Liste derselben
  Standorte (Airtable Embed), Hinweis dass Mitglieder weitere Standorte an den Vorstand
  melden können sowie Link zu `alle-standorte.html`.
* **alle-standorte.html** — eigene Leaflet-Karte mit Markern für alle gemeldeten Standorte,
  plus Liste aller gemeldeten Standorte inkl. sichtbarem Prüfstatus (Airtable Embed),
  unabhängig vom Veröffentlichungs-Status.
* **beobachten.html** — Informationen zur sicheren Sonnenbeobachtung.
* **impressum-datenschutz.html** — Impressum und Datenschutzerklärung (im Footer aller Seiten verlinkt). Es gibt aktuell **keine** Galerie-Seite; eine Galerie über ein Airtable-Embed ist für später geplant.

## Airtable

* Es werden ausschließlich öffentliche **Embed-Links** verwendet, **niemals** Share-Links.
* Alle Embed-URLs zentral in `site/js/config.js` pflegen, z. B.:

  ```javascript
  const AIRTABLE = {
      geprueft: "...",  // Status: Geeignet, Eingeschränkt geeignet, Vor Ort geprüft
      alle: "..."       // alle gemeldeten Standorte inkl. Status-Spalte
  };
  ```
* HTML-Seiten dürfen **keine** fest eingebetteten Airtable-URLs enthalten — immer über `config.js` referenzieren.

### Woher die Embed-Links kommen

Klassisches Freigeben einzelner Grid-Views ist in der aktuellen Airtable-Oberfläche nicht
mehr zugänglich (der Basis-weite "Teilen"-Button teilt nur die gesamte Base). Die
Embed-Links stammen stattdessen aus einer **Airtable-Interface** in der Base
`app1rAWD8E6gh0Y9j` ("AGO SoFi-Standorte 2026"):

* Interface **„Website-Einbettung"** (`pbdwrCwjWYvqUgC4w`)
  * Seite **„Geprüfte Standorte"** — Tabelle `Standorte` (`tblLIAnUxou1SroQB`), gefiltert auf
    `Veroeffentlichung` = angehakt **und** `Status` ist eine von Geeignet /
    Eingeschränkt geeignet / Vor Ort geprüft.
  * Seite **„Alle Standorte"** — dieselbe Tabelle, ungefiltert (zeigt alle Status-Werte,
    unabhängig von `Veroeffentlichung`), mit sichtbarer Status-Spalte.

Öffentliches Teilen einzelner Interface-Seiten erfordert einen **kostenpflichtigen
Airtable-Plan** (auf dem Free-Plan bleibt der Button „Interface teilen" inaktiv).

Neue Embed-Links holen: In Airtable die jeweilige Interface-Seite öffnen → **„Interface
teilen"** → Tab **„Über Web teilen"** → Seite im Dropdown auswählen → Toggle aktivieren →
**„Diese Seite einbetten"** → `src`-URL aus dem `<iframe>`-Code in `config.js` eintragen.

## Kartendaten

Die Kartenmarker (Leaflet + OpenTopoMap-Kacheln, wegen Geländeschummerung) beziehen ihre
Koordinaten aus `site/js/standorte.json` — einem **statischen Snapshot** (Name, Status,
Koordinaten, `veroeffentlicht`-Flag) aus der Tabelle `Standorte` in Base `app1rAWD8E6gh0Y9j`.

Bewusst **kein** Live-API-Aufruf im Browser: Ein Airtable-Zugriffsschlüssel im
öffentlichen Client-JS wäre auslesbar und würde Zugriff auf Felder erlauben, die auf der
Website gar nicht gezeigt werden (z. B. "Interne Notiz").

`site/js/map.js` liest die JSON-Datei und filtert clientseitig:
`initStandorteKarte("karte", ["Geeignet", "Eingeschränkt geeignet", "Vor Ort geprüft"])`
für die geprüften Standorte (index.html), `initStandorteKarte("karte", null)` für alle
Standorte (alle-standorte.html) — die Filterlogik muss mit der der Airtable-Interface-Seiten
übereinstimmen (siehe oben).

**Snapshot aktualisieren:** Wenn sich Standorte ändern, `standorte.json` über die
Airtable-Verbindung neu erzeugen (Felder: Standortname, Kurzbeschreibung, Status,
Breitengrad, Laengengrad, Veroeffentlichung) und deployen — analog zu den Embed-Links.

## Gestaltung

* Schlicht, schnell, responsiv (Desktop und Smartphone)
* Erscheinungsbild möglichst nah an **agorion.de**
* Wenige Farben, gute Lesbarkeit, Fokus auf Inhalt

## Konventionen für die Entwicklung

* Sauberes, semantisches HTML5
* CSS übersichtlich strukturiert halten
* JavaScript auf das Notwendigste beschränken (kein unnötiges Tooling, keine Abhängigkeiten)
* Lokal im Browser testen, dabei Desktop- und Smartphone-Ansicht prüfen
* Ziel: eine wartungsarme, schnelle Website, deren Inhalte über Airtable-Embeds gepflegt werden können, ohne den Quellcode ändern zu müssen
