-- Erweitert die Region-Auswahlliste um Wetterau, Vogelsberg, Rhön.
-- Rein additiv (bestehende Werte bleiben gültig), kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/004-region-erweitern.sql

ALTER TABLE standorte
    MODIFY COLUMN region ENUM(
        'Vordertaunus',
        'Hintertaunus',
        'Wetterau-Rand',
        'Odenwald/Bergstraße',
        'Wetterau',
        'Vogelsberg',
        'Rhön'
    );
