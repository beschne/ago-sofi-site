-- Schema für die AGO-SoFi-Standortdatenbank.
-- Wird einmalig auf dem Server angelegt.
-- Ausführen als: mysql ago_sofi < schema.sql
-- Für Änderungen an einer bereits befüllten DB siehe db/migrations/.

CREATE TABLE IF NOT EXISTS standorte (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    standortname VARCHAR(255) NOT NULL,
    kurzbeschreibung TEXT,
    status ENUM(
        'Vorschlag',
        'Zu prüfen',
        'Vor Ort geprüft',
        'Geeignet',
        'Eingeschränkt geeignet',
        'Ungeeignet',
        'Nicht mehr verfügbar'
    ) NOT NULL DEFAULT 'Vorschlag',
    veroeffentlicht TINYINT(1) NOT NULL DEFAULT 0,
    breitengrad DECIMAL(9,6) NOT NULL,
    laengengrad DECIMAL(9,6) NOT NULL,
    zugaenglichkeit VARCHAR(100),
    parkplatz VARCHAR(100),
    andrang_erwartet VARCHAR(100),
    sicherheitsrisiken VARCHAR(100),
    kartenlink VARCHAR(500),
    region VARCHAR(100),
    entfernung_bad_homburg_km DECIMAL(5,1),
    fahrzeit_minuten SMALLINT UNSIGNED,
    horizontbewertung VARCHAR(100),
    gesamtbewertung TINYINT UNSIGNED,
    kurze_bewertung TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_veroeffentlicht (status, veroeffentlicht)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS standort_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    standort_id INT UNSIGNED NOT NULL,
    kategorie ENUM('horizontfoto', 'panorama', 'horizontgrafik', 'weiteres') NOT NULL,
    dateiname VARCHAR(255) NOT NULL,
    sortierung SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    autor_quelle VARCHAR(255),
    lizenz ENUM(
        'Eigenes Werk (AGO)',
        'CC BY 4.0',
        'CC BY-SA 4.0',
        'CC0 / Public Domain',
        'Freigabe durch Urheber',
        'Sonstige'
    ),
    aufnahme_zeitpunkt DATETIME,
    gps_breitengrad DECIMAL(9,6),
    gps_laengengrad DECIMAL(9,6),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (standort_id) REFERENCES standorte(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
