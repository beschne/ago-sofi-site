-- Status "Vor Ort geprüft" umbenennen in "In Prüfung vor Ort". Der Zeitpunkt der Besichtigung
-- steht bereits im Feld zuletzt_vor_ort_geprueft (Fernglas-Symbol); der Status-Wert soll
-- stattdessen anzeigen, dass gerade ein Mitglied vor Ort unterwegs ist/war und die
-- abschließende Eignungsbewertung noch aussteht -- wichtig, damit andere AGO-Mitglieder nicht
-- denselben Standort parallel besichtigen. Dreistufig, weil MySQL beim Umbenennen eines
-- ENUM-Werts die bestehenden Zeilen gegen die NEUE Werteliste prüft (Data-truncated-Fehler bei
-- einer einstufigen Umbenennung) -- daher erst den neuen Wert ergänzen, Zeilen umziehen, dann
-- den alten Wert entfernen.
-- Einmalig auf der Produktions-DB ausführen:
--   mysql ago_sofi < db/migrations/013-status-in-pruefung-vor-ort.sql

ALTER TABLE standorte
    MODIFY COLUMN status ENUM(
        'Vorschlag',
        'Zu prüfen',
        'Vor Ort geprüft',
        'In Prüfung vor Ort',
        'Geeignet',
        'Eingeschränkt geeignet',
        'Ungeeignet',
        'Nicht mehr verfügbar'
    ) NOT NULL DEFAULT 'Vorschlag';

UPDATE standorte SET status = 'In Prüfung vor Ort' WHERE status = 'Vor Ort geprüft';

ALTER TABLE standorte
    MODIFY COLUMN status ENUM(
        'Vorschlag',
        'Zu prüfen',
        'In Prüfung vor Ort',
        'Geeignet',
        'Eingeschränkt geeignet',
        'Ungeeignet',
        'Nicht mehr verfügbar'
    ) NOT NULL DEFAULT 'Vorschlag';
