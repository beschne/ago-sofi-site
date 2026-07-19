-- Bringt die Auswahllisten aus Airtable zurück (Region, Zugänglichkeit, Parkplatz,
-- Erwarteter Andrang, Sicherheitsrisiken) und wandelt Horizontbewertung von Text
-- ("⭐⭐⭐ Gut" etc.) in eine Zahl 0–5 um (analog zu gesamtbewertung).
-- Einmalig auf der bereits befüllten Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/003-auswahllisten.sql

-- Text -> Zahl, solange die Spalte noch VARCHAR ist
UPDATE standorte SET horizontbewertung = CASE
    WHEN horizontbewertung LIKE '%Ausgezeichnet%' THEN '5'
    WHEN horizontbewertung LIKE '%Sehr gut%' THEN '4'
    WHEN horizontbewertung LIKE '%Gut%' THEN '3'
    WHEN horizontbewertung LIKE '%Eingeschränkt%' THEN '2'
    WHEN horizontbewertung LIKE '%Ungeeignet%' THEN '1'
    ELSE NULL
END;

ALTER TABLE standorte
    MODIFY COLUMN region ENUM(
        'Vordertaunus',
        'Hintertaunus',
        'Wetterau-Rand',
        'Odenwald/Bergstraße'
    ),
    MODIFY COLUMN zugaenglichkeit ENUM(
        'Jederzeit frei zugänglich',
        'Tagsüber frei zugänglich',
        'Nur zu Fuß erreichbar',
        'Genehmigung erforderlich',
        'Privatgelände',
        'Gesperrt',
        'Unbekannt'
    ),
    MODIFY COLUMN parkplatz ENUM(
        'Direkt am Standort',
        '< 100 m',
        '100 - 500m',
        '> 500m',
        'Kein Parkplatz',
        'Unbekannt'
    ),
    MODIFY COLUMN andrang_erwartet ENUM(
        'Sehr gering',
        'Gering',
        'Mittel',
        'Hoch',
        'Sehr hoch',
        'Unbekannt'
    ),
    MODIFY COLUMN sicherheitsrisiken ENUM(
        'Keine bekannt',
        'Gering',
        'Mittel',
        'Hoch',
        'Nicht bewertet'
    ),
    MODIFY COLUMN horizontbewertung TINYINT UNSIGNED;
