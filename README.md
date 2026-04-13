# ON4CRD

Site PHP moderne pour le radio-club ON4CRD avec contenus éditoriaux, espace membres, QSL, boutique, enchères, tableau de bord, PWA et administration.

## Fonctionnalités principales

- actualités, articles, comité, écoles, wiki et presse
- authentification membre et tableau de bord séparé
- module QSL avec import ADIF, génération SVG et export
- boutique club et module d'enchères
- workflow éditorial multilingue FR/EN/NL/DE
- PWA hors ligne et SEO de base
- sécurité renforcée sur uploads, headers, CSRF, CSP et flux distants

## Démarrage local avec Docker Compose

Le projet inclut une stack Docker Compose qui:
- installe automatiquement les dépendances PHP nécessaires;
- initialise la base MySQL avec `schema/schema.sql`;
- crée (ou met à jour) automatiquement un compte administrateur.

### Lancer

```bash
docker compose up --build
```

Le site est accessible sur `http://localhost:8080`.

### Variables utiles

Vous pouvez personnaliser les variables via un fichier `.env` à la racine:

- `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_ROOT_PASS`
- `APP_URL`
- `ADMIN_CALLSIGN`, `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`

Exemple rapide:

```bash
cat > .env <<'ENV'
ADMIN_CALLSIGN=F4ABC
ADMIN_NAME=Admin Local
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=SuperSecret123!
ENV
```

Puis relancer:

```bash
docker compose up --build
```
