<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/upload.php';
csrf_token(); // Session-Cookie muss gesetzt sein, bevor unten HTML-Ausgabe beginnt

$pdo = ago_sofi_db();
$alleStatus = ['Vorschlag', 'Zu prüfen', 'Vor Ort geprüft', 'Geeignet', 'Eingeschränkt geeignet', 'Ungeeignet', 'Nicht mehr verfügbar'];
$fotoKategorien = [
    'horizontfoto' => 'Horizontfoto',
    'panorama' => 'Panorama',
    'horizontgrafik' => 'Horizontgrafik',
    'weiteres' => 'Weitere Fotos (mehrere möglich)',
];
$lizenzOptionen = [
    'Eigenes Werk (AGO)',
    'CC BY 4.0',
    'CC BY-SA 4.0',
    'CC0 / Public Domain',
    'Freigabe durch Urheber',
    'Sonstige',
];
$regionOptionen = ['Vordertaunus', 'Hintertaunus', 'Wetterau-Rand', 'Odenwald/Bergstraße', 'Wetterau', 'Vogelsberg', 'Rhön', 'Lahn-Dill-Bergland'];
$zugaenglichkeitOptionen = ['Jederzeit frei zugänglich', 'Tagsüber frei zugänglich', 'Nur zu Fuß erreichbar', 'Genehmigung erforderlich', 'Privatgelände', 'Gesperrt', 'Unbekannt'];
$parkplatzOptionen = ['Direkt am Standort', '< 100 m', '100 - 500m', '> 500m', 'Kein Parkplatz', 'Unbekannt'];
$andrangOptionen = ['Sehr gering', 'Gering', 'Mittel', 'Hoch', 'Sehr hoch', 'Unbekannt'];
$sicherheitsrisikenOptionen = ['Keine bekannt', 'Gering', 'Mittel', 'Hoch', 'Nicht bewertet'];

$id = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id']) ? (int) $_POST['id'] : null);
$fehler = [];

// Bestehenden Datensatz laden (für GET-Formular oder um bei POST-Fehlern Fotos erneut anzuzeigen)
$bestehend = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM standorte WHERE id = ?');
    $stmt->execute([$id]);
    $bestehend = $stmt->fetch();
    if (!$bestehend) {
        http_response_code(404);
        die('Standort nicht gefunden.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $daten = [
        'standortname' => trim($_POST['standortname'] ?? ''),
        'kurzbeschreibung' => trim($_POST['kurzbeschreibung'] ?? ''),
        'status' => $_POST['status'] ?? 'Vorschlag',
        'veroeffentlicht' => isset($_POST['veroeffentlicht']) ? 1 : 0,
        'breitengrad' => $_POST['breitengrad'] ?? '',
        'laengengrad' => $_POST['laengengrad'] ?? '',
        'hoehe_meter' => $_POST['hoehe_meter'] ?? '',
        'hoehe_vom_turm' => isset($_POST['hoehe_vom_turm']) ? 1 : 0,
        'zugaenglichkeit' => trim($_POST['zugaenglichkeit'] ?? ''),
        'parkplatz' => trim($_POST['parkplatz'] ?? ''),
        'andrang_erwartet' => trim($_POST['andrang_erwartet'] ?? ''),
        'sicherheitsrisiken' => trim($_POST['sicherheitsrisiken'] ?? ''),
        'kartenlink' => trim($_POST['kartenlink'] ?? ''),
        'region' => trim($_POST['region'] ?? ''),
        'entfernung_bad_homburg_km' => $_POST['entfernung_bad_homburg_km'] ?? '',
        'fahrzeit_minuten' => $_POST['fahrzeit_minuten'] ?? '',
        'zuletzt_vor_ort_geprueft' => $_POST['zuletzt_vor_ort_geprueft'] ?? '',
        'horizontbewertung' => $_POST['horizontbewertung'] ?? '',
        'gesamtbewertung' => $_POST['gesamtbewertung'] ?? '',
        'kurze_bewertung' => trim($_POST['kurze_bewertung'] ?? ''),
        'interne_notiz' => trim($_POST['interne_notiz'] ?? ''),
    ];

    if ($daten['standortname'] === '') {
        $fehler[] = 'Standortname darf nicht leer sein.';
    }
    if (!is_numeric($daten['breitengrad']) || !is_numeric($daten['laengengrad'])) {
        $fehler[] = 'Breitengrad und Längengrad müssen Zahlen sein.';
    }
    if (!in_array($daten['status'], $alleStatus, true)) {
        $fehler[] = 'Ungültiger Status.';
    }

    // Numerische Leerfelder als NULL statt leerem String behandeln
    foreach (['entfernung_bad_homburg_km', 'fahrzeit_minuten', 'hoehe_meter', 'horizontbewertung', 'gesamtbewertung'] as $feld) {
        if ($daten[$feld] === '') {
            $daten[$feld] = null;
        }
    }
    foreach (['zugaenglichkeit', 'parkplatz', 'andrang_erwartet', 'sicherheitsrisiken', 'kartenlink', 'region', 'kurzbeschreibung', 'kurze_bewertung', 'interne_notiz', 'zuletzt_vor_ort_geprueft'] as $feld) {
        if ($daten[$feld] === '') {
            $daten[$feld] = null;
        }
    }

    if (!in_array($daten['zugaenglichkeit'], array_merge($zugaenglichkeitOptionen, [null]), true)) {
        $fehler[] = 'Ungültige Zugänglichkeit.';
    }
    if (!in_array($daten['parkplatz'], array_merge($parkplatzOptionen, [null]), true)) {
        $fehler[] = 'Ungültiger Parkplatz.';
    }
    if (!in_array($daten['andrang_erwartet'], array_merge($andrangOptionen, [null]), true)) {
        $fehler[] = 'Ungültiger erwarteter Andrang.';
    }
    if (!in_array($daten['sicherheitsrisiken'], array_merge($sicherheitsrisikenOptionen, [null]), true)) {
        $fehler[] = 'Ungültige Sicherheitsrisiken.';
    }
    if (!in_array($daten['region'], array_merge($regionOptionen, [null]), true)) {
        $fehler[] = 'Ungültige Region.';
    }
    if ($daten['kartenlink'] !== null && !preg_match('#^https?://#i', $daten['kartenlink'])) {
        $fehler[] = 'Kartenlink muss mit http:// oder https:// beginnen.';
    }

    // Foto-Uploads verarbeiten (auch bei Validierungsfehlern schon prüfen, um früh Fehler zu zeigen)
    $neueFotos = []; // kategorie => [ ['dateiname'=>, 'autor_quelle'=>, 'lizenz'=>, 'aufnahme_zeitpunkt'=>, 'gps_breitengrad'=>, 'gps_laengengrad'=>], ... ]
    try {
        foreach (['horizontfoto', 'panorama', 'horizontgrafik'] as $kategorie) {
            if (!empty($_FILES[$kategorie]['name'])) {
                $fotoDaten = verarbeite_foto_upload($_FILES[$kategorie]);
                if ($fotoDaten) {
                    $autor = trim($_POST['foto_autor_' . $kategorie] ?? '');
                    $lizenz = $_POST['foto_lizenz_' . $kategorie] ?? '';
                    $fotoDaten['autor_quelle'] = $autor !== '' ? $autor : null;
                    $fotoDaten['lizenz'] = in_array($lizenz, $lizenzOptionen, true) ? $lizenz : null;
                    $neueFotos[$kategorie] = [$fotoDaten];
                }
            }
        }
        if (!empty($_FILES['weiteres']['name'][0])) {
            $autorWeiteres = trim($_POST['foto_autor_weiteres'] ?? '');
            $autorWeiteres = $autorWeiteres !== '' ? $autorWeiteres : null;
            $lizenzWeiteresRoh = $_POST['foto_lizenz_weiteres'] ?? '';
            $lizenzWeiteres = in_array($lizenzWeiteresRoh, $lizenzOptionen, true) ? $lizenzWeiteresRoh : null;

            $anzahl = count($_FILES['weiteres']['name']);
            for ($i = 0; $i < $anzahl; $i++) {
                if ($_FILES['weiteres']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $einzelDatei = [
                    'name' => $_FILES['weiteres']['name'][$i],
                    'type' => $_FILES['weiteres']['type'][$i],
                    'tmp_name' => $_FILES['weiteres']['tmp_name'][$i],
                    'error' => $_FILES['weiteres']['error'][$i],
                    'size' => $_FILES['weiteres']['size'][$i],
                ];
                $fotoDaten = verarbeite_foto_upload($einzelDatei);
                if ($fotoDaten) {
                    $fotoDaten['autor_quelle'] = $autorWeiteres;
                    $fotoDaten['lizenz'] = $lizenzWeiteres;
                    $neueFotos['weiteres'][] = $fotoDaten;
                }
            }
        }
    } catch (RuntimeException $e) {
        $fehler[] = $e->getMessage();
    }

    if (empty($fehler)) {
        if ($id) {
            $sql = 'UPDATE standorte SET standortname=:standortname, kurzbeschreibung=:kurzbeschreibung,
                    status=:status, veroeffentlicht=:veroeffentlicht, breitengrad=:breitengrad,
                    laengengrad=:laengengrad, hoehe_meter=:hoehe_meter, hoehe_vom_turm=:hoehe_vom_turm,
                    zugaenglichkeit=:zugaenglichkeit, parkplatz=:parkplatz,
                    andrang_erwartet=:andrang_erwartet, sicherheitsrisiken=:sicherheitsrisiken,
                    kartenlink=:kartenlink, region=:region,
                    entfernung_bad_homburg_km=:entfernung_bad_homburg_km, fahrzeit_minuten=:fahrzeit_minuten,
                    zuletzt_vor_ort_geprueft=:zuletzt_vor_ort_geprueft,
                    horizontbewertung=:horizontbewertung, gesamtbewertung=:gesamtbewertung,
                    kurze_bewertung=:kurze_bewertung, interne_notiz=:interne_notiz
                    WHERE id=:id';
            $daten['id'] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($daten);
        } else {
            $slug = eindeutiger_slug($pdo, slugify($daten['standortname']));
            $daten['slug'] = $slug;
            $spalten = array_keys($daten);
            $platzhalter = array_map(fn($s) => ':' . $s, $spalten);
            $sql = 'INSERT INTO standorte (' . implode(',', $spalten) . ') VALUES (' . implode(',', $platzhalter) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($daten);
            $id = (int) $pdo->lastInsertId();
        }

        // Zum Löschen markierte bestehende Fotos entfernen
        if (!empty($_POST['foto_loeschen']) && is_array($_POST['foto_loeschen'])) {
            foreach ($_POST['foto_loeschen'] as $fotoId) {
                $fotoId = (int) $fotoId;
                $stmt = $pdo->prepare('SELECT dateiname FROM standort_fotos WHERE id = ? AND standort_id = ?');
                $stmt->execute([$fotoId, $id]);
                $foto = $stmt->fetch();
                if ($foto) {
                    @unlink(ago_sofi_uploads_dir() . '/' . $foto['dateiname']);
                    $del = $pdo->prepare('DELETE FROM standort_fotos WHERE id = ?');
                    $del->execute([$fotoId]);
                }
            }
        }

        // Metadaten bestehender Fotos aktualisieren
        if (!empty($_POST['foto_meta']) && is_array($_POST['foto_meta'])) {
            foreach ($_POST['foto_meta'] as $fotoId => $meta) {
                $fotoId = (int) $fotoId;
                $beschreibung = trim($meta['beschreibung'] ?? '');
                $beschreibung = $beschreibung !== '' ? $beschreibung : null;
                $autor = trim($meta['autor_quelle'] ?? '');
                $autor = $autor !== '' ? $autor : null;
                $lizenzRoh = $meta['lizenz'] ?? '';
                $lizenz = in_array($lizenzRoh, $lizenzOptionen, true) ? $lizenzRoh : null;

                $zeit = null;
                $zeitRoh = trim($meta['aufnahme_zeitpunkt'] ?? '');
                if ($zeitRoh !== '') {
                    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $zeitRoh);
                    if ($dt !== false) {
                        $zeit = $dt->format('Y-m-d H:i:s');
                    }
                }

                $lat = is_numeric($meta['gps_breitengrad'] ?? '') ? (float) $meta['gps_breitengrad'] : null;
                $lon = is_numeric($meta['gps_laengengrad'] ?? '') ? (float) $meta['gps_laengengrad'] : null;

                $update = $pdo->prepare(
                    'UPDATE standort_fotos SET beschreibung=?, autor_quelle=?, lizenz=?, aufnahme_zeitpunkt=?, gps_breitengrad=?, gps_laengengrad=?
                     WHERE id=? AND standort_id=?'
                );
                $update->execute([$beschreibung, $autor, $lizenz, $zeit, $lat, $lon, $fotoId, $id]);
            }
        }

        // Neue Fotos speichern (Einzel-Kategorien ersetzen ein evtl. vorhandenes Foto derselben Kategorie)
        foreach ($neueFotos as $kategorie => $dateien) {
            if ($kategorie !== 'weiteres') {
                $alt = $pdo->prepare('SELECT id, dateiname FROM standort_fotos WHERE standort_id = ? AND kategorie = ?');
                $alt->execute([$id, $kategorie]);
                foreach ($alt->fetchAll() as $altesFoto) {
                    @unlink(ago_sofi_uploads_dir() . '/' . $altesFoto['dateiname']);
                    $pdo->prepare('DELETE FROM standort_fotos WHERE id = ?')->execute([$altesFoto['id']]);
                }
            }
            foreach ($dateien as $sortierung => $fotoDaten) {
                $insertFoto = $pdo->prepare(
                    'INSERT INTO standort_fotos
                     (standort_id, kategorie, dateiname, sortierung, autor_quelle, lizenz, aufnahme_zeitpunkt, gps_breitengrad, gps_laengengrad)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $insertFoto->execute([
                    $id, $kategorie, $fotoDaten['dateiname'], $sortierung,
                    $fotoDaten['autor_quelle'], $fotoDaten['lizenz'],
                    $fotoDaten['aufnahme_zeitpunkt'], $fotoDaten['gps_breitengrad'], $fotoDaten['gps_laengengrad'],
                ]);
            }
        }

        header('Location: index.php?gespeichert=1');
        exit;
    }

    // Bei Fehlern: $daten für erneute Anzeige im Formular verwenden
    $bestehend = array_merge($bestehend ?? [], $daten, ['id' => $id, 'slug' => $bestehend['slug'] ?? null]);
}

$fotos = [];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM standort_fotos WHERE standort_id = ? ORDER BY kategorie, sortierung');
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $foto) {
        $fotos[$foto['kategorie']][] = $foto;
    }
}

function feld(?array $daten, string $name, $fallback = ''): string {
    return htmlspecialchars((string) ($daten[$name] ?? $fallback));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? 'Standort bearbeiten' : 'Neuer Standort' ?> – Admin</title>
    <link rel="stylesheet" href="verwaltung.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h1><?= $id ? 'Standort bearbeiten' : 'Neuer Standort' ?></h1>
        <a href="index.php">&larr; Zurück zur Übersicht</a>
    </div>

    <?php foreach ($fehler as $f): ?>
        <p class="fehler"><?= htmlspecialchars($f) ?></p>
    <?php endforeach; ?>

    <form class="admin-form" method="post" action="edit.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <label for="standortname">Standortname *</label>
        <input type="text" id="standortname" name="standortname" required value="<?= feld($bestehend, 'standortname') ?>">

        <label for="status">Status *</label>
        <select id="status" name="status">
            <?php foreach ($alleStatus as $status): ?>
                <option value="<?= htmlspecialchars($status) ?>" <?= ($bestehend['status'] ?? '') === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
            <?php endforeach; ?>
        </select>

        <label><input type="checkbox" name="veroeffentlicht" <?= !empty($bestehend['veroeffentlicht']) ? 'checked' : '' ?>> Veröffentlicht (öffentlich sichtbar)</label>

        <label for="kurzbeschreibung">Kurzbeschreibung</label>
        <textarea id="kurzbeschreibung" name="kurzbeschreibung"><?= feld($bestehend, 'kurzbeschreibung') ?></textarea>

        <label for="breitengrad">Breitengrad *</label>
        <input type="text" id="breitengrad" name="breitengrad" required value="<?= feld($bestehend, 'breitengrad') ?>">

        <label for="laengengrad">Längengrad *</label>
        <input type="text" id="laengengrad" name="laengengrad" required value="<?= feld($bestehend, 'laengengrad') ?>">

        <label for="hoehe_meter">Höhe (m ü. NN)</label>
        <input type="number" min="0" id="hoehe_meter" name="hoehe_meter" value="<?= feld($bestehend, 'hoehe_meter') ?>">

        <label><input type="checkbox" name="hoehe_vom_turm" <?= !empty($bestehend['hoehe_vom_turm']) ? 'checked' : '' ?>> Höhe vom Turm (Aussichtsplattform, nicht Bodenniveau)</label>

        <label for="region">Region</label>
        <select id="region" name="region">
            <option value="">–</option>
            <?php foreach ($regionOptionen as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($bestehend['region'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="zugaenglichkeit">Zugänglichkeit</label>
        <select id="zugaenglichkeit" name="zugaenglichkeit">
            <option value="">–</option>
            <?php foreach ($zugaenglichkeitOptionen as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($bestehend['zugaenglichkeit'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="parkplatz">Parkplatz</label>
        <select id="parkplatz" name="parkplatz">
            <option value="">–</option>
            <?php foreach ($parkplatzOptionen as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($bestehend['parkplatz'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="andrang_erwartet">Erwarteter Andrang</label>
        <select id="andrang_erwartet" name="andrang_erwartet">
            <option value="">–</option>
            <?php foreach ($andrangOptionen as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($bestehend['andrang_erwartet'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="sicherheitsrisiken">Sicherheitsrisiken</label>
        <select id="sicherheitsrisiken" name="sicherheitsrisiken">
            <option value="">–</option>
            <?php foreach ($sicherheitsrisikenOptionen as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>" <?= ($bestehend['sicherheitsrisiken'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="kartenlink">Kartenlink (URL)</label>
        <input type="url" id="kartenlink" name="kartenlink" value="<?= feld($bestehend, 'kartenlink') ?>">

        <label for="entfernung_bad_homburg_km">Entfernung ab Bad Homburg (km)</label>
        <input type="number" step="0.1" id="entfernung_bad_homburg_km" name="entfernung_bad_homburg_km" value="<?= feld($bestehend, 'entfernung_bad_homburg_km') ?>">

        <label for="fahrzeit_minuten">Fahrzeit ab Bad Homburg (Minuten)</label>
        <input type="number" id="fahrzeit_minuten" name="fahrzeit_minuten" value="<?= feld($bestehend, 'fahrzeit_minuten') ?>">

        <label for="zuletzt_vor_ort_geprueft">Zuletzt vor Ort geprüft (öffentlich sichtbar, wenn ausgefüllt)</label>
        <input type="date" id="zuletzt_vor_ort_geprueft" name="zuletzt_vor_ort_geprueft" value="<?= feld($bestehend, 'zuletzt_vor_ort_geprueft') ?>">

        <label for="horizontbewertung">Horizontbewertung (0–5)</label>
        <input type="number" min="0" max="5" id="horizontbewertung" name="horizontbewertung" value="<?= feld($bestehend, 'horizontbewertung') ?>">

        <label for="gesamtbewertung">Gesamtbewertung (1–5)</label>
        <input type="number" min="1" max="5" id="gesamtbewertung" name="gesamtbewertung" value="<?= feld($bestehend, 'gesamtbewertung') ?>">

        <label for="kurze_bewertung">Kurze Einschätzung</label>
        <textarea id="kurze_bewertung" name="kurze_bewertung"><?= feld($bestehend, 'kurze_bewertung') ?></textarea>

        <label for="interne_notiz">Interne Notiz (nie öffentlich sichtbar)</label>
        <textarea id="interne_notiz" name="interne_notiz"><?= feld($bestehend, 'interne_notiz') ?></textarea>

        <h2>Fotos &amp; Grafiken</h2>
        <p class="hinweis">
            Bitte keine personenbezogenen Daten bei Autor/Quelle eintragen — bei
            AGO-Mitgliedern einfach „Mitglied" statt Name verwenden.
        </p>

        <?php foreach ($fotoKategorien as $kategorie => $label): ?>
            <h3><?= htmlspecialchars($label) ?></h3>

            <?php if (!empty($fotos[$kategorie])): ?>
                <div class="bestehende-fotos">
                    <?php foreach ($fotos[$kategorie] as $foto): ?>
                        <div class="bestehendes-foto">
                            <img src="/uploads/<?= htmlspecialchars($foto['dateiname']) ?>" alt="">

                            <label>Beschreibung
                                <input type="text" maxlength="255" name="foto_meta[<?= (int) $foto['id'] ?>][beschreibung]" value="<?= htmlspecialchars($foto['beschreibung'] ?? '') ?>">
                            </label>

                            <label>Autor/Quelle
                                <input type="text" name="foto_meta[<?= (int) $foto['id'] ?>][autor_quelle]" value="<?= htmlspecialchars($foto['autor_quelle'] ?? '') ?>">
                            </label>

                            <label>Lizenz
                                <select name="foto_meta[<?= (int) $foto['id'] ?>][lizenz]">
                                    <option value="">–</option>
                                    <?php foreach ($lizenzOptionen as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt) ?>" <?= ($foto['lizenz'] ?? '') === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label>Aufnahmezeitpunkt
                                <input type="datetime-local" name="foto_meta[<?= (int) $foto['id'] ?>][aufnahme_zeitpunkt]"
                                       value="<?= $foto['aufnahme_zeitpunkt'] ? date('Y-m-d\TH:i', strtotime($foto['aufnahme_zeitpunkt'])) : '' ?>">
                            </label>

                            <label>GPS Breitengrad
                                <input type="number" step="0.000001" name="foto_meta[<?= (int) $foto['id'] ?>][gps_breitengrad]" value="<?= htmlspecialchars($foto['gps_breitengrad'] ?? '') ?>">
                            </label>

                            <label>GPS Längengrad
                                <input type="number" step="0.000001" name="foto_meta[<?= (int) $foto['id'] ?>][gps_laengengrad]" value="<?= htmlspecialchars($foto['gps_laengengrad'] ?? '') ?>">
                            </label>

                            <?php if ($osmLink = foto_osm_link($foto)): ?>
                                <p class="hinweis"><a href="<?= htmlspecialchars($osmLink) ?>" target="_blank" rel="noopener">Aufnahmeort auf OpenStreetMap</a></p>
                            <?php endif; ?>

                            <label><input type="checkbox" name="foto_loeschen[]" value="<?= (int) $foto['id'] ?>"> löschen</label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label for="foto_<?= $kategorie ?>">Neue Datei hochladen</label>
            <input type="file" id="foto_<?= $kategorie ?>" name="<?= $kategorie === 'weiteres' ? 'weiteres[]' : $kategorie ?>" accept="image/jpeg,image/png,image/webp" <?= $kategorie === 'weiteres' ? 'multiple' : '' ?>>

            <label for="foto_autor_<?= $kategorie ?>">Autor/Quelle (für neue Datei<?= $kategorie === 'weiteres' ? 'en' : '' ?>)</label>
            <input type="text" id="foto_autor_<?= $kategorie ?>" name="foto_autor_<?= $kategorie ?>" placeholder="z. B. Mitglied">

            <label for="foto_lizenz_<?= $kategorie ?>">Lizenz (für neue Datei<?= $kategorie === 'weiteres' ? 'en' : '' ?>)</label>
            <select id="foto_lizenz_<?= $kategorie ?>" name="foto_lizenz_<?= $kategorie ?>">
                <option value="">–</option>
                <?php foreach ($lizenzOptionen as $opt): ?>
                    <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>
        <p class="hinweis">
            JPG, PNG oder WebP, max. 8&nbsp;MB pro Datei. Aufnahmezeitpunkt und
            GPS-Koordinaten werden bei JPG-Dateien automatisch aus den EXIF-Daten
            übernommen (falls vorhanden) und lassen sich nach dem Speichern hier
            korrigieren.
        </p>

        <div class="admin-actions">
            <button type="submit" class="btn">Speichern</button>
        </div>
    </form>
</div>
</body>
</html>
