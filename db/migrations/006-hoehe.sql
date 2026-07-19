-- Höhe über Meeresspiegel (für die Detailseite, vor der Horizontbewertung).
-- Additiv, kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/006-hoehe.sql

ALTER TABLE standorte
    ADD COLUMN hoehe_meter SMALLINT UNSIGNED NULL AFTER laengengrad,
    ADD COLUMN hoehe_vom_turm TINYINT(1) NOT NULL DEFAULT 0 AFTER hoehe_meter;
