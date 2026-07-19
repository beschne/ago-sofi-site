<?php
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Nur POST erlaubt.');
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    die('Fehlende ID.');
}

$pdo = ago_sofi_db();

$stmt = $pdo->prepare('SELECT dateiname FROM standort_fotos WHERE standort_id = ?');
$stmt->execute([$id]);
foreach ($stmt->fetchAll() as $foto) {
    @unlink(ago_sofi_uploads_dir() . '/' . $foto['dateiname']);
}

$stmt = $pdo->prepare('DELETE FROM standorte WHERE id = ?');
$stmt->execute([$id]);

header('Location: index.php?geloescht=1');
exit;
