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

## Deploy-Ablauf (nach jeder Änderung)

Vom Mac aus, im Projektverzeichnis:

```bash
./deploy/deploy.sh
```

Das Skript ([`deploy/deploy.sh`](deploy/deploy.sh)) synct den Inhalt von `site/` per
`rsync --delete` auf `/var/www/sofi.agorion.de` — Dateien, die lokal gelöscht wurden,
werden auch auf dem Server entfernt. Es nutzt automatisch den dedizierten Deploy-Key.
