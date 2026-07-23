# AG Orion – Sonnenfinsternis-Website

Mini-Website der AG Orion zur Vorbereitung auf die partielle Sonnenfinsternis
am 12. August 2026: Sammeln und Prüfen möglicher Beobachtungsstandorte.
Veröffentlicht unter https://sofi.agorion.de.

![Karte der empfohlenen Beobachtungsstandorte mit nach Eignung eingefärbten Markern](SCREENSHOT.jpg?v=057d7436)
<sub>Stand: 23.07.2026</sub>

## Tech-Stack

HTML5, CSS, Vanilla JavaScript, PHP (Server-Templating, kein Framework) und MySQL/MariaDB.
Standortdaten liegen in einer eigenen Datenbank und werden über `site/verwaltung/` gepflegt
(nicht mehr über Airtable).

## Entwicklung

Alle auszuliefernden Dateien liegen unter `site/`. Für lokale PHP-Entwicklung gegen die
echte Datenbank siehe `DEPLOYMENT.md` (SSH-Tunnel-Ansatz). Details zu Seiten, Datenmodell
und Konventionen: `CLAUDE.md`.

## Deployment

Siehe `DEPLOYMENT.md`.
