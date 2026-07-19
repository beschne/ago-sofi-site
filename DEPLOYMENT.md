# Deployment

Die Website wird über dieses Git-Repo auf den Server ausgerollt. Nur der Inhalt von
`site/` wird tatsächlich ausgeliefert — `README.md`, `CLAUDE.md`, `DEPLOYMENT.md` etc.
liegen zwar mit im Repo-Checkout auf dem Server, sind aber über nginx nicht erreichbar.

## Einmalige Einrichtung auf dem Server

1. Repo klonen, z. B. nach `/var/www/sofi.agorion.de`:

   ```bash
   git clone <remote-url> /var/www/sofi.agorion.de
   ```

2. nginx `root` auf das `site/`-Unterverzeichnis zeigen lassen:

   ```nginx
   server {
       server_name sofi.agorion.de;
       root /var/www/sofi.agorion.de/site;
       index index.html;
   }
   ```

3. nginx-Konfiguration testen und neu laden:

   ```bash
   nginx -t && systemctl reload nginx
   ```

## Deploy-Ablauf (nach jedem Push)

Auf dem Server im Repo-Verzeichnis:

```bash
cd /var/www/sofi.agorion.de
git pull
```

Da `root` bereits auf `site/` zeigt, sind Änderungen sofort live — kein zusätzlicher
Kopier-Schritt nötig. Kein `systemctl reload` erforderlich, da sich nur statische
Dateien ändern.

## Später: alternative Deploy-Variante

Statt `git pull` auf dem Server kann später auch lokal per `rsync`/`scp` nur der
Inhalt von `site/` auf den Server kopiert werden (kein `.git`, keine Doku-Dateien
auf dem Server). Diese Variante ist noch nicht eingerichtet.
