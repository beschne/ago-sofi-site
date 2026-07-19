<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/upload.php';

$pdo = ago_sofi_db();
$lizenzOptionen = [
    'Eigenes Werk (AGO)',
    'CC BY 4.0',
    'CC BY-SA 4.0',
    'CC0 / Public Domain',
    'Freigabe durch Urheber',
    'Sonstige',
];
$kategorieLabel = [
    'horizontfoto' => 'Horizontfoto',
    'panorama' => 'Panorama',
    'horizontgrafik' => 'Horizontgrafik',
    'weiteres' => 'Weitere Fotos',
];

$fehler = [];
$erfolg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Zum Löschen markierte Fotos entfernen
    if (!empty($_POST['foto_loeschen']) && is_array($_POST['foto_loeschen'])) {
        foreach ($_POST['foto_loeschen'] as $fotoId) {
            $fotoId = (int) $fotoId;
            $stmt = $pdo->prepare('SELECT dateiname FROM standort_fotos WHERE id = ?');
            $stmt->execute([$fotoId]);
            $foto = $stmt->fetch();
            if ($foto) {
                @unlink(ago_sofi_uploads_dir() . '/' . $foto['dateiname']);
                $pdo->prepare('DELETE FROM standort_fotos WHERE id = ?')->execute([$fotoId]);
            }
        }
    }

    // Metadaten aktualisieren
    if (!empty($_POST['foto_meta']) && is_array($_POST['foto_meta'])) {
        foreach ($_POST['foto_meta'] as $fotoId => $meta) {
            $fotoId = (int) $fotoId;
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
                'UPDATE standort_fotos SET autor_quelle=?, lizenz=?, aufnahme_zeitpunkt=?, gps_breitengrad=?, gps_laengengrad=?
                 WHERE id=?'
            );
            $update->execute([$autor, $lizenz, $zeit, $lat, $lon, $fotoId]);
        }
    }

    $erfolg = true;
}

$standortFilter = isset($_GET['standort_id']) ? (int) $_GET['standort_id'] : 0;

$sql = 'SELECT sf.*, s.standortname, s.slug
        FROM standort_fotos sf
        JOIN standorte s ON s.id = sf.standort_id';
if ($standortFilter) {
    $sql .= ' WHERE sf.standort_id = ' . (int) $standortFilter;
}
$sql .= ' ORDER BY s.standortname, sf.kategorie, sf.sortierung';
$fotos = $pdo->query($sql)->fetchAll();

$standorteFuerFilter = $pdo->query('SELECT id, standortname FROM standorte ORDER BY standortname')->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bilder verwalten – Admin</title>
    <link rel="stylesheet" href="verwaltung.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h1>Bilder verwalten</h1>
        <div class="admin-header-aktionen">
            <a href="index.php">&larr; Zurück zu Standorte</a>
        </div>
    </div>

    <?php if ($erfolg): ?>
        <p class="erfolg">Änderungen gespeichert.</p>
    <?php endif; ?>
    <?php foreach ($fehler as $f): ?>
        <p class="fehler"><?= htmlspecialchars($f) ?></p>
    <?php endforeach; ?>

    <form method="get" style="margin-bottom: 1rem;">
        <label for="standort_id">Nach Standort filtern:</label>
        <select name="standort_id" id="standort_id" onchange="this.form.submit()">
            <option value="">Alle Standorte</option>
            <?php foreach ($standorteFuerFilter as $st): ?>
                <option value="<?= (int) $st['id'] ?>" <?= $standortFilter === (int) $st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['standortname']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if (empty($fotos)): ?>
        <p>Keine Bilder vorhanden.</p>
    <?php else: ?>
        <form class="admin-form" method="post" action="bilder.php<?= $standortFilter ? '?standort_id=' . $standortFilter : '' ?>">
            <?= csrf_field() ?>
            <div class="bestehende-fotos">
                <?php foreach ($fotos as $foto): ?>
                    <div class="bestehendes-foto">
                        <p style="margin: 0 0 0.3rem;">
                            <a href="edit.php?id=<?= (int) $foto['standort_id'] ?>"><strong><?= htmlspecialchars($foto['standortname']) ?></strong></a>
                            <br>
                            <span class="hinweis"><?= htmlspecialchars($kategorieLabel[$foto['kategorie']] ?? $foto['kategorie']) ?></span>
                            &middot;
                            <a href="/standort/<?= htmlspecialchars($foto['slug']) ?>" target="_blank">ansehen</a>
                        </p>

                        <img src="/uploads/<?= htmlspecialchars($foto['dateiname']) ?>" alt="">

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

                        <label><input type="checkbox" name="foto_loeschen[]" value="<?= (int) $foto['id'] ?>"> löschen</label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="admin-actions">
                <button type="submit" class="btn">Speichern</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
