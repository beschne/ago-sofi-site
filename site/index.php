<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';

$title = 'AG Orion – Beobachtungsstandorte Sonnenfinsternis 12. August 2026';
$activeNav = 'geprueft';

$pdo = ago_sofi_db();
$stmt = $pdo->prepare(
    "SELECT * FROM standorte
     WHERE veroeffentlicht = 1 AND status IN ('Geeignet', 'Eingeschränkt geeignet', 'Vor Ort geprüft')
     ORDER BY standortname"
);
$stmt->execute();
$standorte = $stmt->fetchAll();

require __DIR__ . '/inc/header.php';
?>
            <h1>Beobachtungsstandorte für die Sonnenfinsternis am 12. August 2026</h1>
            <p>
                Als Vorbereitung auf die partielle Sonnenfinsternis am 12. August 2026 sammelt und
                prüft die AG Orion mögliche Beobachtungsstandorte. Hier findest du die bereits
                geprüften und empfohlenen Standorte.
            </p>

            <div id="karte" class="karte"></div>

            <section>
                <h2>Geprüfte Standorte</h2>
                <p>
                    Standorte, die bereits geprüft und für die Beobachtung empfohlen wurden
                    (ggf. mit Einschränkungen).
                </p>
                <div class="standorte-liste">
                    <?php if (empty($standorte)): ?>
                        <p>Aktuell sind noch keine Standorte veröffentlicht.</p>
                    <?php else: ?>
                        <?php foreach ($standorte as $s): ?>
                            <?= standort_zeile($s) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <p>
                Du kennst einen weiteren guten Beobachtungsstandort? Bitte melde ihn dem Vorstand,
                damit er geprüft und gegebenenfalls hier aufgenommen werden kann. Eine Übersicht
                aller gemeldeten Standorte inkl. Prüfstatus findest du unter
                <a href="/alle-standorte.php">Alle Standorte</a>.
            </p>
<?php
$extraFooter = '<script>initStandorteKarte("karte", "geprueft");</script>';
require __DIR__ . '/inc/footer.php';
