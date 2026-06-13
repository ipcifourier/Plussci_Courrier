# Acquisition & OCR — Documentation technique

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture & flux de données](#2-architecture--flux-de-données)
3. [Canaux d'acquisition](#3-canaux-dacquisition)
   - 3.1 [Dépôt manuel (interface web)](#31-dépôt-manuel-interface-web)
   - 3.2 [Importation par e-mail (IMAP)](#32-importation-par-e-mail-imap)
   - 3.3 [Dossier de numérisation surveillé (scan folder)](#33-dossier-de-numérisation-surveillé-scan-folder)
4. [Pipeline OCR](#4-pipeline-ocr)
5. [Classification automatique](#5-classification-automatique)
6. [Formats de fichiers acceptés](#6-formats-de-fichiers-acceptés)
7. [Configuration](#7-configuration)
8. [Commandes Artisan](#8-commandes-artisan)
9. [Modèle de données](#9-modèle-de-données)
10. [Contrôle d'accès](#10-contrôle-daccès)
11. [Indicateurs & tableau de bord](#11-indicateurs--tableau-de-bord)

---

## 1. Vue d'ensemble

Le module **Acquisition & OCR** constitue le point d'entrée de tous les documents dans la GED (Gestion Électronique de Documents). Il combine :

- **Trois canaux d'acquisition** : dépôt web, e-mail IMAP, dossier scanner réseau.
- **Un pipeline OCR asynchrone** basé sur `smalot/pdfparser` (PDF natif) et Tesseract (images, PDF numérisés).
- **Une classification automatique** par analyse textuelle qui détecte le type documentaire, extrait les mots-clés et propose un dossier cible.
- **Un panier d'acquisition** qui affiche une prévisualisation (type détecté, disponibilité OCR, suggestion de classement) avant le transfert définitif vers la GED.

**Page Filament :** `GED > Acquisition & OCR`  
**Classe :** `App\Filament\Pages\AcquisitionPage`

---

## 2. Architecture & flux de données

```
┌─────────────────────────────────────────────────────────┐
│                   Canaux d'acquisition                  │
│                                                         │
│  Interface web    →   AcquisitionPage::save()           │
│  IMAP             →   EmailImportService                │
│  Scan folder      →   ProcessScanFolderCommand          │
└───────────────────────────┬─────────────────────────────┘
                            │
                            ▼
                  DocumentImportService::import()
                            │
          ┌─────────────────┼─────────────────────┐
          ▼                 ▼                     ▼
     Document          DocumentVersion         Spatie Media
     (create)         (version 1.0)          (fichier physique)
                            │
                            ▼
                  ExtractDocumentTextJob   (queue async)
                            │
                            ▼
                        OcrService
               ┌────────────────────────┐
               │  PDF → smalot/pdfparser│
               │  Image → Tesseract     │
               │  TXT  → lecture directe│
               └────────────────────────┘
                            │
                            ▼
                  ClassifyDocumentJob   (délai 3 s)
                            │
                            ▼
                  AutoClassificationService::classify()
                  (type, tags, mots-clés, métadonnées)
```

---

## 3. Canaux d'acquisition

### 3.1 Dépôt manuel (interface web)

L'opérateur dépose jusqu'à **20 fichiers** par lot via l'interface Filament.

**Options du formulaire :**

| Champ | Description | Valeur par défaut |
|---|---|---|
| Fichiers | Sélection multi-fichiers (glisser-déposer) | — |
| Dossier cible | Dossier GED de destination | Aucun (non classé) |
| Type de document | Type documentaire ou `Détection automatique` | `__auto__` |
| Préfixe du titre | Ajouté devant le nom de chaque fichier | Vide |
| Reconnaissance automatique du type | Active la détection par nom + OCR | ✓ |
| Classement intelligent | Propose le meilleur dossier cible | ✓ |
| Confidentialité | Standard / Confidentiel / Personnel | Standard |

**Taille maximale par fichier :** configurable via `ged.max_file_size_mb` (défaut : 50 Mo).

**Résolution du type documentaire :**

```
si type sélectionné ≠ "__auto__"
    → utiliser le type sélectionné
sinon si auto_detect_type = true
    → AutoClassificationService::detectType(titre, nom fichier)
    → si confiance < 50 % → "Document"
sinon
    → "Document"
```

**Résolution du dossier cible :**

```
si dossier_id renseigné manuellement
    → utiliser ce dossier
sinon si auto_suggest_dossier = true
    → chercher un dossier actif dont le libellé match le type détecté
sinon
    → null (document non classé)
```

---

### 3.2 Importation par e-mail (IMAP)

Accessible via le bouton **« Importer depuis e-mail »** dans la page Acquisition, ou directement via la commande Artisan.

**Comportement :**

1. Connexion IMAP avec les paramètres fournis (ou ceux de la configuration).
2. Récupération de tous les messages **non lus** (`unseen`) dans le dossier IMAP spécifié.
3. Pour chaque message : importation de chaque pièce jointe comme un `Document` distinct.
4. Le sujet du message devient le titre du document ; l'expéditeur est enregistré dans `source_meta`.
5. Le message est marqué **lu** (`Seen`) après traitement.
6. Les messages sans pièce jointe sont ignorés (comptabilisés dans `skipped`).

**Classe :** `App\Services\EmailImportService`

**Source enregistrée :** `source = 'email'`, `source_meta = "De: sender | Sujet: subject"`

---

### 3.3 Dossier de numérisation surveillé (scan folder)

Permet l'intégration avec des scanners réseau ou multifonctions qui déposent des fichiers dans un dossier partagé.

**Fonctionnement :**

1. Le scanner dépose les fichiers dans le dossier `ACQUISITION_SCAN_FOLDER` (défaut : `storage/app/scan-inbox`).
2. La commande `acquisition:process-scan-folder` lit ce dossier, importe chaque fichier dans la GED via `DocumentImportService`, puis déplace le fichier traité vers `ACQUISITION_SCAN_DONE_FOLDER` (défaut : `storage/app/scan-done`).
3. L'interface web affiche en temps réel le contenu du dossier scanner et le nombre de fichiers en attente.

**Extensions prises en charge :** `pdf`, `jpg`, `jpeg`, `png`, `tiff`, `tif`, `bmp`, `doc`, `docx`, `txt`

**Source enregistrée :** `source = 'scan_folder'`

---

## 4. Pipeline OCR

### Classe `App\Services\OcrService`

#### Méthode `extract(string $filePath, string $mimeType): array`

Retourne `['status' => string, 'text' => string|null, 'error'? => string]`

| Statut | Signification |
|---|---|
| `completed` | Texte extrait avec succès |
| `pending` | En attente de traitement |
| `processing` | Extraction en cours |
| `failed` | Erreur lors de l'extraction |
| `unavailable` | Type MIME non supporté ou Tesseract absent |

#### Backends d'extraction

| Type de fichier | Backend | Prérequis |
|---|---|---|
| PDF avec texte natif | `smalot/pdfparser` (PHP pur) | Aucun |
| PDF scanné (image) | Tesseract OCR (fallback) | Binaire Tesseract installé |
| Image (JPG, PNG, TIFF…) | Tesseract OCR | Binaire Tesseract installé |
| Texte brut (TXT) | `file_get_contents` | Aucun |

#### Détection de Tesseract

Le service cherche le binaire dans cet ordre :
1. Variable d'environnement `TESSERACT_PATH`
2. `tesseract` (dans le `PATH`)
3. `C:\Program Files\Tesseract-OCR\tesseract.exe`
4. `C:\Program Files (x86)\Tesseract-OCR\tesseract.exe`
5. `/usr/bin/tesseract`
6. `/usr/local/bin/tesseract`

#### Job asynchrone `App\Jobs\ExtractDocumentTextJob`

- **Déclenchement :** automatiquement lors de chaque import, via `ExtractDocumentTextJob::dispatch($version->id)`.
- **Tentatives :** 2 (`$tries = 2`)
- **Timeout :** 120 secondes
- **En cas de succès OCR :** déclenche `ClassifyDocumentJob` avec un délai de 3 secondes.
- **En cas d'échec :** `ocr_status` = `failed`, `ocr_text` = null.

---

## 5. Classification automatique

### Classe `App\Services\AutoClassificationService`

#### Détection du type (`detectType`)

Le texte OCR, le titre et le nom de fichier sont analysés via des règles regex pondérées.

**Types détectés et poids :**

| Type documentaire | Poids | Exemples de patterns |
|---|---|---|
| Rapport activité | 3 | `rapport d'activité`, `bilan annuel` |
| Rapport mission | 3 | `rapport de mission`, `compte-rendu de mission` |
| PV réunion | 3 | `procès-verbal`, `PV réunion` |
| Compte-rendu | 2 | `compte-rendu`, `CR réunion` |
| Note de service | 2 | `note de service`, `N.S. n°` |
| Contrat | 2 | `contrat de`, `convention de`, `marché` |
| Facture | 2 | `facture n°`, `montant TTC`, `total HT` |
| Bon de commande | 2 | `bon de commande`, `BC n°` |
| Décision | 2 | `décision n°`, `arrêté n°` |
| Procédure | 2 | `procédure de`, `guide d'utilisation` |
| Courrier entrant | 1 | `ci-joint`, `j'ai l'honneur`, `veuillez trouver` |
| Courrier sortant | 1 | `objet :`, `référence :`, `à l'attention de` |

Le type retenu est celui avec le score le plus élevé. La **confiance** est calculée proportionnellement au score maximum possible (0–100 %).

> **Règle de mise à jour :** le type du document est remplacé par le type auto-détecté uniquement si la confiance est ≥ 60 % **et** si le type actuel est `Document`, `Autre` ou vide.

#### Extraction des tags et mots-clés

- Tokenisation du texte OCR + titre.
- Suppression des stopwords (listes française et anglaise intégrées).
- Conservation des tokens de 3–40 caractères.
- Dédoublonnage, tri par fréquence, conservation des 30 premiers.

#### Extraction de métadonnées structurées

Le service recherche et extrait automatiquement :
- **Dates** (formats `DD/MM/YYYY`, `YYYY-MM-DD`, dates en lettres)
- **Références documentaires** (numéros de référence)
- **Montants** (valeurs numériques avec indicateurs financiers)

---

## 6. Formats de fichiers acceptés

| Format | Extension | MIME type | OCR |
|---|---|---|---|
| PDF natif | `.pdf` | `application/pdf` | `smalot/pdfparser` |
| PDF scanné | `.pdf` | `application/pdf` | Tesseract (fallback) |
| Word (legacy) | `.doc` | `application/msword` | — |
| Word | `.docx` | `application/vnd.openxmlformats-officedocument.wordprocessingml.document` | — |
| Excel (legacy) | `.xls` | `application/vnd.ms-excel` | — |
| Excel | `.xlsx` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` | — |
| Image JPEG | `.jpg`, `.jpeg` | `image/jpeg` | Tesseract |
| Image PNG | `.png` | `image/png` | Tesseract |
| Image TIFF | `.tiff`, `.tif` | `image/tiff` | Tesseract |
| Texte brut | `.txt` | `text/plain` | Lecture directe |

---

## 7. Configuration

### Fichier `config/acquisition.php`

| Clé | Variable `.env` | Valeur par défaut | Description |
|---|---|---|---|
| `scan_folder` | `ACQUISITION_SCAN_FOLDER` | `storage/app/scan-inbox` | Dossier de dépôt scanner |
| `scan_done_folder` | `ACQUISITION_SCAN_DONE_FOLDER` | `storage/app/scan-done` | Dossier d'archivage après import |
| `tesseract_path` | `TESSERACT_PATH` | `` (auto-détection) | Chemin vers le binaire Tesseract |
| `tesseract_lang` | `TESSERACT_LANG` | `fra+eng` | Langues OCR (codes Tesseract) |
| `imap.host` | `IMAP_HOST` | `` | Serveur IMAP |
| `imap.port` | `IMAP_PORT` | `993` | Port IMAP |
| `imap.encryption` | `IMAP_ENCRYPTION` | `ssl` | Chiffrement (`ssl`, `tls`, vide) |
| `imap.validate_cert` | `IMAP_VALIDATE_CERT` | `false` | Validation du certificat SSL |
| `imap.username` | `IMAP_USERNAME` | `` | Adresse e-mail |
| `imap.password` | `IMAP_PASSWORD` | `` | Mot de passe |
| `imap.folder` | `IMAP_FOLDER` | `INBOX` | Dossier IMAP à surveiller |
| `imap.protocol` | `IMAP_PROTOCOL` | `imap` | Protocole (`imap`, `pop3`) |
| `max_file_size_mb` | `ACQUISITION_MAX_FILE_MB` | `50` | Taille maximale par fichier (Mo) |

### Exemple `.env`

```dotenv
# --- Acquisition & OCR ---

# Dossiers scanner
ACQUISITION_SCAN_FOLDER=/var/scan/inbox
ACQUISITION_SCAN_DONE_FOLDER=/var/scan/done

# Tesseract OCR
TESSERACT_PATH=/usr/bin/tesseract
TESSERACT_LANG=fra+eng

# Import e-mail IMAP
IMAP_HOST=imap.example.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_VALIDATE_CERT=false
IMAP_USERNAME=ged@example.com
IMAP_PASSWORD=secret
IMAP_FOLDER=INBOX

# Limite de taille
ACQUISITION_MAX_FILE_MB=50
```

### Paramètre GED persistant

La taille maximale peut aussi être surchargée depuis les paramètres applicatifs (`app_settings`) via la clé `ged.max_file_size_mb`, qui prend la priorité sur la valeur `.env`.

---

## 8. Commandes Artisan

### `acquisition:process-scan-folder`

Importe les fichiers présents dans le dossier scanner.

```bash
php artisan acquisition:process-scan-folder [options]
```

| Option | Description |
|---|---|
| `--dossier=<id>` | ID du dossier GED cible (optionnel) |
| `--folder=<path>` | Chemin du dossier de scan (écrase `ACQUISITION_SCAN_FOLDER`) |
| `--dry-run` | Liste les fichiers sans les importer |

**Exemple :**

```bash
# Import réel dans le dossier GED #5
php artisan acquisition:process-scan-folder --dossier=5

# Simulation sans import
php artisan acquisition:process-scan-folder --dry-run

# Dossier personnalisé
php artisan acquisition:process-scan-folder --folder=/mnt/scanner/hp-mfp
```

---

### `acquisition:import-emails`

Importe les pièces jointes des e-mails non lus depuis une boîte IMAP.

```bash
php artisan acquisition:import-emails [options]
```

| Option | Description |
|---|---|
| `--dossier=<id>` | ID du dossier GED cible (optionnel) |
| `--host=<hostname>` | Serveur IMAP (écrase `IMAP_HOST`) |
| `--port=<port>` | Port IMAP (défaut : 993) |
| `--username=<email>` | Adresse e-mail |
| `--password=<pwd>` | Mot de passe |
| `--folder=<folder>` | Dossier IMAP (défaut : `INBOX`) |
| `--dry-run` | Test de connexion sans import |

**Exemple :**

```bash
# Import depuis la configuration .env
php artisan acquisition:import-emails

# Import vers le dossier GED #3, boîte explicite
php artisan acquisition:import-emails --dossier=3 --host=imap.gmail.com --username=ged@example.com --password=secret

# Test de connexion uniquement
php artisan acquisition:import-emails --dry-run
```

---

### Planification recommandée (Scheduler)

Ajouter dans `routes/console.php` ou `App\Console\Kernel` :

```php
// Import scanner toutes les 5 minutes
Schedule::command('acquisition:process-scan-folder')->everyFiveMinutes();

// Import e-mails toutes les 15 minutes
Schedule::command('acquisition:import-emails')->everyFifteenMinutes();
```

---

## 9. Modèle de données

### `Document`

| Colonne | Type | Description |
|---|---|---|
| `reference_doc` | string | Référence unique auto-générée |
| `titre` | string | Titre du document |
| `type_document` | string | Type documentaire |
| `dossier_id` | FK | Dossier GED parent |
| `auteur_id` | FK | Utilisateur créateur |
| `etat_cycle_vie` | string | `Brouillon`, `Validé`, `Archivé`… |
| `confidentiality_level` | string | `Standard`, `Confidentiel`, `Personnel` |
| `version_courante_id` | FK | Dernière version active |
| `tags_json` | JSON | Liste de tags extraits |
| `keywords` | text | Mots-clés concaténés |
| `metadata_json` | JSON | Métadonnées structurées extraites |
| `classification_confidence` | int | Score de confiance (0–100) |
| `classified_at` | datetime | Date de dernière classification |

### `DocumentVersion`

| Colonne | Type | Description |
|---|---|---|
| `document_id` | FK | Document parent |
| `numero_version` | string | Ex : `1.0`, `1.1`, `2.0` |
| `media_id` | FK | Fichier physique (Spatie Media) |
| `ocr_status` | string | `pending` / `processing` / `completed` / `failed` / `unavailable` |
| `ocr_text` | longtext | Texte extrait par OCR |
| `checksum_sha256` | string | Empreinte SHA-256 du fichier |
| `source` | string | `upload`, `email`, `scan_folder` |
| `source_meta` | string | Métadonnée contextuelle (chemin, expéditeur…) |

---

## 10. Contrôle d'accès

L'accès à la page **Acquisition & OCR** est contrôlé par la méthode `canAccess()` :

```php
// App\Filament\Pages\AcquisitionPage
public static function canAccess(): bool
{
    return $user->hasRole('Super Admin')
        || $user->hasPermissionTo('ged.documents.create');
}
```

**Permission requise :** `ged.documents.create`

Cette permission est associée au groupe **GED** dans la matrice des rôles. Tout rôle disposant de cette permission peut accéder au module d'acquisition.

---

## 11. Indicateurs & tableau de bord

La page Acquisition affiche quatre compteurs en temps réel :

| Indicateur | Description |
|---|---|
| **OCR en attente** | `DocumentVersion` avec `ocr_status = 'pending'` |
| **OCR terminé** | `DocumentVersion` avec `ocr_status = 'completed'` |
| **Scan inbox** | Nombre de fichiers présents dans `ACQUISITION_SCAN_FOLDER` |
| **OCR en cours** | `DocumentVersion` avec `ocr_status = 'processing'` |

Ces valeurs sont calculées côté serveur dans la propriété calculée `acquisitionStats` de `AcquisitionPage` et rafraîchies à chaque rendu Livewire.

---

## Installation de Tesseract (Windows)

1. Télécharger l'installeur depuis : <https://github.com/UB-Mannheim/tesseract/wiki>
2. Installer en incluant le pack de langue **French** (`fra`) lors de l'installation.
3. Ajouter le répertoire d'installation au `PATH` système, ou renseigner le chemin complet dans `.env` :
   ```dotenv
   TESSERACT_PATH=C:\Program Files\Tesseract-OCR\tesseract.exe
   ```

## Installation de Tesseract (Linux / macOS)

```bash
# Debian / Ubuntu
sudo apt-get install tesseract-ocr tesseract-ocr-fra

# macOS (Homebrew)
brew install tesseract
brew install tesseract-lang   # pour le pack français
```

---

*Documentation générée pour PlusSCI Courrier — Module Acquisition & OCR.*
