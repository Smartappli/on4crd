# Réaudit détaillé code & sécurité — 2026-04-12

## Méthodologie
- Analyse statique complète sur `app/`, `pages/`, `schema/`, `.github/workflows/`.
- Lint PHP global (`php tools/lint.php`).
- Détection automatique des appels à fonctions non définies dans le dépôt (script PHP tokenisé).
- Revue ciblée des surfaces à risque: uploads, SSRF/URLs sortantes, transactions métier (boutique/enchères), pipeline CI sécurité.

## Résumé exécutif
- ✅ **Améliorations de sécurité présentes** sur uploads, validation d'URL distante, transactions concurrentes, et CI sécurité.
- ⚠️ **Risque applicatif majeur restant**: de nombreuses fonctions appelées par l'application ne sont pas définies dans les fichiers du dépôt audité (82 identifiants remontés), ce qui peut provoquer des erreurs fatales à l'exécution sur des routes centrales.
- ✅ La syntaxe PHP est valide après corrections.

## Points vérifiés et résultats

### 1) Uploads (MIME, signature, taille, métadonnées, exécution)
**État actuel**
- Upload centralisé avec validation MIME (`finfo`), extension autorisée, signature binaire, taille max, nom randomisé, permissions 0644.
- Nettoyage des images via re-encodage GD (suppression des métadonnées EXIF/IPTC selon format) avant stockage.
- Blocage de l'exécution de scripts en zone `storage/` via `.htaccess` dédié.

**Conclusion**
- Les exigences de durcissement upload sont globalement couvertes.

### 2) Concurrence métier (stock / enchères)
**État actuel**
- `place_shop_order()` verrouille les lignes produit (`FOR UPDATE`) pendant la commande + décrément de stock conditionnel.
- `place_auction_bid()` verrouille le lot (`FOR UPDATE`), valide le minimum, et détecte un conflit concurrent via update conditionnel et `rowCount()`.

**Conclusion**
- Le risque de course a été significativement réduit sur les flux critiques.

### 3) Validation défensive des URLs externes
**État actuel**
- Validation HTTP/HTTPS en amont.
- Blocage des hôtes privés/réservés + re-résolution DNS A/AAAA et rejet des IP privées/réservées.

**Conclusion**
- Durcissement SSRF amélioré de manière pertinente côté validation.

### 4) Pipeline sécurité
**État actuel**
- Job CI `security` ajouté avec: lint, tests, SAST PHPStan et `composer audit`.
- PHPStan est désormais bloquant (pas de `continue-on-error`).

**Conclusion**
- Le pipeline répond à l'objectif de contrôle continu de sécurité.

## Risque critique restant: fonctions manquantes
- Le script d'inventaire a remonté **82 fonctions appelées mais non définies** dans le dépôt (exemples: `db`, `render_layout`, `require_login`, `table_exists`, etc.).
- Impact: risque élevé d'erreurs fatales runtime sur des routes principales.
- Recommandation prioritaire:
  1. Restaurer le bloc fonctionnel manquant (ou les fichiers omis) depuis la source canonique.
  2. Ajouter un test de fumée CLI qui charge `app/bootstrap.php` + route publique pour détecter immédiatement ce type de régression.

## Commandes exécutées (réaudit)
- `php tools/lint.php`
- `php -r '...token_get_all...'` (inventaire définitions/appels de fonctions)
- `rg -n "file_get_contents\(|curl_|fopen\(" app pages`
