<?php
/**
 * Gemeinsamer Seitenkopf. Erwartet vor dem include gesetzt:
 * $title (string), $activeNav ("geprueft"|"alle"|"sonnenfinsternis"|"beobachten"), optional $extraHead (string, wird in <head> ausgegeben).
 */
function ago_sofi_nav_class(string $item, string $activeNav): string {
    return $item === $activeNav ? ' class="current" aria-current="page"' : '';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/css/style.css?v=<?= @filemtime(__DIR__ . '/../css/style.css') ?: '1' ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" href="/img/favicon-32x32.png" sizes="32x32">
    <link rel="icon" href="/img/favicon-192x192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/img/apple-touch-icon-180x180.png">
    <?= $extraHead ?? '' ?>
</head>
<body>
    <div id="page">
        <header id="branding">
            <h1 id="site-title"><a href="/">AG Orion – Sonnenfinsternis 2026</a></h1>
            <p id="site-description">Beobachtungsstandorte für die partielle Sonnenfinsternis am 12. August 2026</p>
            <a href="/" class="branding-image">
                <!-- Test: SoFi-Banner statt Original-Logo (agorion.de). Original liegt
                     unverändert unter /img/banner-original-{300,768,940}.jpg, siehe unten. -->
                <?php $bannerV = @filemtime(__DIR__ . '/../img/banner-940.jpg') ?: '1'; ?>
                <img src="/img/banner-940.jpg?v=<?= $bannerV ?>"
                     srcset="/img/banner-300.jpg?v=<?= $bannerV ?> 300w,
                             /img/banner-768.jpg?v=<?= $bannerV ?> 768w,
                             /img/banner-940.jpg?v=<?= $bannerV ?> 940w"
                     sizes="(max-width: 940px) 100vw, 940px"
                     width="940" height="198" alt="Astronomische Gesellschaft Orion" loading="lazy">
                <!--
                <img src="https://www.agorion.de/wp-content/uploads/2016/07/milky-way-logo.jpg"
                     srcset="https://www.agorion.de/wp-content/uploads/2016/07/milky-way-logo-300x63.jpg 300w,
                             https://www.agorion.de/wp-content/uploads/2016/07/milky-way-logo-768x162.jpg 768w,
                             https://www.agorion.de/wp-content/uploads/2016/07/milky-way-logo.jpg 940w"
                     sizes="(max-width: 940px) 100vw, 940px"
                     width="940" height="198" alt="Astronomische Gesellschaft Orion" loading="lazy">
                -->
            </a>
            <nav id="access">
                <div>
                    <ul>
                        <li><a href="/"<?= ago_sofi_nav_class('geprueft', $activeNav) ?>>Empfohlene Standorte</a></li>
                        <li><a href="/alle-standorte"<?= ago_sofi_nav_class('alle', $activeNav) ?>>Alle Standorte</a></li>
                        <li><a href="/sonnenfinsternis"<?= ago_sofi_nav_class('sonnenfinsternis', $activeNav) ?>>Die Sonnenfinsternis</a></li>
                        <li><a href="/beobachten"<?= ago_sofi_nav_class('beobachten', $activeNav) ?>>Sicher beobachten</a></li>
                    </ul>
                </div>
            </nav>
        </header>

        <main>
