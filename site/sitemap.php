<?php
// Dynamisch erzeugte Sitemap, erreichbar unter /sitemap.xml (siehe nginx-Rewrite).
// Enthält alle veröffentlichten Standorte (veroeffentlicht=1, unabhängig vom Status) plus die
// statischen Inhaltsseiten. Bleibt für die Standorte automatisch aktuell, da live aus der DB
// erzeugt -- WICHTIG: die Liste $statischeSeiten unten ist die einzige Stelle, die von Hand
// gepflegt werden muss. Wird eine neue statische Seite ergänzt, muss sie hier nachgetragen
// werden, sonst taucht sie nicht in der Sitemap auf.
require __DIR__ . '/inc/db.php';

$statischeSeiten = [
    ['pfad' => '/', 'prioritaet' => '1.0'],
    ['pfad' => '/alle-standorte', 'prioritaet' => '0.8'],
    ['pfad' => '/sonnenfinsternis', 'prioritaet' => '0.8'],
    ['pfad' => '/beobachten', 'prioritaet' => '0.6'],
    ['pfad' => '/impressum-datenschutz', 'prioritaet' => '0.1'],
];

$pdo = ago_sofi_db();
$standorte = $pdo->query(
    "SELECT slug, updated_at FROM standorte WHERE veroeffentlicht = 1 ORDER BY id"
)->fetchAll();

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($statischeSeiten as $seite): ?>
    <url>
        <loc>https://sofi.agorion.de<?= htmlspecialchars($seite['pfad']) ?></loc>
        <priority><?= $seite['prioritaet'] ?></priority>
    </url>
    <?php endforeach; ?>
    <?php foreach ($standorte as $s): ?>
    <url>
        <loc>https://sofi.agorion.de/standort/<?= htmlspecialchars($s['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($s['updated_at'])) ?></lastmod>
        <priority>0.5</priority>
    </url>
    <?php endforeach; ?>
</urlset>
