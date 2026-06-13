# Runbook J0 - Go Live Synchronisation Montante

Objectif: activer la synchronisation montante (ordinateur -> plateforme) de facon controlee, avec verification et rollback rapide.

Portee: Laravel/Filament PLUSSCI-Courrier, endpoints `/api/sync-client/*`.

## 0. Prerequis (J-1)

- Acces admin serveur + acces SQL + acces stockage.
- Sauvegarde testee (restauration validee sur environnement de test).
- Communication envoyee aux utilisateurs (fenetre de maintenance).
- Une equipe support disponible pendant la fenetre.

## 1. Variables de production cibles

Verifier dans `.env`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_ENCRYPT=true`
- `QUEUE_CONNECTION=database`
- `SYNC_ENABLED=true`
- `SYNC_DEFAULT_INTERVAL_MINUTES=10`
- `SYNC_MAX_FILES_PER_PULL=150`
- `SYNC_ALLOW_UPLOAD=true`

## 2. Chronologie J0 (minute par minute)

### T-30 min - Freeze applicatif

1. Annoncer debut de fenetre.
2. Geler les modifications non critiques.
3. Verifier l'espace disque disponible (>20%).

### T-25 min - Backup complet

1. Sauvegarder la base.
2. Sauvegarder `storage/app/public`.
3. Capturer un snapshot VM/serveur (si disponible).

Resultat attendu: artefacts de backup disponibles et verifies.

### T-20 min - Verification des workers

Executer:

```bash
php artisan queue:failed
php artisan queue:monitor database:default --max=100
```

Resultat attendu: pas de panne queue critique.

### T-15 min - Verification routes sync

Executer:

```bash
php artisan route:list --path=sync-client
```

Resultat attendu: 4 routes presentes (`ping`, `config`, `changes`, `download`).

### T-12 min - Nettoyage cache framework

Executer:

```bash
php artisan optimize:clear
```

Resultat attendu: config/routes/views recachees proprement au prochain appel.

### T-10 min - Activation effective

1. Mettre a jour `.env` avec les valeurs phase 2 (upload active).
2. Appliquer:

```bash
php artisan optimize:clear
```

Resultat attendu: `SYNC_ALLOW_UPLOAD=true` pris en compte.

### T-8 min - Smoke test API (token de test)

Depuis un poste de test (ou Postman):

1. `GET /api/sync-client/ping`
2. `GET /api/sync-client/config`
3. `GET /api/sync-client/changes`

Resultat attendu: `ok=true`, config utilisateur/global cohérente.

### T-5 min - Test fonctionnel metier

Cas de test minimum:

1. Poste A: creer/modifier un fichier local via client desktop.
2. Verifier reception cote plateforme.
3. Poste B: recuperer la modification via sync descendante.
4. Verifier les droits d'acces (utilisateur autorise vs non autorise).

Resultat attendu: flux bout en bout valide.

### T0 - Ouverture utilisateur pilote

1. Ouvrir a un groupe pilote (10-20 utilisateurs).
2. Activer surveillance rapprochee 60 minutes.

### T+15 min - Point de controle 1

Verifier:

- taux erreurs 5xx
- taux erreurs 4xx auth token
- latence moyenne endpoints sync
- volume upload

Seuils d'alerte recommandés:

- 5xx > 2%
- latence p95 > 2s
- echec auth > 5% (tokens invalides)

### T+30 min - Point de controle 2

1. Verifier l'absence de conflits anormaux.
2. Verifier la file de jobs.
3. Confirmer l'absence d'impact sur autres modules (courriers, GED, notifications).

### T+60 min - Decision

- Si stable: elargir progressivement le groupe.
- Si instable: rollback immediat (section 5).

## 3. Checklist de validation post-activation

- Routes sync accessibles.
- Auth jeton appareil fonctionnelle.
- Upload + download fonctionnels.
- Permissions document respectees.
- Jobs/queue stables.
- Aucune degradation visible du dashboard/admin.

## 4. Monitoring operationnel (J0 -> J+7)

A suivre quotidiennement:

- nombre de synchronisations reussies/echouees
- erreurs 401/403/429/500 sur `api/sync-client/*`
- volume journalier des uploads
- croissance du stockage
- conflits de versions

## 5. Rollback immediat (moins de 2 minutes)

### Option A - Desactivation upload uniquement (recommande)

1. Dans `.env`:

- `SYNC_ALLOW_UPLOAD=false`

2. Appliquer:

```bash
php artisan optimize:clear
```

Effet: la sync descendante reste active, les uploads clients sont coupes.

### Option B - Arret complet sync

1. Dans `.env`:

- `SYNC_ENABLED=false`

2. Appliquer:

```bash
php artisan optimize:clear
```

Effet: tous flux sync desactives.

## 6. Communication standard

Message utilisateur en cas de rollback partiel:

"La synchronisation montante est temporairement suspendue pour stabilisation. La consultation et la synchronisation descendante restent disponibles."

## 7. Annexes - Commandes utiles

Etat migrations:

```bash
php artisan migrate:status
```

Routes sync:

```bash
php artisan route:list --path=sync-client
```

Purge cache:

```bash
php artisan optimize:clear
```

Etat jobs en echec:

```bash
php artisan queue:failed
```

Relancer jobs echoues:

```bash
php artisan queue:retry all
```

---

Owner runbook: Equipe plateforme PLUSSCI
Derniere mise a jour: 2026-03-06
