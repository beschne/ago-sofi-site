<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';
csrf_token(); // Session-Cookie muss gesetzt sein, bevor unten HTML-Ausgabe beginnt

$statusFilter = $_GET['status'] ?? '';
$veroeffentlichtFilter = $_GET['veroeffentlicht'] ?? '';
$pdo = ago_sofi_db();

$bedingungen = [];
$parameter = [];
if ($statusFilter !== '') {
    $bedingungen[] = 'status = ?';
    $parameter[] = $statusFilter;
}
if ($veroeffentlichtFilter !== '') {
    $bedingungen[] = 'veroeffentlicht = ?';
    $parameter[] = $veroeffentlichtFilter === 'ja' ? 1 : 0;
}

$sql = 'SELECT * FROM standorte';
if ($bedingungen) {
    $sql .= ' WHERE ' . implode(' AND ', $bedingungen);
}
$sql .= ' ORDER BY standortname';
$stmt = $pdo->prepare($sql);
$stmt->execute($parameter);
$standorte = $stmt->fetchAll();

$alleStatus = ['Vorschlag', 'Zu prüfen', 'Vor Ort geprüft', 'Geeignet', 'Eingeschränkt geeignet', 'Ungeeignet', 'Nicht mehr verfügbar'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Standorte verwalten – Admin</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h1>Standorte verwalten</h1>
        <div class="admin-header-aktionen">
            <a href="bilder.php">Bilder verwalten</a>
            <a href="datenqualitaet.php">Datenqualität</a>
            <a href="edit.php" class="btn">+ Neuer Standort</a>
        </div>
    </div>

    <?php if (isset($_GET['gespeichert'])): ?>
        <p class="erfolg">Standort gespeichert.</p>
    <?php endif; ?>
    <?php if (isset($_GET['geloescht'])): ?>
        <p class="erfolg">Standort gelöscht.</p>
    <?php endif; ?>

    <form method="get" style="margin-bottom: 1rem; display: flex; gap: 1.5rem; align-items: center;">
        <span>
            <label for="status">Nach Status filtern:</label>
            <select name="status" id="status" onchange="this.form.submit()">
                <option value="">Alle</option>
                <?php foreach ($alleStatus as $status): ?>
                    <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                <?php endforeach; ?>
            </select>
        </span>
        <span>
            <label for="veroeffentlicht">Veröffentlicht:</label>
            <select name="veroeffentlicht" id="veroeffentlicht" onchange="this.form.submit()">
                <option value="" <?= $veroeffentlichtFilter === '' ? 'selected' : '' ?>>Alle</option>
                <option value="ja" <?= $veroeffentlichtFilter === 'ja' ? 'selected' : '' ?>>Ja</option>
                <option value="nein" <?= $veroeffentlichtFilter === 'nein' ? 'selected' : '' ?>>Nein</option>
            </select>
        </span>
    </form>

    <table class="admin-tabelle">
        <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Veröffentlicht</th>
            <th>Aktionen</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($standorte as $s): ?>
            <tr>
                <td><?= vor_ort_marker($s['zuletzt_vor_ort_geprueft']) ?><?= htmlspecialchars($s['standortname']) ?></td>
                <td><?= htmlspecialchars($s['status']) ?></td>
                <td><?= $s['veroeffentlicht'] ? 'Ja' : 'Nein' ?></td>
                <td>
                    <a href="edit.php?id=<?= (int) $s['id'] ?>">Bearbeiten</a>
                    &middot;
                    <a href="/standort/<?= htmlspecialchars($s['slug']) ?>" target="_blank">Ansehen</a>
                    &middot;
                    <form method="post" action="delete.php" style="display:inline"
                          onsubmit="return confirm('Standort &quot;<?= htmlspecialchars(addslashes($s['standortname'])) ?>&quot; wirklich löschen?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                        <button type="submit" class="btn btn-gefahr" style="padding: 0 0.4rem; font-size: 0.8rem;">Löschen</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="admin-anzahl"><?= count($standorte) ?> Standort<?= count($standorte) === 1 ? '' : 'e' ?> angezeigt.</p>
</div>
</body>
</html>
