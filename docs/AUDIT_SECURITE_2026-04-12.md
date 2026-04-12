# Audit code & sécurité — 2026-04-12

## Portée
- Revue statique du code PHP (`app/`, `pages/`, `tests/`).
- Vérifications de syntaxe PHP sur tous les fichiers `.php` du dépôt.
- Revue ciblée des points sensibles : routage, CSRF, SQL, upload de fichiers, en-têtes HTTP de sécurité.

## Résumé exécutif
- **État global : bloquant** à cause d'une erreur de syntaxe dans `app/functions.php`.
- **Points positifs** : usage systématique de requêtes préparées PDO dans les pages revues, présence d'un modèle CSRF sur les formulaires POST, et en-têtes de sécurité HTTP déjà définis.
- **Risque principal** : le noyau applicatif est actuellement non chargeable à cause d'un segment corrompu (`[... ELLIPSIZATION ...]`).

## Constats détaillés

### 1) Bloquant — corruption de code dans `app/functions.php`
- Le fichier contient littéralement la chaîne `[...] ELLIPSIZATION ...` au milieu d'une fonction, ce qui casse le parsing PHP.
- Impact : l'application ne peut pas démarrer normalement (erreur fatale de parsing).
- Preuve:
  - `php -l app/functions.php` retourne une erreur de syntaxe sur la ligne 306.
  - Le contenu visible autour de la ligne montre explicitement la chaîne de troncature.

### 2) Bonnes pratiques présentes
- **Headers de sécurité** appliqués : CSP, HSTS (si HTTPS), `X-Content-Type-Options`, `X-Frame-Options`, `Permissions-Policy`, `COOP`, `CORP`.
- **Protection CSRF** : vérification côté serveur (`verify_csrf()`) + champs `_csrf` sur les formulaires POST observés.
- **SQL** : usage majoritaire de `prepare()/execute()` avec paramètres liés.

### 3) Risques résiduels / améliorations recommandées
1. **Uploads**
   - Renforcer la validation MIME (finfo + signature), taille maximale, et nettoyage strict des métadonnées.
   - Vérifier que les fichiers uploadés restent hors exécution PHP (règles web server + storage non exécutable).
2. **Concurrence métier (enchères/stock)**
   - Envisager `SELECT ... FOR UPDATE` ou contraintes SQL supplémentaires pour éviter des courses critiques sous charge.
3. **Validation défensive des URLs externes**
   - La base est bonne, mais compléter contre les contournements DNS (re-résolution et contrôle au moment de la connexion HTTP).
4. **Pipeline sécurité**
   - Ajouter un job CI avec : lint PHP, tests, et un SAST (ex: Psalm/PHPStan avec règles sécurité), plus un contrôle dépendances (`composer audit`).

## Priorisation (court terme)
1. **P0** : restaurer immédiatement `app/functions.php` (fichier complet non tronqué) et relancer lint/tests.
2. **P1** : verrouiller les flux d'upload (MIME/signature/taille + stockage non exécutable).
3. **P1** : renforcer les transactions critiques (enchères/stock) pour limiter les races.
4. **P2** : industrialiser les contrôles sécurité en CI.

## Commandes exécutées
- `for f in $(find . -name '*.php' -not -path './vendor/*'); do php -l "$f" >/tmp/lint.out 2>&1 || { echo "$f"; cat /tmp/lint.out; }; done`
- `rg -n "_csrf|verify_csrf\(" pages`
- `rg -n -- "query\(|prepare\(|execute\(|ORDER BY .*\. |LIMIT .*\. |\$sql\s*\.=" pages app`

