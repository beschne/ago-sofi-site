<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';

$title = 'Alle Standorte – AG Orion Beobachtungsstandorte Sonnenfinsternis';
$activeNav = 'alle';

$pdo = ago_sofi_db();
$stmt = $pdo->query("SELECT * FROM standorte WHERE veroeffentlicht = 1 ORDER BY standortname");
$standorte = $stmt->fetchAll();

require __DIR__ . '/inc/header.php';
?>
            <h1>Alle gemeldeten Standorte</h1>
            <p>
                Übersicht aller gemeldeten Standorte mit aktuellem Prüfstatus — von
                Vorschlag über „noch zu prüfen" bis „ungeeignet". Bereits geprüfte und
                empfohlene Standorte findest du auf der <a href="/">Startseite</a>.
            </p>

            <div id="karte" class="karte"></div>

            <section>
                <h2>Alle gemeldeten Standorte</h2>
                <div class="standorte-liste">
                    <?php if (empty($standorte)): ?>
                        <p>Noch keine Standorte gemeldet.</p>
                    <?php else: ?>
                        <?php foreach ($standorte as $s): ?>
                            <?= standort_zeile($s) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <p>
                Du kennst einen weiteren guten Beobachtungsstandort? Bitte melde ihn dem Vorstand,
                damit er geprüft und gegebenenfalls hier aufgenommen werden kann.
            </p>
<?php
$extraFooter = '<script>initStandorteKarte("karte", "alle");</script>';
require __DIR__ . '/inc/footer.php';
