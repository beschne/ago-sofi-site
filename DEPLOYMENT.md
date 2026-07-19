# Deployment

Die Website wird über das private GitHub-Repo `beschne/ago-sofi-site` auf den Server
ausgerollt. Nur der Inhalt von `site/` wird tatsächlich ausgeliefert — `README.md`,
`CLAUDE.md`, `DEPLOYMENT.md` etc. liegen zwar mit im Repo-Checkout auf dem Server,
sind aber über nginx nicht erreichbar, da `root` gezielt auf `site/` zeigt.

## Einmalige Einrichtung auf dem Server

### 1. Deploy-Key erzeugen (Repo ist privat, braucht eigenen Lesezugriff)

Auf dem Server einen dedizierten, unbeschützten SSH-Key nur für dieses Repo anlegen:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/ago-sofi-site-deploy -N "" -C "deploy@sofi.agorion.de"
```

Den öffentlichen Schlüssel ausgeben und kopieren:

```bash
cat ~/.ssh/ago-sofi-site-deploy.pub
```

Dann in GitHub unter **github.com/beschne/ago-sofi-site → Settings → Deploy keys →
Add deploy key** einfügen. "Allow write access" **nicht** aktivieren (Read-only reicht
zum Pullen).

### 2. SSH so konfigurieren, dass `git clone`/`pull` diesen Key benutzt

In `~/.ssh/config` auf dem Server:

```
Host github.com-ago-sofi-site
    HostName github.com
    User git
    IdentityFile ~/.ssh/ago-sofi-site-deploy
    IdentitiesOnly yes
```

### 3. Repo klonen

```bash
git clone git@github.com-ago-sofi-site:beschne/ago-sofi-site.git /var/www/sofi.agorion.de
```

### 4. nginx `root` auf das `site/`-Unterverzeichnis zeigen lassen

```nginx
server {
    server_name sofi.agorion.de;
    root /var/www/sofi.agorion.de/site;
    index index.html;
}
```

Falls für `sofi.agorion.de` noch kein TLS-Zertifikat existiert, z. B. per Certbot
einrichten (`certbot --nginx -d sofi.agorion.de`) — abhängig davon, wie die anderen
agorion.de-Subdomains auf diesem Server bereits abgesichert sind.

### 5. nginx-Konfiguration testen und neu laden

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
Kopier-Schritt nötig, kein `systemctl reload` erforderlich, da sich nur statische
Dateien ändern.

## Später: alternative Deploy-Variante

Statt `git pull` auf dem Server kann später auch lokal per `rsync`/`scp` nur der
Inhalt von `site/` auf den Server kopiert werden (kein `.git`, keine Doku-Dateien
auf dem Server). Diese Variante ist noch nicht eingerichtet.
