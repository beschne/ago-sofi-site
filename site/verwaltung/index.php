<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';

$statusFilter = $_GET['status'] ?? '';
$pdo = ago_sofi_db();

if ($statusFilter !== '') {
    $stmt = $pdo->prepare("SELECT * FROM standorte WHERE status = ? ORDER BY standortname");
    $stmt->execute([$statusFilter]);
} else {
    $stmt = $pdo->query("SELECT * FROM standorte ORDER BY standortname");
}
$standorte = $stmt->fetchAll();

$alleStatus = ['Vorschlag', 'Zu prüfen', 'Vor Ort geprüft', 'Geeignet', 'Eingeschränkt geeignet', 'Ungeeignet', 'Nicht mehr verfügbar'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Standorte verwalten – Admin</title>
    <link rel="stylesheet" href="verwaltung.css">
</head>
<body>
<div class="admin-wrap">
    <div class="admin-header">
        <h1>Standorte verwalten</h1>
        <a href="edit.php" class="btn">+ Neuer Standort</a>
    </div>

    <?php if (isset($_GET['gespeichert'])): ?>
        <p class="erfolg">Standort gespeichert.</p>
    <?php endif; ?>
    <?php if (isset($_GET['geloescht'])): ?>
        <p class="erfolg">Standort gelöscht.</p>
    <?php endif; ?>

    <form method="get" style="margin-bottom: 1rem;">
        <label for="status">Nach Status filtern:</label>
        <select name="status" id="status" onchange="this.form.submit()">
            <option value="">Alle</option>
            <?php foreach ($alleStatus as $status): ?>
                <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
            <?php endforeach; ?>
        </select>
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
                <td><?= htmlspecialchars($s['standortname']) ?></td>
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
</div>
</body>
</html>
