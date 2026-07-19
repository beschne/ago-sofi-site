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

* **index.html** — Startseite: kurze Einführung, Standortkarte (Platzhalter), veröffentlichte Standortliste (Airtable Embed), noch zu prüfende Standortvorschläge (Airtable Embed), Hinweis dass Mitglieder weitere Standorte an den Vorstand melden können. Es gibt **keine** separate `standorte.html` — die Standortliste lebt direkt auf der Startseite.
* **beobachten.html** — Informationen zur sicheren Sonnenbeobachtung.
* **impressum-datenschutz.html** — Impressum und Datenschutzerklärung (im Footer aller Seiten verlinkt). Es gibt aktuell **keine** Galerie-Seite; eine Galerie über ein Airtable-Embed ist für später geplant.

## Airtable

* Es werden ausschließlich öffentliche **Embed-Links** verwendet, **niemals** Share-Links.
* Alle Embed-URLs zentral in `site/js/config.js` pflegen, z. B.:

  ```javascript
  const AIRTABLE = {
      standorte: "...",
      vorschlaege: "..."
  };
  ```
* HTML-Seiten dürfen **keine** fest eingebetteten Airtable-URLs enthalten — immer über `config.js` referenzieren.

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
