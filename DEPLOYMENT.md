# Deployment

Die Website wird **direkt vom lokalen Mac aus per rsync** auf den Server ausgerollt.
Es liegt kein Git-Checkout auf dem Server — nur der Inhalt von `site/` landet dort,
unter `/var/www/sofi.agorion.de`.

* Server: `92.205.236.81` (reverse DNS: `81.236.205.92.host.secureserver.net`)
* SSH-User: `cerberus`
* Zielverzeichnis: `/var/www/sofi.agorion.de` (entspricht 1:1 dem Inhalt von `site/`)
* nginx-`root` zeigt direkt auf dieses Verzeichnis

## Einmalige Einrichtung (bereits erledigt)

### 1. Dedizierter SSH-Key für den Deploy-Zugriff

Lokal auf dem Mac erzeugt (nicht der persönliche Key):

```bash
ssh-keygen -t ed25519 -f ~/.ssh/ago-sofi-deploy -N "" -C "deploy@sofi.agorion.de"
```

Der öffentliche Schlüssel liegt in `~/.ssh/ago-sofi-deploy.pub` auf dem Mac und ist als
einziger Eintrag in `~/.ssh/authorized_keys` von `cerberus` auf dem Server hinterlegt.

### 2. Zielverzeichnis auf dem Server

```bash
sudo mkdir -p /var/www/sofi.agorion.de
sudo chown cerberus:cerberus /var/www/sofi.agorion.de
sudo chmod 755 /var/www/sofi.agorion.de
```

### 3. nginx-Konfiguration

Die aktive Konfiguration liegt im Projekt unter
[`deploy/nginx/sofi.agorion.de.conf`](deploy/nginx/sofi.agorion.de.conf) und ist 1:1 die
Datei, die auf dem Server unter `/etc/nginx/sites-available/sofi.agorion.de` liegt
(inkl. der von Certbot ergänzten TLS-/Redirect-Blöcke).

**Wichtig:** Wird die Konfiguration auf dem Server geändert (z. B. durch eine
Certbot-Zertifikatserneuerung mit Anpassungen, oder manuell), muss diese Datei im Projekt
entsprechend nachgezogen werden — und umgekehrt: Änderungen hier werden per

```bash
scp deploy/nginx/sofi.agorion.de.conf cerberus@92.205.236.81:/tmp/sofi.agorion.de.conf
ssh cerberus@92.205.236.81 'sudo mv /tmp/sofi.agorion.de.conf /etc/nginx/sites-available/sofi.agorion.de && sudo chown root:root /etc/nginx/sites-available/sofi.agorion.de && sudo nginx -t && sudo systemctl reload nginx'
```

auf den Server übertragen. Aktivierung erfolgte einmalig über:

```bash
ssh cerberus@92.205.236.81 'sudo ln -sf /etc/nginx/sites-available/sofi.agorion.de /etc/nginx/sites-enabled/sofi.agorion.de'
```

### 4. TLS-Zertifikat (Let's Encrypt / Certbot)

Einmalig eingerichtet, analog zu den anderen Subdomains auf diesem Server:

```bash
ssh cerberus@92.205.236.81 'sudo certbot --nginx -d sofi.agorion.de --non-interactive --agree-tos -m benno.schneider@gmail.com --redirect'
```

Certbot erneuert das Zertifikat automatisch per systemd-Timer im Hintergrund.

### 5. MySQL/MariaDB (`ago_sofi`)

Seit der Umstellung von Airtable auf ein eigenes Backend läuft eine eigene Datenbank auf
demselben MariaDB-Server, der auch eine andere WordPress-Seite auf diesem Server bedient:

```bash
ssh cerberus@92.205.236.81 "sudo mysql -e \"CREATE DATABASE IF NOT EXISTS ago_sofi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS 'ago_sofi'@'localhost' IDENTIFIED BY '<passwort>'; GRANT ALL PRIVILEGES ON ago_sofi.* TO 'ago_sofi'@'localhost'; FLUSH PRIVILEGES;\""
scp db/schema.sql db/seed-migration.sql cerberus@92.205.236.81:/tmp/
ssh cerberus@92.205.236.81 'sudo mysql ago_sofi < /tmp/schema.sql && sudo mysql ago_sofi < /tmp/seed-migration.sql && rm /tmp/schema.sql /tmp/seed-migration.sql'
```

Zugangsdaten liegen **außerhalb** des Webroots und außerhalb des Git-Repos unter
`/var/www/sofi.agorion.de-secrets/db-config.php` (Format siehe `site/inc/db.php`), Besitzer
`www-data`, Modus `640` — nie committen, nie per rsync deployen.

### 6. Upload-Verzeichnis für Fotos

```bash
ssh cerberus@92.205.236.81 'sudo mkdir -p /var/www/sofi.agorion.de/uploads && sudo chown www-data:www-data /var/www/sofi.agorion.de/uploads && sudo chmod 755 /var/www/sofi.agorion.de/uploads'
```

Wichtig: `www-data` (nicht `cerberus`), da PHP-FPM als `www-data` läuft und dorthin
schreiben muss. `deploy.sh` schließt `uploads/` per `--exclude` von der Synchronisation aus
(siehe unten) — das Verzeichnis muss also einmalig manuell angelegt werden und bleibt danach
unabhängig vom Deploy-Zyklus.

### 7. Verwaltungs-Zugangsschutz (HTTP Basic Auth)

`/verwaltung/` ist über nginx `auth_basic` geschützt (nicht `.htaccess` — der Server nutzt nginx,
das liest keine `.htaccess`-Dateien). Neuen Nutzer anlegen/Passwort ändern:

```bash
ssh cerberus@92.205.236.81 'HASH=$(openssl passwd -apr1 "<neues-passwort>"); echo "<benutzername>:$HASH" | sudo tee /etc/nginx/.htpasswd-sofi-admin > /dev/null && sudo chmod 640 /etc/nginx/.htpasswd-sofi-admin && sudo chown root:www-data /etc/nginx/.htpasswd-sofi-admin'
```

Für mehrere Zeilen (mehrere Nutzer) die Datei entsprechend mit mehreren `benutzer:hash`-Zeilen
befüllen. Die Datei liegt außerhalb des Webroots, ist nicht Teil des Deploys.

## Deploy-Ablauf (nach jeder Änderung)

Vom Mac aus, im Projektverzeichnis:

```bash
./deploy/deploy.sh
```

Das Skript ([`deploy/deploy.sh`](deploy/deploy.sh)) synct den Inhalt von `site/` per
`rsync --delete` auf `/var/www/sofi.agorion.de` — Dateien, die lokal gelöscht wurden,
werden auch auf dem Server entfernt. `site/uploads/` ist davon **ausgeschlossen** (dort
liegen die über das Verwaltungsformular hochgeladenen Fotos, die nur auf dem Server existieren).
Es nutzt automatisch den dedizierten Deploy-Key.

## Lokale Entwicklung gegen die echte Datenbank

Für PHP-Änderungen lokal testen, ohne eine eigene MySQL-Installation zu brauchen:

```bash
ssh -f -N -L 13306:127.0.0.1:3306 -i ~/.ssh/ago-sofi-deploy cerberus@92.205.236.81
```

Dann `site/inc/db.php` per Umgebungsvariable `AGO_SOFI_DB_CONFIG` auf eine lokale Config
zeigen lassen, die denselben Host/User/Passwort wie die Server-Config nutzt, aber
`127.0.0.1:13306` als Host angibt. Die eigentlichen Zugangsdaten dafür **nicht lokal
speichern** — stattdessen bevorzugt direkt auf dem Server mit `php -S` (als `www-data` via
`sudo -u www-data`) gegen die echte Config testen und nur die HTTP-Antworten per SSH-Tunnel
lokal ansehen; so verlässt das DB-Passwort den Server nie.
