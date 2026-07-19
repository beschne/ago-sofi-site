-- Fügt ein rein internes Notizfeld hinzu (nie öffentlich angezeigt, nur in der Verwaltung
-- editierbar). Additiv, kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/005-interne-notiz.sql

ALTER TABLE standorte
    ADD COLUMN interne_notiz TEXT NULL AFTER kurze_bewertung;
