-- Datenqualitäts-Check für die Tabelle `standorte`.
-- Ausführen (read-only, kein sudo nötig für die Abfrage selbst):
--   mysql ago_sofi < tools/datenqualitaet/pruefkriterien.sql
-- bzw. auf dem Server per SSH:
--   ssh cerberus@92.205.236.81 'sudo mysql ago_sofi < /tmp/pruefkriterien.sql'
--
-- Kriterien (Stand 22.07.2026, mit Benno abgestimmt):
--   1. Jeder Standort braucht eine Horizontgrafik (standort_fotos, kategorie='horizontgrafik').
--   2. Hartes Kriterium nur für Status "Geeignet": braucht ein Datum in
--      `zuletzt_vor_ort_geprueft`. Bei "Eingeschränkt geeignet" ist ein fehlendes Datum nur
--      eine Warnung (2w.), kein hartes Kriterium.
--   2b. Veröffentlichte (`veroeffentlicht=1`) Standorte mit Status "Geeignet" oder
--       "Eingeschränkt geeignet" müssen sonst alle wesentlichen Felder ausgefüllt haben (keine
--       für Besucher wichtige Lücke online sichtbar); `zuletzt_vor_ort_geprueft` zählt hier nur
--       bei "Geeignet" als Pflichtfeld, siehe Kriterium 2. Die Spalte `fehlende_felder` listet
--       konkret auf, welche Felder leer sind.
--   3. Jeder Standort braucht einen `kartenlink`, und die darin enthaltenen Koordinaten müssen
--      zu `breitengrad`/`laengengrad` passen (Toleranz 0,0005° ≈ 50 m) -- relevant, wenn
--      Koordinaten nachträglich korrigiert wurden, der Link aber nicht mitgezogen wurde.
--   4. `entfernung_bad_homburg_km` und `fahrzeit_minuten` müssen gesetzt sein. Nur Vorhandensein
--      wird geprüft, nicht die Plausibilität der Werte.
--   5. `veroeffentlicht=1`, aber Status nicht in der öffentlich als "geprüft" geltenden Liste
--      (Geeignet, Eingeschränkt geeignet, Vor Ort geprüft) -- vermutlich vergessen, Status oder
--      Veröffentlichung anzupassen.
--   6. Koordinaten-Plausibilität: breitengrad/laengengrad müssen im Suchgebiet liegen
--      (Breite 49-51°, Länge 7,5-9,5°) -- fängt grobe Tippfehler ab (z. B. vertauschte
--      Kommastelle).
--   7. Mögliche Duplikate: zwei Standorte mit Koordinaten < 200 m auseinander. Eigene Abfrage
--      am Ende der Datei (Paar-Ergebnis passt nicht ins einheitliche Zeilenschema oben).

-- 1. Fehlende Horizontgrafik
SELECT
    '1. Fehlt: Horizontgrafik' AS kriterium,
    s.id, s.slug, s.standortname, s.status, s.veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte s
LEFT JOIN standort_fotos f ON f.standort_id = s.id AND f.kategorie = 'horizontgrafik'
WHERE f.id IS NULL

UNION ALL

-- 2. Hart: "Geeignet" ohne Prüfdatum
SELECT
    '2. Fehlt (hart, nur "Geeignet"): zuletzt_vor_ort_geprueft' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE status = 'Geeignet'
  AND zuletzt_vor_ort_geprueft IS NULL

UNION ALL

-- 2w. Warnung: "Eingeschränkt geeignet" ohne Prüfdatum (kein hartes Kriterium)
SELECT
    '2w. Warnung: zuletzt_vor_ort_geprueft fehlt ("Eingeschränkt geeignet")' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE status = 'Eingeschränkt geeignet'
  AND zuletzt_vor_ort_geprueft IS NULL

UNION ALL

-- 2b. Veröffentlicht, aber wesentliche Felder fehlen (zuletzt_vor_ort_geprueft nur bei "Geeignet" Pflicht)
SELECT
    '2b. Veröffentlicht, aber unvollständig' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    CONCAT_WS(', ',
        IF(zugaenglichkeit IS NULL, 'zugaenglichkeit', NULL),
        IF(parkplatz IS NULL, 'parkplatz', NULL),
        IF(andrang_erwartet IS NULL, 'andrang_erwartet', NULL),
        IF(sicherheitsrisiken IS NULL, 'sicherheitsrisiken', NULL),
        IF(kartenlink IS NULL, 'kartenlink', NULL),
        IF(region IS NULL, 'region', NULL),
        IF(entfernung_bad_homburg_km IS NULL, 'entfernung_bad_homburg_km', NULL),
        IF(fahrzeit_minuten IS NULL, 'fahrzeit_minuten', NULL),
        IF(hoehe_meter IS NULL, 'hoehe_meter', NULL),
        IF(horizontbewertung IS NULL, 'horizontbewertung', NULL),
        IF(gesamtbewertung IS NULL, 'gesamtbewertung', NULL),
        IF(kurze_bewertung IS NULL, 'kurze_bewertung', NULL),
        IF(status = 'Geeignet' AND zuletzt_vor_ort_geprueft IS NULL, 'zuletzt_vor_ort_geprueft', NULL)
    ) AS fehlende_felder
FROM standorte
WHERE veroeffentlicht = 1
  AND status IN ('Geeignet', 'Eingeschränkt geeignet')
  AND (
        zugaenglichkeit IS NULL OR parkplatz IS NULL OR andrang_erwartet IS NULL
        OR sicherheitsrisiken IS NULL OR kartenlink IS NULL OR region IS NULL
        OR entfernung_bad_homburg_km IS NULL OR fahrzeit_minuten IS NULL
        OR hoehe_meter IS NULL OR horizontbewertung IS NULL OR gesamtbewertung IS NULL
        OR kurze_bewertung IS NULL
        OR (status = 'Geeignet' AND zuletzt_vor_ort_geprueft IS NULL)
      )

UNION ALL

-- 3a. Kein OSM-Link
SELECT
    '3a. Fehlt: OpenStreetMap-Link' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE kartenlink IS NULL OR kartenlink = ''

UNION ALL

-- 3b. OSM-Link vorhanden, aber Koordinaten im Link weichen von breitengrad/laengengrad ab
SELECT
    '3b. OSM-Link zeigt falsche Koordinaten' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE kartenlink IS NOT NULL AND kartenlink != ''
  AND (
        ABS(CAST(REGEXP_REPLACE(REGEXP_SUBSTR(kartenlink, 'mlat=[0-9.-]+'), 'mlat=', '') AS DECIMAL(10,6)) - breitengrad) > 0.0005
        OR ABS(CAST(REGEXP_REPLACE(REGEXP_SUBSTR(kartenlink, 'mlon=[0-9.-]+'), 'mlon=', '') AS DECIMAL(10,6)) - laengengrad) > 0.0005
      )

UNION ALL

-- 4. Entfernung/Fahrzeit ab Bad Homburg fehlt (Werte selbst werden nicht geprüft)
SELECT
    '4. Fehlt: Entfernung/Fahrzeit ab Bad Homburg' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE entfernung_bad_homburg_km IS NULL OR fahrzeit_minuten IS NULL

UNION ALL

-- 5. Veröffentlicht, aber Status nicht in der öffentlich "geprüften" Liste
SELECT
    '5. Veröffentlicht, aber Status nicht "geprüft"' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE veroeffentlicht = 1
  AND status NOT IN ('Geeignet', 'Eingeschränkt geeignet', 'Vor Ort geprüft')

UNION ALL

-- 6. Koordinaten außerhalb des plausiblen Suchgebiets (grobe Tippfehler)
SELECT
    '6. Koordinaten unplausibel (außerhalb Breite 49-51° / Länge 7,5-9,5°)' AS kriterium,
    id, slug, standortname, status, veroeffentlicht,
    NULL AS fehlende_felder
FROM standorte
WHERE breitengrad NOT BETWEEN 49 AND 51
   OR laengengrad NOT BETWEEN 7.5 AND 9.5

ORDER BY kriterium, id;

-- 7. Mögliche Duplikate: Standort-Paare mit Koordinaten < 200 m auseinander (Haversine-Formel,
-- da MariaDB-Version-unabhängig ohne Spatial-Erweiterungen berechnet). Eigenes Ergebnis, jede
-- Zeile ist ein Paar statt ein einzelner Standort.
SELECT
    a.id AS id_a, a.slug AS slug_a, a.standortname AS standortname_a,
    b.id AS id_b, b.slug AS slug_b, b.standortname AS standortname_b,
    ROUND(
        6371000 * 2 * ASIN(SQRT(
            POWER(SIN(RADIANS(a.breitengrad - b.breitengrad) / 2), 2)
            + COS(RADIANS(a.breitengrad)) * COS(RADIANS(b.breitengrad))
              * POWER(SIN(RADIANS(a.laengengrad - b.laengengrad) / 2), 2)
        ))
    ) AS distanz_meter
FROM standorte a
JOIN standorte b ON a.id < b.id
HAVING distanz_meter < 200
ORDER BY distanz_meter;
