-- Optionaler Webseiten-Link pro Standort (z. B. Homepage einer Sternwarte),
-- unabhängig vom Kartenlink. Wird auf der Detailseite nur angezeigt, wenn gesetzt.
-- Additiv, kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/012-webseite.sql

ALTER TABLE standorte
    ADD COLUMN webseite VARCHAR(500) NULL AFTER kartenlink;
