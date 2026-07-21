-- Fügt ein optionales einzeiliges Beschreibungsfeld pro Foto hinzu, das auf der
-- Detailseite angezeigt wird, wenn vorhanden. Additiv, kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/009-foto-beschreibung.sql

ALTER TABLE standort_fotos
    ADD COLUMN beschreibung VARCHAR(255) NULL AFTER sortierung;
