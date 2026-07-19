<?php
// Validiert und speichert einen Foto-Upload unter site/uploads/.
// Gibt bei Erfolg den gespeicherten Dateinamen zurück, sonst null.

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

function verarbeite_foto_upload(array $datei): ?string {
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

    $endung = UPLOAD_ERLAUBTE_TYPEN[$bildinfo['mime']];
    $dateiname = bin2hex(random_bytes(16)) . '.' . $endung;
    $ziel = ago_sofi_uploads_dir() . '/' . $dateiname;

    if (!move_uploaded_file($datei['tmp_name'], $ziel)) {
        throw new RuntimeException('Datei konnte nicht gespeichert werden.');
    }
    chmod($ziel, 0644);

    return $dateiname;
}
