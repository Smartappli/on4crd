# ON4CRD

Site PHP moderne pour le radio-club ON4CRD avec contenus éditoriaux, espace membres, QSL, boutique, enchères, tableau de bord, PWA et administration.

## Fonctionnalités principales

- actualités, articles, comité, écoles, wiki et presse
- authentification membre, tableau de bord séparé et préférences newsletter
- module QSL avec import ADIF, génération SVG et export
- boutique club et module d'enchères
- workflow éditorial multilingue FR/EN/NL/DE
- gestion newsletter (abonnés, import CSV, campagnes et désabonnement)
- PWA hors ligne et SEO avancé (canonical, robots, sitemap dynamique)
- sécurité renforcée sur uploads, headers, CSRF, CSP et flux distants
- cache applicatif fichier (TTL) pour accélérer pages publiques et sitemap

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


### Mode maintenance

Vous pouvez activer un mode maintenance dans `config/config.php`:

```php
'app' => [
    // ...
    'maintenance' => [
        'enabled' => true,
        'secret' => 'votre-cle-bypass',
        'allowed_routes' => ['login', 'robots.txt', 'sitemap.xml'],
    ],
],
```

- Quand activé, toutes les routes non autorisées renvoient `503` avec `offline.html`.
- Un bypass session est possible avec `?maintenance_bypass=<secret>`.


### Assistant d’installation (déploiement initial)

Pour un déploiement initial simplifié, ouvrez :

```
http://<votre-domaine>/install.php
```

L’assistant fonctionne en 2 étapes :
1. création automatique de `config/config.php` avec test de connexion MySQL ;
2. initialisation de la base + création du compte administrateur.

Ensuite, vérifiez que `app.allow_install` reste à `false` et conservez `storage/install.lock`.

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

## Exploitation

- Observabilité minimale: `docs/OBSERVABILITE_PROD.md`
- Plan de reprise (PRA): `docs/PLAN_REPRISE_CLUB.md`

### Gestion des erreurs en production

La section `observability` de `config/config.php` accepte:

```php
'observability' => [
    'enabled' => true,
    'display_error_details' => false, // laisser false en production
],
```

- En production (`false`), les erreurs fatales non capturées renvoient un message générique avec une référence de requête.
- En développement (`true`), le message d’exception est ajouté à la réponse pour faciliter le débogage.
