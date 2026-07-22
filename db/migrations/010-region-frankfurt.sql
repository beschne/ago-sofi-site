-- Erweitert die Region-Auswahlliste um Frankfurt (innerstädtische Standorte wie
-- Hochhaus-Aussichtsplattformen, die keiner der bestehenden Umland-Regionen entsprechen).
-- Rein additiv (bestehende Werte bleiben gültig), kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/010-region-frankfurt.sql

ALTER TABLE standorte
    MODIFY COLUMN region ENUM(
        'Vordertaunus',
        'Hintertaunus',
        'Wetterau-Rand',
        'Odenwald/Bergstraße',
        'Wetterau',
        'Vogelsberg',
        'Rhön',
        'Lahn-Dill-Bergland',
        'Frankfurt'
    );
