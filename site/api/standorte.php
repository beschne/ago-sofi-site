<?php
// Öffentliches JSON für die Leaflet-Karte (site/js/map.js).
// ?filter=geprueft  -> nur veröffentlichte, geprüfte/empfohlene Standorte
// ?filter=alle (oder ohne Parameter) -> alle veröffentlichten Standorte, unabhängig vom Status

require __DIR__ . '/../inc/db.php';

header('Content-Type: application/json; charset=utf-8');

const GEPRUEFT_STATUS = ['Geeignet', 'Eingeschränkt geeignet', 'Vor Ort geprüft'];

$filter = $_GET['filter'] ?? 'alle';

try {
    $pdo = ago_sofi_db();

    if ($filter === 'geprueft') {
        $platzhalter = implode(',', array_fill(0, count(GEPRUEFT_STATUS), '?'));
        $stmt = $pdo->prepare(
            "SELECT slug, standortname, status, breitengrad, laengengrad
             FROM standorte
             WHERE veroeffentlicht = 1 AND status IN ($platzhalter)
             ORDER BY standortname"
        );
        $stmt->execute(GEPRUEFT_STATUS);
    } else {
        $stmt = $pdo->query(
            "SELECT slug, standortname, status, breitengrad, laengengrad
             FROM standorte
             WHERE veroeffentlicht = 1
             ORDER BY standortname"
        );
    }

    $ergebnis = array_map(static function (array $row): array {
        return [
            'name' => $row['standortname'],
            'status' => $row['status'],
            'slug' => $row['slug'],
            'lat' => (float) $row['breitengrad'],
            'lon' => (float) $row['laengengrad'],
        ];
    }, $stmt->fetchAll());

    echo json_encode($ergebnis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Standorte konnten nicht geladen werden.']);
}
