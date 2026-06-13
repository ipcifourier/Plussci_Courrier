# Guide utilisateur — Gestionnaire de fichiers avancé

## Objectif
Ce guide explique comment utiliser la page **Gestionnaire de fichiers avancé** dans l'administration PLUSS-CI pour:
- classer des documents rapidement,
- déplacer/couper/copier des lots,
- travailler hors ligne puis synchroniser,
- visualiser les documents en mode liste ou galerie.

---

## Accès
- Menu: `GED` -> `Gestionnaire fichiers`
- URL: `admin/advanced-file-manager-page`

### Permissions minimales
- Consultation: `ged.documents.view`
- Ajout (upload): `ged.documents.create`
- Déplacement/édition: `ged.documents.update`
- Suppression: `ged.documents.delete`

---

## Vue générale de l'écran
La page contient:
1. **Bandeau de pilotage**: compteurs et options d'affichage.
2. **Pilotage rapide**: recherche, filtres, tri, vue `Liste/Galerie`.
3. **Glisser-déposer (upload)**: import de fichiers vers un dossier cible.
4. **Dossiers cibles (drop zone)**: déplacement d'un document par drag-and-drop, avec recherche et filtres temporels.
5. **Presse-papiers intelligent**: copier/couper/coller/supprimer la sélection.
6. **Fichiers**: affichage des documents, actions `Ouvrir` et `Visionner`.
7. **Tâches hors ligne**: tâches synchronisées avec le serveur (et file locale hors ligne).

---

## Utilisation pas à pas

## 1) Rechercher et filtrer
- Saisir un mot-clé dans `Recherche` (titre, référence, mots-clés).
- Filtrer par `Dossier` ou `Type`.
- Trier par `Date`, `Titre`, `Référence` ou `Type`.
- Changer la vue avec `Liste` ou `Galerie`.

Résultat: la zone `Fichiers` se met à jour automatiquement.

---

## 2) Importer des fichiers (upload)
Dans la section `Glisser-déposer`:
1. Choisir (optionnel) un `Dossier cible`.
2. Renseigner `Type document`.
3. Choisir le niveau de confidentialité.
4. Déposer ou sélectionner plusieurs fichiers.
5. Cliquer `Importer les fichiers`.

Résultat: les documents sont créés dans la GED avec les métadonnées choisies.

---

## 3) Déplacer un document par glisser-déposer
Dans `Fichiers`:
1. Cliquer et maintenir un document (carte galerie ou ligne liste).
2. Le déposer dans un bloc dossier de `Gestionnaire de fenêtres: dossiers cibles`.

Options utiles dans `dossiers cibles`:
- `Rechercher un dossier cible` pour trouver rapidement un dossier parmi un grand volume.
- Filtres `Année` et `Mois` pour ne montrer que les dossiers actifs sur la période.
- Mode rapide: 3 dossiers affichés par défaut, bouton pour afficher tous les dossiers.

En ligne:
- le déplacement est appliqué immédiatement.

Hors ligne:
- l'opération est ajoutée à la file locale et synchronisée plus tard.

---

## 4) Utiliser la sélection et le presse-papiers
### Sélection
- Cliquer `Sélectionner` sur les cartes en galerie, ou cocher en liste.

### Actions disponibles
- `Copier la sélection`: place les IDs en mode copie.
- `Couper la sélection`: place les IDs en mode déplacement.
- `Coller dans le dossier filtré`: applique dans le dossier actuellement filtré.
- `Supprimer la sélection`: suppression immédiate en ligne, ou mise en file hors ligne (V2.1).
- `Vider sélection`: réinitialise la sélection.

---

## 5) Comprendre le mode hors ligne (V2 + V2.1)
Le module gère une file locale d'opérations (IndexedDB):
- `move` (déplacement),
- `delete` (suppression lot).

Indicateurs:
- `opérations en file`
- `suppressions en attente`

Synchronisation:
- automatique au retour réseau,
- ou manuelle via le bouton `Synchroniser`.

---

## 6) Badge “Suppression en attente (offline)”
Quand une suppression est lancée hors ligne:
- un badge apparaît sur les documents concernés,
- le badge disparaît après synchronisation réussie.

---

## 7) Ouvrir et Visionner
Dans `Fichiers`:
- `Ouvrir`: ouvre la fiche document Filament.
- `Visionner`: ouvre le média dans un nouvel onglet.

---

## 8) Personnaliser l'affichage
La page propose des options d'interface:
- Style: `Epuree` / `Visuelle`
- Densité: `Confort` / `Compact`

Les préférences sont sauvegardées localement dans le navigateur.

---

## 9) Utiliser le Gestionnaire de tâches hors ligne
La section `Gestionnaire de tâches hors ligne` sert a suivre vos actions a faire, meme sans connexion.

Fonctionnement:
1. Ajouter une tâche avec `Ajouter`.
2. Cocher la case pour marquer `fait`.
3. Cliquer `Retirer` pour supprimer.

Synchronisation:
- En ligne: les tâches sont synchronisées avec votre compte utilisateur (persistées en base).
- Hors ligne: les changements sont stockés localement, puis synchronisés automatiquement au retour de la connexion.

Remarques:
- Les tâches sont rattachées a l'utilisateur connecté.
- En cas de conflit simple, la dernière action locale synchronisée est appliquée.

---

## Bonnes pratiques
- Filtrer d'abord le dossier cible avant des opérations en lot.
- Vérifier les badges offline avant de fermer la session.
- Lancer `Synchroniser` après une période sans réseau.
- Préférer `Couper + Coller` pour reclasser rapidement plusieurs documents.

---

## Dépannage rapide
## Les boutons ne réagissent pas
- Faire un rechargement complet du navigateur (Ctrl+F5).
- Vérifier que JavaScript est actif.
- Vérifier que vous êtes sur `admin/advanced-file-manager-page`.

## Les opérations ne se synchronisent pas
- Vérifier la connexion internet.
- Cliquer `Synchroniser` manuellement.
- Vérifier les permissions (`update`/`delete`).

## Les tâches hors ligne ne remontent pas
- Vérifier que la session est toujours active.
- Recharger la page puis repasser en ligne.
- Vérifier dans la console navigateur l'absence d'erreur réseau sur `offline-tasks/sync`.

## Un document ne se déplace pas
- Vérifier que le dossier cible existe et est sélectionnable.
- Vérifier vos permissions `ged.documents.update`.

## La suppression échoue
- Vérifier la permission `ged.documents.delete`.

---

## Référence technique (admin)
- Vue: `resources/views/filament/pages/advanced-file-manager.blade.php`
- Page Filament: `app/Filament/Pages/AdvancedFileManagerPage.php`
- Sync offline API: `POST admin/file-manager/sync-ops`
- Contrôleur sync: `app/Http/Controllers/FileManagerSyncController.php`
- API tâches: `GET/POST/PATCH/DELETE admin/file-manager/offline-tasks`
- API sync tâches: `POST admin/file-manager/offline-tasks/sync`
- Contrôleur tâches: `app/Http/Controllers/FileManagerOfflineTaskController.php`
- Modèle tâches: `app/Models/OfflineTask.php`
- Table: `offline_tasks`
