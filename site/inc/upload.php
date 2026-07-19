<?php
// Validiert und speichert einen Foto-Upload unter site/uploads/.
// Gibt bei Erfolg ein Array mit Dateiname + aus EXIF gelesenen Metadaten zurück
// (nur bei JPEG möglich; PNG/WebP haben kein EXIF), sonst null.

const UPLOAD_MAX_BYTES = 8 * 1024 * 1024; // 8 MB
const UPLOAD_ERLAUBTE_TYPEN = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

function ago_sofi_uploads_dir(): string {
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function verarbeite_foto_upload(array $datei): ?array {
    if ($datei['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($datei['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload-Fehler (Code ' . $datei['error'] . ').');
    }
    if ($datei['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Datei zu groß (max. 8 MB).');
    }

    $bildinfo = getimagesize($datei['tmp_name']);
    if ($bildinfo === false || !isset(UPLOAD_ERLAUBTE_TYPEN[$bildinfo['mime']])) {
        throw new RuntimeException('Nur JPG, PNG oder WebP-Bilder sind erlaubt.');
    }

    $exif = exif_aus_datei($datei['tmp_name'], $bildinfo['mime']);

    $endung = UPLOAD_ERLAUBTE_TYPEN[$bildinfo['mime']];
    $dateiname = bin2hex(random_bytes(16)) . '.' . $endung;
    $ziel = ago_sofi_uploads_dir() . '/' . $dateiname;

    if (!move_uploaded_file($datei['tmp_name'], $ziel)) {
        throw new RuntimeException('Datei konnte nicht gespeichert werden.');
    }
    chmod($ziel, 0644);

    return array_merge(['dateiname' => $dateiname], $exif);
}

// EXIF (Aufnahmezeitpunkt, GPS) auslesen. Nur JPEG unterstützt Exif — bei PNG/WebP
// bleiben die Werte leer und müssen manuell im Formular nachgetragen werden.
function exif_aus_datei(string $pfad, string $mimeType): array {
    $ergebnis = ['aufnahme_zeitpunkt' => null, 'gps_breitengrad' => null, 'gps_laengengrad' => null];

    if ($mimeType !== 'image/jpeg') {
        return $ergebnis;
    }

    $exif = @exif_read_data($pfad);
    if ($exif === false) {
        return $ergebnis;
    }

    if (!empty($exif['DateTimeOriginal'])) {
        $zeit = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
        if ($zeit !== false) {
            $ergebnis['aufnahme_zeitpunkt'] = $zeit->format('Y-m-d H:i:s');
        }
    }

    if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
        $lat = exif_gps_zu_dezimal($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
        $lon = exif_gps_zu_dezimal($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E');
        if ($lat !== null && $lon !== null) {
            $ergebnis['gps_breitengrad'] = $lat;
            $ergebnis['gps_laengengrad'] = $lon;
        }
    }

    return $ergebnis;
}

function exif_gps_zu_dezimal(array $koordinate, string $ref): ?float {
    if (count($koordinate) !== 3) {
        return null;
    }
    [$grad, $minuten, $sekunden] = array_map('exif_rational_zu_float', $koordinate);
    $dezimal = $grad + ($minuten / 60) + ($sekunden / 3600);
    if (in_array(strtoupper($ref), ['S', 'W'], true)) {
        $dezimal *= -1;
    }
    return round($dezimal, 6);
}

function exif_rational_zu_float(string $wert): float {
    if (strpos($wert, '/') !== false) {
        [$zaehler, $nenner] = explode('/', $wert, 2);
        $nenner = (float) $nenner;
        return $nenner != 0.0 ? ((float) $zaehler) / $nenner : 0.0;
    }
    return (float) $wert;
}
