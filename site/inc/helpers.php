<?php
// Gemeinsame Anzeige-Helfer für die Standort-Listen (index.php, alle-standorte.php).

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

function standort_zeile(array $s): string {
    $name = htmlspecialchars($s['standortname']);
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
            <span class="standort-name">{$name}</span>
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
    return implode(' &middot; ', $teile);
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
