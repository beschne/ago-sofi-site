<?php
// Einfacher CSRF-Schutz für die Admin-Formulare (Basic-Auth via nginx schützt den
// Zugriff auf /verwaltung/ generell, das hier schützt zusätzlich gegen Cross-Site-Requests).

function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $submitted = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(403);
        die('Ungültiges Formular (CSRF-Token fehlt oder abgelaufen). Bitte Seite neu laden und erneut versuchen.');
    }
}
