# CLAUDE.md

Diese Datei gibt Claude Code Kontext für die Arbeit in diesem Repository.

## Projekt

AG Orion – Mini-Website zur partiellen Sonnenfinsternis am **12. August 2026**.
Statische Website, lokal entwickelt und veröffentlicht auf **https://sofi.agorion.de** (nginx).

## Tech-Stack

* Statisches HTML5
* CSS (kein Präprozessor)
* Vanilla JavaScript
* **Keine** Frameworks, **keine** Build-Tools, **keine** Datenbank, **keine** Serverlogik

Standortdaten werden ausschließlich über **eingebettete Airtable-Views (Embed)** angezeigt — keine eigene Datenhaltung im Code.

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
    │   └── config.js
    └── img/
```

Alles, was ausgeliefert werden soll, liegt unterhalb von `site/`. Dateien außerhalb von `site/`
(README, CLAUDE.md, Deployment-Doku etc.) landen nie auf dem Webserver, siehe `DEPLOYMENT.md`.

## Seiten

* **index.html** — Startseite: kurze Einführung, Standortkarte (Platzhalter, nur geprüfte Standorte), Liste der geprüften/empfohlenen Standorte (Status Geeignet, Eingeschränkt geeignet, Vor Ort geprüft; Airtable Embed), Hinweis dass Mitglieder weitere Standorte an den Vorstand melden können sowie Link zu `alle-standorte.html`.
* **alle-standorte.html** — eigene Karte (Platzhalter, alle Standorte) plus Liste aller gemeldeten Standorte inkl. sichtbarem Prüfstatus (Airtable Embed), unabhängig vom Veröffentlichungs-Status.
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
