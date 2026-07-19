-- Erweitert die Region-Auswahlliste um Lahn-Dill-Bergland (z. B. Dünsberg, deutlich
-- außerhalb des Taunus/Wetterau-Suchgebiets, als Wetter-Ausweichstandort).
-- Rein additiv (bestehende Werte bleiben gültig), kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/008-region-lahn-dill.sql

ALTER TABLE standorte
    MODIFY COLUMN region ENUM(
        'Vordertaunus',
        'Hintertaunus',
        'Wetterau-Rand',
        'Odenwald/Bergstraße',
        'Wetterau',
        'Vogelsberg',
        'Rhön',
        'Lahn-Dill-Bergland'
    );
