-- Datum der letzten Vor-Ort-Prüfung. Rein intern (wie interne_notiz), nie öffentlich
-- angezeigt, nur in der Verwaltung editierbar. Additiv, kein Datenverlust.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/007-zuletzt-vor-ort-geprueft.sql

ALTER TABLE standorte
    ADD COLUMN zuletzt_vor_ort_geprueft DATE NULL AFTER fahrzeit_minuten;
