# Plan de reprise d'activité (PRA) — ON4CRD

## Périmètre
- Base de données MySQL.
- Fichiers applicatifs versionnés.
- Données non versionnées: `storage/uploads`, `storage/cache` (selon politique), `config/config.php`.

## Objectifs de reprise
- **RPO cible**: 24h maximum.
- **RTO cible**: 4h maximum.

## Stratégie de sauvegarde
1. **Sauvegarde quotidienne DB**
   - Dump SQL horodaté.
2. **Sauvegarde quotidienne fichiers**
   - `storage/uploads` + `config/config.php`.
3. **Rétention**
   - 7 snapshots quotidiens
   - 4 snapshots hebdomadaires
   - 3 snapshots mensuels
4. **Hors site**
   - Copie chiffrée sur un stockage distant.

## Procédure de restauration (résumé)
1. Provisionner un environnement propre (Docker/VM).
2. Déployer la version applicative cible.
3. Restaurer `config/config.php`.
4. Restaurer `storage/uploads`.
5. Restaurer la base MySQL depuis le dump choisi.
6. Vérifier la santé:
   - accès page d'accueil
   - login admin
   - création commande test
   - soumission enchère test

## Exercice mensuel obligatoire
- Exécuter un test de restauration complet **une fois par mois**.
- Mesurer RTO réel.
- Archiver un rapport (date, durée, anomalies, actions correctives).

## Checklist d'audit mensuel
- Sauvegardes quotidiennes présentes et lisibles.
- Dernier test PRA < 31 jours.
- Procédure de restauration à jour.
- Rotation des secrets réalisée si incident sécurité.
