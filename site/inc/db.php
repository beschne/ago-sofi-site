<?php
// PDO-Verbindung zur ago_sofi-Datenbank.
// Zugangsdaten liegen bewusst außerhalb des Webroots und außerhalb des Git-Repos
// (siehe PLAN-mysql-migration.md) und werden hier über einen absoluten Pfad eingebunden.

function ago_sofi_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Lokale Entwicklung kann den Pfad per Umgebungsvariable überschreiben
    // (z. B. auf eine Config zeigen, die einen SSH-Tunnel zur echten DB nutzt).
    $configPath = getenv('AGO_SOFI_DB_CONFIG') ?: '/var/www/sofi.agorion.de-secrets/db-config.php';
    if (!is_file($configPath)) {
        throw new RuntimeException("DB-Konfiguration nicht gefunden: $configPath");
    }
    $config = require $configPath;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['dbname'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
