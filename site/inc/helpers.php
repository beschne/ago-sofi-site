<?php
// Gemeinsame Anzeige-Helfer für die Standort-Listen (index.php, alle-standorte.php).

// SQL-Fragment für ORDER BY: sortiert Standorte nach Eignung (beste zuerst).
const STATUS_SORT_SQL = "FIELD(status, 'Geeignet', 'Vor Ort geprüft', 'Eingeschränkt geeignet', 'Zu prüfen', 'Vorschlag', 'Ungeeignet', 'Nicht mehr verfügbar')";

const STATUS_BADGE_KLASSEN = [
    'Vorschlag' => 'badge-vorschlag',
    'Zu prüfen' => 'badge-pruefen',
    'Vor Ort geprüft' => 'badge-vorort',
    'Geeignet' => 'badge-geeignet',
    'Eingeschränkt geeignet' => 'badge-eingeschraenkt',
    'Ungeeignet' => 'badge-ungeeignet',
    'Nicht mehr verfügbar' => 'badge-nichtmehr',
];

function status_badge(string $status): string {
    $klasse = STATUS_BADGE_KLASSEN[$status] ?? 'badge-vorschlag';
    return '<span class="status-badge ' . $klasse . '">' . htmlspecialchars($status) . '</span>';
}

// Fernglas-Symbol: zeigt an, dass die AGO den Standort persönlich besichtigt hat
// (zuletzt_vor_ort_geprueft gesetzt) — unabhängig vom aktuellen status, bewusst
// kein Häkchen, damit es nicht als "geeignet" missverstanden wird.
function vor_ort_marker(?string $zuletztVorOrtGeprueft): string {
    if ($zuletztVorOrtGeprueft === null) {
        return '';
    }
    $datum = htmlspecialchars(date('d.m.Y', strtotime($zuletztVorOrtGeprueft)));
    return '<span class="vor-ort-marker" title="Vor Ort geprüft am ' . $datum . '">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . '<circle cx="7" cy="16" r="3.2"></circle><circle cx="17" cy="16" r="3.2"></circle>'
        . '<path d="M9.8 14.5 L8.5 8 h3l1 3M14.2 14.5 L15.5 8h-3l-1 3"></path><path d="M11.5 8h1"></path>'
        . '</svg></span>';
}

const ANDRANG_BADGE_KLASSEN = [
    'Sehr gering' => 'andrang-sehr-gering',
    'Gering' => 'andrang-gering',
    'Mittel' => 'andrang-mittel',
    'Hoch' => 'andrang-hoch',
    'Sehr hoch' => 'andrang-sehr-hoch',
];

// Nur für bekannte Andrang-Werte ein Tag liefern (null bei "Unbekannt"/leer).
function andrang_badge(?string $andrang): ?string {
    if ($andrang === null || !isset(ANDRANG_BADGE_KLASSEN[$andrang])) {
        return null;
    }
    $klasse = ANDRANG_BADGE_KLASSEN[$andrang];
    return '<span class="status-badge ' . $klasse . '">Andrang ' . htmlspecialchars(mb_strtolower($andrang, 'UTF-8')) . '</span>';
}

// Bewertungen (Horizont-, Gesamtbewertung) als Sonnen-Skala 0–5 darstellen.
function bewertung_sonnen(?int $wert, int $max = 5): ?string {
    if ($wert === null) {
        return null;
    }
    $wert = max(0, min($max, $wert));
    return str_repeat('☀', $wert) . str_repeat('○', $max - $wert);
}

// Formatiert einen Grad-Wert mit ° und Himmelsrichtung statt Vorzeichen (z. B. "50.234080°N").
function koordinate_grad(float $wert, string $positiv, string $negativ): string {
    return number_format(abs($wert), 6, '.', '') . '° ' . ($wert < 0 ? $negativ : $positiv);
}

function standort_zeile(array $s): string {
    $name = htmlspecialchars($s['standortname']);
    $marker = vor_ort_marker($s['zuletzt_vor_ort_geprueft'] ?? null);
    $badge = status_badge($s['status']);
    $andrangBadge = andrang_badge($s['andrang_erwartet'] ?? null) ?? '';
    $url = '/standort/' . htmlspecialchars($s['slug']);

    $fakten = [];
    if ($s['entfernung_bad_homburg_km'] !== null) {
        $fakten[] = number_format((float) $s['entfernung_bad_homburg_km'], 1, ',', '.') . ' km / ' .
            ($s['fahrzeit_minuten'] !== null ? $s['fahrzeit_minuten'] . ' min' : '?') . ' ab Bad Homburg';
    }
    if ($s['zugaenglichkeit']) {
        $fakten[] = htmlspecialchars($s['zugaenglichkeit']);
    }
    if ($s['parkplatz']) {
        $fakten[] = 'Parkplatz: ' . htmlspecialchars($s['parkplatz']);
    }
    $faktenHtml = $fakten ? '<p class="standort-fakten">' . implode(' &middot; ', $fakten) . '</p>' : '';

    $kurz = $s['kurzbeschreibung'] ? '<p>' . nl2br(htmlspecialchars($s['kurzbeschreibung'])) . '</p>' : '';

    return <<<HTML
    <details class="standort-zeile">
        <summary>
            <span>{$marker}<span class="standort-name">{$name}</span></span>
            {$badge}
            {$andrangBadge}
        </summary>
        <div class="standort-details">
            {$kurz}
            {$faktenHtml}
            <p><a href="{$url}">Alle Details ansehen &rarr;</a></p>
        </div>
    </details>
    HTML;
}

function foto_credit(array $foto): string {
    $teile = [];
    if (!empty($foto['autor_quelle'])) {
        $teile[] = htmlspecialchars($foto['autor_quelle']);
    }
    if (!empty($foto['lizenz'])) {
        $teile[] = htmlspecialchars($foto['lizenz']);
    }
    if (!empty($foto['aufnahme_zeitpunkt'])) {
        $teile[] = date('d.m.Y', strtotime($foto['aufnahme_zeitpunkt']));
    }
    if ($link = foto_luftbild_link($foto)) {
        $teile[] = '<a href="' . htmlspecialchars($link) . '" target="_blank" rel="noopener">Luftbild von Google Maps</a>';
    }
    return implode(' &middot; ', $teile);
}

function foto_luftbild_link(array $foto): ?string {
    if (!isset($foto['gps_breitengrad'], $foto['gps_laengengrad'])
        || $foto['gps_breitengrad'] === null || $foto['gps_laengengrad'] === null
        || $foto['gps_breitengrad'] === '' || $foto['gps_laengengrad'] === ''
    ) {
        return null;
    }
    $lat = (float) $foto['gps_breitengrad'];
    $lon = (float) $foto['gps_laengengrad'];
    // Einfacher Google-Maps-Link (kein API-Key nötig, da nur verlinkt statt eingebettet),
    // t=k zeigt Satelliten-/Luftbild statt der Straßenkarte.
    return "https://www.google.com/maps?q={$lat},{$lon}&t=k";
}

function slugify(string $text): string {
    $ersetzungen = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue'];
    $text = strtr($text, $ersetzungen);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return preg_replace('/-+/', '-', $text) ?: 'standort';
}

function eindeutiger_slug(PDO $pdo, string $basisSlug, ?int $ausgenommenId = null): string {
    $slug = $basisSlug;
    $zaehler = 2;
    while (true) {
        if ($ausgenommenId !== null) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM standorte WHERE slug = ? AND id != ?');
            $stmt->execute([$slug, $ausgenommenId]);
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM standorte WHERE slug = ?');
            $stmt->execute([$slug]);
        }
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $basisSlug . '-' . $zaehler;
        $zaehler++;
    }
}
