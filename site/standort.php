<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';

$slug = $_GET['slug'] ?? '';

$pdo = ago_sofi_db();
$stmt = $pdo->prepare("SELECT * FROM standorte WHERE slug = ?");
$stmt->execute([$slug]);
$s = $stmt->fetch();

if (!$s) {
    http_response_code(404);
    $title = 'Standort nicht gefunden – AG Orion';
    $activeNav = '';
    require __DIR__ . '/inc/header.php';
    echo '<h1>Standort nicht gefunden</h1><p>Diesen Standort gibt es nicht (mehr). Zurück zur <a href="/">Startseite</a>.</p>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

$fotosStmt = $pdo->prepare("SELECT * FROM standort_fotos WHERE standort_id = ? ORDER BY kategorie, sortierung");
$fotosStmt->execute([$s['id']]);
$fotos = $fotosStmt->fetchAll();

$fotosNachKategorie = [];
foreach ($fotos as $foto) {
    $fotosNachKategorie[$foto['kategorie']][] = $foto;
}

$kategorieLabel = [
    'horizontfoto' => 'Horizontfoto',
    'panorama' => 'Panorama',
    'horizontgrafik' => 'Horizontgrafik',
    'weiteres' => 'Weitere Fotos',
];

$title = htmlspecialchars($s['standortname']) . ' – AG Orion Beobachtungsstandorte';
$activeNav = '';

require __DIR__ . '/inc/header.php';
?>
            <h1><?= htmlspecialchars($s['standortname']) ?></h1>
            <p><?= status_badge($s['status']) ?></p>

            <?php if ($s['kurzbeschreibung']): ?>
                <p><?= nl2br(htmlspecialchars($s['kurzbeschreibung'])) ?></p>
            <?php endif; ?>

            <div id="karte" class="karte" data-lat="<?= htmlspecialchars($s['breitengrad']) ?>" data-lon="<?= htmlspecialchars($s['laengengrad']) ?>"></div>

            <section>
                <h2>Details</h2>
                <table class="standort-details-tabelle">
                    <?php
                    $zeilen = [
                        'Region' => $s['region'],
                        'Zugänglichkeit' => $s['zugaenglichkeit'],
                        'Parkplatz' => $s['parkplatz'],
                        'Erwarteter Andrang' => $s['andrang_erwartet'],
                        'Sicherheitsrisiken' => $s['sicherheitsrisiken'],
                        'Entfernung ab Bad Homburg' => $s['entfernung_bad_homburg_km'] !== null ? number_format((float) $s['entfernung_bad_homburg_km'], 1, ',', '.') . ' km' : null,
                        'Fahrzeit ab Bad Homburg' => $s['fahrzeit_minuten'] !== null ? $s['fahrzeit_minuten'] . ' min' : null,
                        'Höhe' => $s['hoehe_meter'] !== null ? $s['hoehe_meter'] . ' m' . ($s['hoehe_vom_turm'] ? ' (vom Turm)' : '') : null,
                        'Horizontbewertung' => $s['horizontbewertung'] !== null ? bewertung_sonnen((int) $s['horizontbewertung']) : null,
                        'Gesamtbewertung' => $s['gesamtbewertung'] !== null ? bewertung_sonnen((int) $s['gesamtbewertung']) : null,
                    ];
                    foreach ($zeilen as $label => $wert):
                        if ($wert === null || $wert === '') continue;
                    ?>
                        <tr>
                            <th><?= htmlspecialchars($label) ?></th>
                            <td><?= htmlspecialchars((string) $wert) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php if ($s['kurze_bewertung']): ?>
                    <p><strong>Kurze Einschätzung:</strong> <?= nl2br(htmlspecialchars($s['kurze_bewertung'])) ?></p>
                <?php endif; ?>

                <?php if ($s['kartenlink']): ?>
                    <p><a href="<?= htmlspecialchars($s['kartenlink']) ?>" target="_blank" rel="noopener">Standort auf OpenStreetMap öffnen</a></p>
                <?php endif; ?>
            </section>

            <?php if (!empty($fotosNachKategorie)): ?>
                <section>
                    <h2>Fotos &amp; Grafiken</h2>
                    <?php foreach ($fotosNachKategorie as $kategorie => $liste): ?>
                        <h3><?= htmlspecialchars($kategorieLabel[$kategorie] ?? $kategorie) ?></h3>
                        <div class="foto-galerie">
                            <?php foreach ($liste as $foto): ?>
                                <figure>
                                    <a href="/uploads/<?= htmlspecialchars($foto['dateiname']) ?>" target="_blank" rel="noopener">
                                        <img src="/uploads/<?= htmlspecialchars($foto['dateiname']) ?>" loading="lazy" alt="<?= htmlspecialchars($kategorieLabel[$kategorie] ?? $kategorie) ?> – <?= htmlspecialchars($s['standortname']) ?>">
                                    </a>
                                    <?php $credit = foto_credit($foto); ?>
                                    <?php if ($credit !== ''): ?>
                                        <figcaption><?= $credit ?></figcaption>
                                    <?php endif; ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <p><a href="/alle-standorte.php">&larr; Zurück zur Übersicht aller Standorte</a></p>
<?php
$extraFooter = '<script>
    (function () {
        var karte = L.map("karte").setView([' . (float) $s['breitengrad'] . ', ' . (float) $s['laengengrad'] . '], 14);
        L.tileLayer("https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png", {
            maxZoom: 17,
            attribution: "Kartendaten: © OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: © OpenTopoMap (CC-BY-SA)"
        }).addTo(karte);
        L.marker([' . (float) $s['breitengrad'] . ', ' . (float) $s['laengengrad'] . ']).addTo(karte);
    })();
</script>';
require __DIR__ . '/inc/footer.php';
