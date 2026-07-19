<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/upload.php';

$pdo = ago_sofi_db();
$alleStatus = ['Vorschlag', 'Zu prüfen', 'Vor Ort geprüft', 'Geeignet', 'Eingeschränkt geeignet', 'Ungeeignet', 'Nicht mehr verfügbar'];
$fotoKategorien = [
    'horizontfoto' => 'Horizontfoto',
    'panorama' => 'Panorama',
    'horizontgrafik' => 'Horizontgrafik',
    'weiteres' => 'Weitere Fotos (mehrere möglich)',
];

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
        'zugaenglichkeit' => trim($_POST['zugaenglichkeit'] ?? ''),
        'parkplatz' => trim($_POST['parkplatz'] ?? ''),
        'andrang_erwartet' => trim($_POST['andrang_erwartet'] ?? ''),
        'sicherheitsrisiken' => trim($_POST['sicherheitsrisiken'] ?? ''),
        'kartenlink' => trim($_POST['kartenlink'] ?? ''),
        'region' => trim($_POST['region'] ?? ''),
        'entfernung_bad_homburg_km' => $_POST['entfernung_bad_homburg_km'] ?? '',
        'fahrzeit_minuten' => $_POST['fahrzeit_minuten'] ?? '',
        'horizontbewertung' => trim($_POST['horizontbewertung'] ?? ''),
        'gesamtbewertung' => $_POST['gesamtbewertung'] ?? '',
        'kurze_bewertung' => trim($_POST['kurze_bewertung'] ?? ''),
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
    foreach (['entfernung_bad_homburg_km', 'fahrzeit_minuten', 'gesamtbewertung'] as $feld) {
        if ($daten[$feld] === '') {
            $daten[$feld] = null;
        }
    }
    foreach (['zugaenglichkeit', 'parkplatz', 'andrang_erwartet', 'sicherheitsrisiken', 'kartenlink', 'region', 'horizontbewertung', 'kurzbeschreibung', 'kurze_bewertung'] as $feld) {
        if ($daten[$feld] === '') {
            $daten[$feld] = null;
        }
    }

    // Foto-Uploads verarbeiten (auch bei Validierungsfehlern schon prüfen, um früh Fehler zu zeigen)
    $neueFotos = []; // kategorie => [dateiname, ...]
    try {
        foreach (['horizontfoto', 'panorama', 'horizontgrafik'] as $kategorie) {
            if (!empty($_FILES[$kategorie]['name'])) {
                $dateiname = verarbeite_foto_upload($_FILES[$kategorie]);
                if ($dateiname) {
                    $neueFotos[$kategorie] = [$dateiname];
                }
            }
        }
        if (!empty($_FILES['weiteres']['name'][0])) {
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
                $dateiname = verarbeite_foto_upload($einzelDatei);
                if ($dateiname) {
                    $neueFotos['weiteres'][] = $dateiname;
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
                    laengengrad=:laengengrad, zugaenglichkeit=:zugaenglichkeit, parkplatz=:parkplatz,
                    andrang_erwartet=:andrang_erwartet, sicherheitsrisiken=:sicherheitsrisiken,
                    kartenlink=:kartenlink, region=:region,
                    entfernung_bad_homburg_km=:entfernung_bad_homburg_km, fahrzeit_minuten=:fahrzeit_minuten,
                    horizontbewertung=:horizontbewertung, gesamtbewertung=:gesamtbewertung,
                    kurze_bewertung=:kurze_bewertung
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
            foreach ($dateien as $sortierung => $dateiname) {
                $insertFoto = $pdo->prepare('INSERT INTO standort_fotos (standort_id, kategorie, dateiname, sortierung) VALUES (?, ?, ?, ?)');
                $insertFoto->execute([$id, $kategorie, $dateiname, $sortierung]);
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

        <label for="region">Region</label>
        <input type="text" id="region" name="region" value="<?= feld($bestehend, 'region') ?>">

        <label for="zugaenglichkeit">Zugänglichkeit</label>
        <input type="text" id="zugaenglichkeit" name="zugaenglichkeit" value="<?= feld($bestehend, 'zugaenglichkeit') ?>">

        <label for="parkplatz">Parkplatz</label>
        <input type="text" id="parkplatz" name="parkplatz" value="<?= feld($bestehend, 'parkplatz') ?>">

        <label for="andrang_erwartet">Erwarteter Andrang</label>
        <input type="text" id="andrang_erwartet" name="andrang_erwartet" value="<?= feld($bestehend, 'andrang_erwartet') ?>">

        <label for="sicherheitsrisiken">Sicherheitsrisiken</label>
        <input type="text" id="sicherheitsrisiken" name="sicherheitsrisiken" value="<?= feld($bestehend, 'sicherheitsrisiken') ?>">

        <label for="kartenlink">Kartenlink (URL)</label>
        <input type="url" id="kartenlink" name="kartenlink" value="<?= feld($bestehend, 'kartenlink') ?>">

        <label for="entfernung_bad_homburg_km">Entfernung ab Bad Homburg (km)</label>
        <input type="number" step="0.1" id="entfernung_bad_homburg_km" name="entfernung_bad_homburg_km" value="<?= feld($bestehend, 'entfernung_bad_homburg_km') ?>">

        <label for="fahrzeit_minuten">Fahrzeit ab Bad Homburg (Minuten)</label>
        <input type="number" id="fahrzeit_minuten" name="fahrzeit_minuten" value="<?= feld($bestehend, 'fahrzeit_minuten') ?>">

        <label for="horizontbewertung">Horizontbewertung</label>
        <input type="text" id="horizontbewertung" name="horizontbewertung" value="<?= feld($bestehend, 'horizontbewertung') ?>">

        <label for="gesamtbewertung">Gesamtbewertung (1–5)</label>
        <input type="number" min="1" max="5" id="gesamtbewertung" name="gesamtbewertung" value="<?= feld($bestehend, 'gesamtbewertung') ?>">

        <label for="kurze_bewertung">Kurze Einschätzung</label>
        <textarea id="kurze_bewertung" name="kurze_bewertung"><?= feld($bestehend, 'kurze_bewertung') ?></textarea>

        <h2>Fotos &amp; Grafiken</h2>
        <?php foreach ($fotoKategorien as $kategorie => $label): ?>
            <label for="foto_<?= $kategorie ?>"><?= htmlspecialchars($label) ?></label>
            <?php if (!empty($fotos[$kategorie])): ?>
                <div class="bestehende-fotos">
                    <?php foreach ($fotos[$kategorie] as $foto): ?>
                        <label>
                            <img src="/uploads/<?= htmlspecialchars($foto['dateiname']) ?>" alt="">
                            <br><input type="checkbox" name="foto_loeschen[]" value="<?= (int) $foto['id'] ?>"> löschen
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="file" id="foto_<?= $kategorie ?>" name="<?= $kategorie ?>" accept="image/jpeg,image/png,image/webp" <?= $kategorie === 'weiteres' ? 'multiple' : '' ?>>
        <?php endforeach; ?>
        <p class="hinweis">JPG, PNG oder WebP, max. 8&nbsp;MB pro Datei.</p>

        <div class="admin-actions">
            <button type="submit" class="btn">Speichern</button>
        </div>
    </form>
</div>
</body>
</html>
