<?php
$title = 'Sicher beobachten – AG Orion Beobachtungsstandorte Sonnenfinsternis';
$activeNav = 'beobachten';

// Bezugsquellen für zertifizierte SoFi-Brillen. Keine Affiliate-Links (siehe Hinweistext unten).
// ['name' => 'Anbietername', 'url' => 'https://...', 'hinweis' => 'optionaler Zusatz']
$distributoren = [
    ['name' => 'Astroshop.de – Omegon Sonnenfinsternis-Brille „Solar Safe“', 'url' => 'https://www.astroshop.de/sofi-brillen/omegon-sonnenfinsternis-brille-sofi-solar-safe/p%2C86176'],
    ['name' => 'Teleskop-Service – Baader Solar Viewer AstroSolar', 'url' => 'https://www.teleskop-express.de/de/filter-254/sonnenfilter-fuer-weisslicht-61/baader-solar-viewer-astrosolar-brille-sichere-beobachtung-der-sonne-727'],
    ['name' => 'Bresser – Sonnenfinsternis-Brille, einzeln', 'url' => 'https://www.bresser.de/p/bresser-sonnenfinsternis-brille-1-stueck-design-a-4701200'],
    ['name' => 'Bresser – Sonnenfinsternis-Brille, 3 Stück', 'url' => 'https://www.bresser.de/p/bresser-sonnenfinsternis-brille-3-stueck-design-a-4701201'],
    ['name' => 'Bresser – Sonnenfinsternis-Brille, 5 Stück', 'url' => 'https://www.bresser.de/p/bresser-sonnenfinsternis-brille-5-stueck-design-a-4701202'],
    ['name' => 'AstroMedia – Sonnen-Sicht-Brille mit Baader-Filterfolie', 'url' => 'https://astromedia.de/Sonnenfinsternisbrille'],
    ['name' => 'Teleskop-Spezialisten – Baader Solar Viewer AstroSolar', 'url' => 'https://www.teleskop-spezialisten.de/shop/Sonnenbeobachtung/Weisslichtbeobachtung/Filter-fuer-Sonnenbeobachtung/Baader-Planetarium-Solar-Viewer-AstroSolar-Sonnenfinsternis-Brille-Beobachtungsbrille-Silver-Gold-Black-White%3A%3A7560.html'],
];

require __DIR__ . '/inc/header.php';
?>
            <h1>Sicher beobachten</h1>
            <p>
                Auch beim Prüfen und Besuchen möglicher Beobachtungsstandorte gilt: Die Sonne
                niemals ohne geeigneten Schutz direkt oder durch optische Geräte betrachten.
                Verwende ausschließlich zertifizierte SoFi-Brillen (ISO 12312-2) oder geeignete
                Filterfolien für Fernglas und Teleskop.
            </p>
            <ul>
                <li>SoFi-Brille vor Gebrauch auf Kratzer und Beschädigungen prüfen</li>
                <li>Fernglas und Teleskop nur mit passendem Objektivfilter verwenden</li>
                <li>Brille nie ablegen, während optische Geräte auf die Sonne gerichtet sind</li>
                <li>Bei Unsicherheit lieber auf indirekte Beobachtungsmethoden (z. B. Camera obscura) zurückgreifen</li>
            </ul>

            <h2>Bezugsquellen für zertifizierte SoFi-Brillen</h2>
            <p>
                Hier sammeln wir Bezugsquellen, bei denen du zertifizierte SoFi-Brillen
                (ISO 12312-2) beziehen kannst.
            </p>
            <p>
                <strong>Wichtiger Hinweis:</strong> Das sind keine Affiliate- oder Partnerlinks.
                Die AG Orion erzielt durch diese Verlinkung keinerlei Erlöse oder Provisionen —
                die Auswahl ist eine reine Serviceleistung für unsere Mitglieder und Besucher.
            </p>

            <?php if (empty($distributoren)): ?>
                <p><em>Diese Liste befindet sich im Aufbau — schau bald wieder vorbei.</em></p>
            <?php else: ?>
                <ul class="distributoren-liste">
                    <?php foreach ($distributoren as $d): ?>
                        <li>
                            <a href="<?= htmlspecialchars($d['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= htmlspecialchars($d['name']) ?></a>
                            <?php if (!empty($d['hinweis'])): ?> &ndash; <?= htmlspecialchars($d['hinweis']) ?><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
<?php require __DIR__ . '/inc/footer.php';
