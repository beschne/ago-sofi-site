-- Ergänzt Autor/Quelle, Lizenz, Aufnahmezeitpunkt und GPS-Koordinaten pro Foto.
-- Einmalig auf der bereits befüllten Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/002-foto-metadaten.sql

ALTER TABLE standort_fotos
    ADD COLUMN autor_quelle VARCHAR(255) AFTER sortierung,
    ADD COLUMN lizenz ENUM(
        'Eigenes Werk (AGO)',
        'CC BY 4.0',
        'CC BY-SA 4.0',
        'CC0 / Public Domain',
        'Freigabe durch Urheber',
        'Sonstige'
    ) AFTER autor_quelle,
    ADD COLUMN aufnahme_zeitpunkt DATETIME AFTER lizenz,
    ADD COLUMN gps_breitengrad DECIMAL(9,6) AFTER aufnahme_zeitpunkt,
    ADD COLUMN gps_laengengrad DECIMAL(9,6) AFTER gps_breitengrad;
