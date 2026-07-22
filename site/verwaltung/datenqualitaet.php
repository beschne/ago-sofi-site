<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/upload.php';
csrf_token(); // Session-Cookie muss gesetzt sein, bevor unten HTML-Ausgabe beginnt

$pdo = ago_sofi_db();
$standorte = $pdo->query('SELECT * FROM standorte ORDER BY id')->fetchAll();

function dq_distanz_meter(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $r = 6371000.0;
    $lat1 = deg2rad($lat1); $lat2 = deg2rad($lat2);
    $dLat = $lat2 - $lat1;
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;
    return $r * 2 * asin(sqrt($a));
}

// 1. Fehlende Horizontgrafik
$mitHorizontgrafik = array_flip($pdo->query(
    "SELECT DISTINCT standort_id FROM standort_fotos WHERE kategorie = 'horizontgrafik'"
)->fetchAll(PDO::FETCH_COLUMN));
$kriterium1 = array_filter($standorte, fn($s) => !isset($mitHorizontgrafik[$s['id']]));

// 2. Hart: "Geeignet" ohne Prüfdatum
$kriterium2 = array_filter($standorte, fn($s) =>
    $s['status'] === 'Geeignet' && $s['zuletzt_vor_ort_geprueft'] === null);

// 2w. Warnung: "Eingeschränkt geeignet" ohne Prüfdatum (kein hartes Kriterium)
$kriterium2w = array_filter($standorte, fn($s) =>
    $s['status'] === 'Eingeschränkt geeignet' && $s['zuletzt_vor_ort_geprueft'] === null);

// 2b. Veröffentlicht, aber wesentliche Felder fehlen (zuletzt_vor_ort_geprueft nur bei "Geeignet" Pflicht)
$pflichtfelder = [
    'zugaenglichkeit', 'parkplatz', 'andrang_erwartet', 'sicherheitsrisiken', 'kartenlink',
    'region', 'entfernung_bad_homburg_km', 'fahrzeit_minuten', 'hoehe_meter',
    'horizontbewertung', 'gesamtbewertung', 'kurze_bewertung',
];
$kriterium2b = [];
foreach ($standorte as $s) {
    if ((int) $s['veroeffentlicht'] !== 1 || !in_array($s['status'], ['Geeignet', 'Eingeschränkt geeignet'], true)) {
        continue;
    }
    $fehlend = array_filter($pflichtfelder, fn($feld) => $s[$feld] === null);
    if ($s['status'] === 'Geeignet' && $s['zuletzt_vor_ort_geprueft'] === null) {
        $fehlend[] = 'zuletzt_vor_ort_geprueft';
    }
    if ($fehlend) {
        $kriterium2b[] = ['standort' => $s, 'fehlend' => $fehlend];
    }
}

// 3a. Kein OSM-Link
$kriterium3a = array_filter($standorte, fn($s) => $s['kartenlink'] === null || $s['kartenlink'] === '');

// 3b. OSM-Link vorhanden, aber Koordinaten im Link weichen von breitengrad/laengengrad ab
$kriterium3b = [];
foreach ($standorte as $s) {
    if ($s['kartenlink'] === null || $s['kartenlink'] === '') {
        continue;
    }
    if (!preg_match('/mlat=(-?[0-9.]+)/', $s['kartenlink'], $mlat) || !preg_match('/mlon=(-?[0-9.]+)/', $s['kartenlink'], $mlon)) {
        continue;
    }
    $abweichungLat = abs((float) $mlat[1] - (float) $s['breitengrad']);
    $abweichungLon = abs((float) $mlon[1] - (float) $s['laengengrad']);
    if ($abweichungLat > 0.0005 || $abweichungLon > 0.0005) {
        $kriterium3b[] = $s;
    }
}

// 4. Entfernung/Fahrzeit ab Bad Homburg fehlt (Werte selbst werden nicht geprüft)
$kriterium4 = array_filter($standorte, fn($s) =>
    $s['entfernung_bad_homburg_km'] === null || $s['fahrzeit_minuten'] === null);

// 5. Koordinaten außerhalb des plausiblen Suchgebiets (grobe Tippfehler)
$kriterium5 = array_filter($standorte, fn($s) =>
    (float) $s['breitengrad'] < 49 || (float) $s['breitengrad'] > 51
    || (float) $s['laengengrad'] < 7.5 || (float) $s['laengengrad'] > 9.5);

// 6. Mögliche Duplikate: Standort-Paare mit Koordinaten < 200 m auseinander
$kriterium6 = [];
$anzahl = count($standorte);
for ($i = 0; $i < $anzahl; $i++) {
    for ($j = $i + 1; $j < $anzahl; $j++) {
        $a = $standorte[$i];
        $b = $standorte[$j];
        $distanz = dq_distanz_meter((float) $a['breitengrad'], (float) $a['laengengrad'], (float) $b['breitengrad'], (float) $b['laengengrad']);
        if ($distanz < 200) {
            $kriterium6[] = ['a' => $a, 'b' => $b, 'distanz' => round($distanz)];
        }
    }
}

// 7. Fotos ohne Autor/Quelle oder ohne Lizenz
$fotosUnvollstaendig = $pdo->query(
    "SELECT standort_id, dateiname, autor_quelle, lizenz FROM standort_fotos
     WHERE autor_quelle IS NULL OR autor_quelle = '' OR lizenz IS NULL"
)->fetchAll();
$kriterium7 = [];
foreach ($fotosUnvollstaendig as $foto) {
    $standort = null;
    foreach ($standorte as $s) {
        if ((int) $s['id'] === (int) $foto['standort_id']) {
            $standort = $s;
            break;
        }
    }
    if ($standort === null) {
        continue;
    }
    $fehlend = [];
    if ($foto['autor_quelle'] === null || $foto['autor_quelle'] === '') {
        $fehlend[] = 'Autor/Quelle';
    }
    if ($foto['lizenz'] === null) {
        $fehlend[] = 'Lizenz';
    }
    $kriterium7[] = ['standort' => $standort, 'dateiname' => $foto['dateiname'], 'fehlend' => $fehlend];
}

// 8. Datei-Leichen: Dateien in uploads/, die in keiner standort_fotos-Zeile referenziert werden
$referenzierteDateien = array_flip($pdo->query('SELECT dateiname FROM standort_fotos')->fetchAll(PDO::FETCH_COLUMN));
$kriterium8 = [];
foreach (scandir(ago_sofi_uploads_dir()) ?: [] as $datei) {
    if ($datei === '.' || $datei === '..' || !is_file(ago_sofi_uploads_dir() . '/' . $datei)) {
        continue;
    }
    if (!isset($referenzierteDateien[$datei])) {
        $kriterium8[] = $datei;
    }
}

function dq_zeile(array $s, ?string $zusatz = null): string {
    $html = '<tr><td>' . htmlspecialchars($s['standortname']) . '</td>';
    $html .= '<td>' . htmlspecialchars($s['status']) . '</td>';
    $html .= '<td>' . ((int) $s['veroeffentlicht'] === 1 ? 'Ja' : 'Nein') . '</td>';
    $html .= '<td>' . ($zusatz !== null ? htmlspecialchars($zusatz) : '') . '</td>';
    $html .= '<td><a href="edit.php?id=' . (int) $s['id'] . '">Bearbeiten</a></td></tr>';
    return $html;
}

function dq_tabelle(array $zeilenHtml): string {
    if (!$zeilenHtml) {
        return '<p class="erfolg">Keine Treffer.</p>';
    }
    return '<table class="admin-tabelle"><thead><tr><th>Standort</th><th>Status</th><th>Veröffentlicht</th><th>Detail</th><th>Aktion</th></tr></thead><tbody>'
        . implode('', $zeilenHtml) . '</tbody></table>';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Datenqualität – Admin</title>
    <link rel="stylesheet" href="verwaltung.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h1>Datenqualität</h1>
        <div class="admin-header-aktionen">
            <a href="index.php">Zur Standortliste</a>
        </div>
    </div>

    <p class="admin-anzahl"><?= count($standorte) ?> Standorte insgesamt geprüft.</p>

    <h2>1. Fehlende Horizontgrafik</h2>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium1)) ?>

    <h2>2. Hartes Kriterium – "Geeignet" ohne Datum "zuletzt vor Ort geprüft"</h2>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium2)) ?>

    <h2>2w. Nur zur Information – "Eingeschränkt geeignet" ohne Datum "zuletzt vor Ort geprüft"</h2>
    <p class="admin-anzahl">Kein hartes Kriterium: dass Standorte noch nicht vor Ort geprüft wurden, ist normal.</p>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium2w)) ?>

    <h2 title="Geprüft bei veroeffentlicht=1 und Status Geeignet/Eingeschränkt geeignet: zugaenglichkeit, parkplatz, andrang_erwartet, sicherheitsrisiken, kartenlink, region, entfernung_bad_homburg_km, fahrzeit_minuten, hoehe_meter, horizontbewertung, gesamtbewertung, kurze_bewertung. Bei Status Geeignet zaehlt zusaetzlich zuletzt_vor_ort_geprueft als Pflichtfeld.">2b. Veröffentlicht, aber sonst unvollständig</h2>
    <?= dq_tabelle(array_map(fn($e) => dq_zeile($e['standort'], implode(', ', $e['fehlend'])), $kriterium2b)) ?>

    <h2>3a. Kein OpenStreetMap-Link</h2>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium3a)) ?>

    <h2>3b. OpenStreetMap-Link zeigt falsche Koordinaten</h2>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium3b)) ?>

    <h2>4. Entfernung/Fahrzeit ab Bad Homburg fehlt</h2>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium4)) ?>

    <h2>5. Koordinaten unplausibel (außerhalb Breite 49–51° / Länge 7,5–9,5°)</h2>
    <?= dq_tabelle(array_map(fn($s) => dq_zeile($s), $kriterium5)) ?>

    <h2>6. Mögliche Duplikate (Koordinaten &lt; 200 m auseinander)</h2>
    <?php if (!$kriterium6): ?>
        <p class="erfolg">Keine Treffer.</p>
    <?php else: ?>
        <table class="admin-tabelle">
            <thead><tr><th>Standort A</th><th>Standort B</th><th>Abstand</th><th>Aktion</th></tr></thead>
            <tbody>
            <?php foreach ($kriterium6 as $paar): ?>
                <tr>
                    <td><?= htmlspecialchars($paar['a']['standortname']) ?></td>
                    <td><?= htmlspecialchars($paar['b']['standortname']) ?></td>
                    <td><?= (int) $paar['distanz'] ?> m</td>
                    <td>
                        <a href="edit.php?id=<?= (int) $paar['a']['id'] ?>">A bearbeiten</a>
                        &middot;
                        <a href="edit.php?id=<?= (int) $paar['b']['id'] ?>">B bearbeiten</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>7. Fotos ohne Autor/Quelle oder Lizenz</h2>
    <?= dq_tabelle(array_map(fn($e) => dq_zeile($e['standort'], $e['dateiname'] . ': ' . implode(', ', $e['fehlend'])), $kriterium7)) ?>

    <h2 title="Dateien in site/uploads/, auf die keine Zeile in standort_fotos mehr verweist. Können manuell im Dateisystem gelöscht werden.">8. Datei-Leichen in uploads/ (keine DB-Referenz mehr)</h2>
    <?php if (!$kriterium8): ?>
        <p class="erfolg">Keine Treffer.</p>
    <?php else: ?>
        <table class="admin-tabelle">
            <thead><tr><th>Dateiname</th></tr></thead>
            <tbody>
            <?php foreach ($kriterium8 as $datei): ?>
                <tr><td><?= htmlspecialchars($datei) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
