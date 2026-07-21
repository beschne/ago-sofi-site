<?php
$title = 'Seite nicht gefunden – AG Orion Sonnenfinsternis 2026';
$activeNav = '';

require __DIR__ . '/inc/header.php';
?>
            <div class="error-404">
                <svg class="error-404-grafik" viewBox="0 0 130 130" width="120" height="120" role="img" aria-label="Sonne, teilweise vom Mond verdeckt">
                    <circle cx="55" cy="75" r="40" fill="#f4c430"/>
                    <circle cx="77" cy="55" r="40" fill="#141a35"/>
                </svg>
                <h1>Hier ist wohl der Mond davor.</h1>
                <p>Diese Seite gibt es nicht (mehr) &ndash; Fehler 404.</p>
                <p><a href="/">Zur&uuml;ck zur Startseite</a></p>
            </div>
<?php
require __DIR__ . '/inc/footer.php';
