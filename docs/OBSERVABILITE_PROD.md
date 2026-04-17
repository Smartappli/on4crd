# Observabilité minimale en production

## Objectif
Mettre en place un socle simple de logs et métriques pour détecter rapidement les incidents sur les parcours critiques du site ON4CRD.

## Ce qui est instrumenté dans le code
- Un journal structuré JSON est activé au bootstrap (`setup_observability`).
- Chaque requête HTTP enregistre un événement `http_request` avec:
  - `route_name`
  - `status_code`
  - `duration_ms`
  - `method`
  - `request_id`
- Les erreurs PHP/Exceptions sont journalisées en `php_error` / `php_exception`.

## Métriques de base à suivre
1. **Erreurs applicatives**
   - Taux de réponses HTTP 5xx
   - Nombre d'événements `php_error`/`php_exception`
2. **Performance**
   - P95/P99 de `duration_ms` global
   - P95/P99 `duration_ms` pour routes critiques (`login`, `shop_checkout`, `auction_bid`, `admin_*`)
3. **Parcours métiers**
   - Taux d'échec de login (à enrichir via événements dédiés)
   - Nombre d'échecs checkout
   - Nombre d'échecs enchères

## Cible d'exploitation
- Collecteur recommandé: stack compatible JSON (ex: Loki/ELK/OpenSearch).
- Rétention minimale: 30 jours (90 jours recommandé pour forensic).
- Dashboard minimal:
  - erreurs 5xx par route
  - latence P95/P99 par route
  - top erreurs PHP

## Alertes minimales
- 5xx > 2% sur 5 minutes.
- P95 `duration_ms` > 1500 ms sur 10 minutes.
- Pic d'erreurs `shop_checkout` ou `auction_bid`.
