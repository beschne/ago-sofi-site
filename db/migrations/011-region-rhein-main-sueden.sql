-- Erweitert die Region-Auswahlliste um Rhein-Main-Süden (Standorte südlich von Frankfurt,
-- z. B. Dreieich/Rodgau, die weder Taunus noch Odenwald/Bergstraße sind).
-- Rein additiv (bestehende Werte bleiben gültig), kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/011-region-rhein-main-sueden.sql

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
        'Frankfurt',
        'Rhein-Main-Süden'
    );
