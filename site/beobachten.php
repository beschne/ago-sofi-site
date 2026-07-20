<?php
$title = 'Sicher beobachten – AG Orion Beobachtungsstandorte Sonnenfinsternis';
$activeNav = 'beobachten';

// Bezugsquellen für zertifizierte SoFi-Brillen. Keine Affiliate-Links (siehe Hinweistext unten).
// ['name' => 'Anbietername', 'url' => 'https://...', 'hinweis' => 'optionaler Zusatz']
$distributoren = [
    ['name' => 'Astroshop.de - Omegon Sonnenfinsternis-Brille „Solar Safe“', 'url' => 'https://www.astroshop.de/sofi-brillen/omegon-sonnenfinsternis-brille-sofi-solar-safe/p%2C86176'],
    ['name' => 'Teleskop-Service - Baader Solar Viewer AstroSolar', 'url' => 'https://www.teleskop-express.de/de/filter-254/sonnenfilter-fuer-weisslicht-61/baader-solar-viewer-astrosolar-brille-sichere-beobachtung-der-sonne-727'],
    ['name' => 'Bresser - Sonnenfinsternis-Brille, einzeln', 'url' => 'https://www.bresser.de/p/bresser-sonnenfinsternis-brille-1-stueck-design-a-4701200'],
    ['name' => 'Bresser - Sonnenfinsternis-Brille, 3 Stück', 'url' => 'https://www.bresser.de/p/bresser-sonnenfinsternis-brille-3-stueck-design-a-4701201'],
    ['name' => 'Bresser - Sonnenfinsternis-Brille, 5 Stück', 'url' => 'https://www.bresser.de/p/bresser-sonnenfinsternis-brille-5-stueck-design-a-4701202'],
    ['name' => 'AstroMedia - Sonnen-Sicht-Brille mit Baader-Filterfolie', 'url' => 'https://astromedia.de/Sonnenfinsternisbrille'],
    ['name' => 'Teleskop-Spezialisten - Baader Solar Viewer AstroSolar', 'url' => 'https://www.teleskop-spezialisten.de/shop/Sonnenbeobachtung/Weisslichtbeobachtung/Filter-fuer-Sonnenbeobachtung/Baader-Planetarium-Solar-Viewer-AstroSolar-Sonnenfinsternis-Brille-Beobachtungsbrille-Silver-Gold-Black-White%3A%3A7560.html'],
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

            <h2>Beobachtung durch Projektion - ganz ohne Teleskop</h2>
            <p>
                Wer keine SoFi-Brille zur Hand hat oder in der Gruppe beobachten möchte, kann die
                Finsternis auch ganz ohne optisches Gerät und ohne direkten Blick in die Sonne
                verfolgen:
            </p>
            <ul>
                <li><strong>Lochkamera (Camera obscura):</strong> Ein kleines, sauberes Loch in
                    Karton pieksen, Sonnenlicht durchscheinen lassen und das Bild auf ein zweites
                    Blatt Papier im Schatten dahinter projizieren. Je größer der Abstand, desto
                    größer, aber auch lichtschwächer, wird das Bild.</li>
                <li><strong>Guckkasten:</strong> Dieselbe Idee in einem Schuhkarton - Loch auf der
                    einen Seite, Projektionsfläche innen, seitlicher Sichtschlitz zum Reinschauen.
                    Gut für Kinder, da man nie zur Sonne blicken muss.</li>
                <li><strong>Küchensieb:</strong> Ein Sieb hochhalten - jedes kleine Loch projiziert
                    ein eigenes Sichelbild auf den Boden. Einfach, wirkt aber verblüffend gut.</li>
                <li><strong>Blätterschatten:</strong> Unter einem Baum wirken die Lücken zwischen
                    den Blättern wie natürliche Lochkameras; am Boden erscheinen dann lauter kleine
                    Sichelbilder - ganz ohne Hilfsmittel.</li>
                <li><strong>Fernglas-Projektion:</strong> Ein Fernglas (eine Linsenhälfte abgedeckt)
                    auf die Sonne richten und das Bild aus dem Okular auf eine weiße Pappe
                    projizieren - nicht selbst hindurchschauen. Nicht zu lange in eine Richtung
                    halten, da sich die Optik durch die Sonnenhitze erwärmen kann.</li>
            </ul>

            <h2>Bezugsquellen für zertifizierte SoFi-Brillen</h2>
            <p>
                Hier sammeln wir Bezugsquellen, bei denen du zertifizierte SoFi-Brillen
                (ISO 12312-2) beziehen kannst.
            </p>
            <p>
                <strong>Wichtiger Hinweis:</strong> Das sind keine Affiliate- oder Partnerlinks.
                Die AG Orion erzielt durch diese Verlinkung keinerlei Erlöse oder Provisionen -
                die Auswahl ist eine reine Serviceleistung für unsere Mitglieder und Besucher.
            </p>

            <?php if (empty($distributoren)): ?>
                <p><em>Diese Liste befindet sich im Aufbau - schau bald wieder vorbei.</em></p>
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

            <h2>Tipps für die Astrofotografie</h2>
            <p>
                Wer die Finsternis nicht nur beobachten, sondern auch fotografieren möchte, braucht
                für Kamera, Fernglas oder Teleskop einen eigenen, zertifizierten Sonnenfilter
                (z. B. eine AstroSolar-Folie) direkt vor der Frontlinse bzw. Objektivöffnung. Eine
                <strong>SoFi-Brille ist dafür nicht geeignet</strong>: Sie ist nur für den direkten
                Blick mit dem bloßen Auge zertifiziert, zu klein für die meisten Objektivdurchmesser
                und lässt sich nicht sicher und lichtdicht vor einer Optik befestigen.
            </p>
            <p>
                <strong>Wichtig:</strong> Da es sich am 12. August um eine rein partielle Finsternis
                handelt (maximal 88 % Bedeckung, nie totale Verdunkelung), bleibt der Filter die
                gesamte Zeit über vor der Optik - auch im Maximum. Anders als bei einer totalen
                Sonnenfinsternis, bei der während der kurzen Totalitätsphase ohne Filter fotografiert
                werden darf, gibt es hier keinen sicheren, filterfreien Moment.
            </p>
            <p>
                Zur Brennweite: Für ein einigermaßen großes Sonnenscheibchen im Bild gilt grob die
                Faustformel Brennweite (mm) ÷ 109 ≈ Bilddurchmesser (mm) auf dem Sensor. An
                <strong>Vollformatkameras</strong> (36 × 24 mm) empfehlen sich deshalb mindestens
                500-600 mm, für eine bildfüllende Sonne eher 1000-2000 mm (z. B. mit Telekonverter).
                An <strong>APS-C-Kameras</strong> (Cropfaktor ca. 1,5) reichen dank des kleineren
                Sensors bereits rund 400-600 mm für eine vergleichbare Bildwirkung.
                <strong>Smartphones</strong> haben naturgemäß viel zu kurze Brennweiten - die Sonne
                bleibt ein winziger Punkt. Praktikabler ist hier die afokale Projektion: das Handy
                mit einer passenden Halterung vor das Okular eines gefilterten Fernglases oder
                Teleskops setzen.
            </p>
<?php require __DIR__ . '/inc/footer.php';
