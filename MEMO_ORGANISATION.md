# Mémo — organisation du mini phpMyAdmin

## Le trajet d’une action

1. React affiche la page et les formulaires dans le navigateur.
2. `api.js` envoie les demandes au PHP avec `fetch()`.
3. Une route PHP reçoit la demande et appelle un service.
4. Le service exécute la requête SQL avec PDO pour contacter MySQL.
5. PHP renvoie une réponse JSON ; React affiche le résultat et actualise la liste.

Un **composant React** représente une partie de la page.
Un **service PHP** est une classe contenant les fonctions qui réalisent les opérations SQL.

## React : ce que je vois à l’écran

Les chemins de ce tableau partent de `frontend/src/`.

Les composants sont rangés selon leur rôle dans `components/` :

- `views/` rassemble les vues MySQL et PostgreSQL.
- `databases/` contient l’explorateur d’une base et la création de bases MySQL.
- `tables/`, `columns/` et `rows/` regroupent les actions sur les tables, les colonnes et les lignes.
- `backups/` contient la création, l’historique et la restauration des sauvegardes.
- `connections/` contient le formulaire de test de connexion.
- `alerts/` contient l’affichage des alertes.
- `common/` contient les éléments réutilisés, comme les messages et la confirmation de suppression.

`App.jsx` assemble les vues, `main.jsx` démarre React, `services/api.js` envoie les demandes au backend
et `index.css` conserve les styles communs.


| Fichier | Rôle |
| --- | --- |
| `main.jsx` | Démarre React et affiche `App`. |
| `App.jsx` | Affiche le menu de gauche et garde le nom de la base choisie. |
| `components/databases/DatabaseExplorer.jsx` | Affiche les tables de cette base et garde le nom de la table choisie. |
| `components/tables/TableStructure.jsx` | Affiche les colonnes et assemble les formulaires et les données de la table. |
| `components/databases/CreateDatabase.jsx` | Formulaire pour créer une base. |
| `components/tables/CreateTable.jsx` | Formulaire pour créer une table. |
| `components/tables/TableActions.jsx` | Renommer ou supprimer une table. |
| `components/columns/AddColumn.jsx` | Formulaire pour ajouter une colonne. |
| `components/columns/ColumnActions.jsx` | Renommer une colonne, changer son type ou la supprimer. |
| `components/rows/TableRows.jsx` | Affiche les lignes et les boutons Modifier et Supprimer. |
| `components/rows/RowForm.jsx` | Formulaire commun pour ajouter une ligne ou modifier une ligne existante. |
| `components/common/DeleteButton.jsx` | Demande confirmation ; pour une base, demande aussi de recopier son nom. |
| `components/common/Feedback.jsx` | Affiche les messages de chargement, réussite et erreur. |
| `services/api.js` | Regroupe les appels `fetch()`, lit le JSON reçu et signale les erreurs. |
| `index.css` | Contient tous les styles Théoa, classés par parties commentées. |

### Comprendre DatabaseExplorer

Si je clique sur la base `boutique`, `App` donne ce nom à `DatabaseExplorer`.
Sa fonction `loadTables()` charge les tables, par exemple `produits` et `clients`.
Si je clique sur `produits`, il mémorise ce nom dans `selectedTable` et affiche sa structure.
Après une création, un renommage ou une suppression de table, la liste est rechargée.

### Les noms qui reviennent dans les composants

- `useState` mémorise une information : nom saisi, sélection, message, etc.
- `useEffect` permet notamment de charger les données à l’ouverture du composant.
- `handleSubmit` est la fonction appelée quand on valide un formulaire.
- `loading` indique qu’une demande est en cours et permet de désactiver le bouton.
- `error` contient le message d’erreur ; `success` contient le message de réussite.
- `onCreated`, `onChanged` et `onSaved` sont des fonctions transmises par le composant parent.
  Le formulaire les appelle après une réussite pour que le parent actualise ses données.

Il n’y a pas de hook personnalisé : seuls les outils habituels de React sont utilisés.

## PHP : recevoir la demande et agir sur les bases SQL

`backend/public/index.php` charge les classes et les routes, puis démarre FlightPHP.

Chaque dossier a un rôle simple :

- `backend/public/` contient l’entrée de l’API : `index.php` charge les fichiers et démarre FlightPHP.
- `backend/bin/` contient les scripts lancés dans le terminal ou par cron.
- `backend/tests/` contient la suite automatisée API et sauvegarde/restauration.
- `backend/src/database/` ouvre les connexions à MySQL et PostgreSQL.
- `backend/src/http/` lit les demandes et prépare les réponses HTTP. `HttpError` reste dans `Api.php`.
- `backend/src/validation/` vérifie les noms, les valeurs et les confirmations reçues.
- `backend/src/services/` réalise les actions sur les bases, tables, colonnes, lignes et sauvegardes.
- `backend/src/backups/` retrouve les fichiers de sauvegarde pour les lister ou les télécharger.
- `backend/src/connections/` teste les informations de connexion saisies dans le formulaire.
- `backend/src/routes/` relie chaque adresse HTTP à l’action correspondante.

Les routes sont dans `backend/src/routes/` :

| Route | Service appelé | Opérations |
| --- | --- | --- |
| `databases.php` | `services/DatabaseService.php` | Lister, créer et supprimer les bases personnelles. |
| `tables.php` | `services/TableService.php` | Lister, créer, renommer et supprimer les tables. |
| `columns.php` | `services/ColumnService.php` | Lire, ajouter, modifier et supprimer les colonnes. |
| `rows.php` | `services/RowService.php` | Lire, ajouter, modifier et supprimer les lignes. |
| `health.php` | Utilise directement la connexion PDO | Vérifier que l’API et MySQL répondent. |

Les chemins suivants partent de `backend/src/` :

- `database/Database.php` : ouvre la connexion PDO à MySQL.
- `services/SchemaService.php` : demande à MySQL la description réelle de la table, ses colonnes
  et sa clé primaire. Par exemple, il découvre que `id` identifie un produit.
- `validation/SqlValidation.php` : vérifie les noms, les types, les valeurs et les confirmations.
  Il protège aussi les bases système pour éviter une mauvaise manipulation.
- `http/Api.php` : lit le JSON envoyé par React et prépare les réponses ou les erreurs HTTP.

`api.js` travaille dans le navigateur ; `http/Api.php` travaille sur le serveur PHP.

## Exemple : modifier le nom d’un produit

1. Dans `TableRows.jsx`, je clique sur Modifier.
2. `RowForm.jsx` affiche les valeurs du produit ; je change son nom et je valide.
3. `api.js` envoie les valeurs modifiées et la clé primaire originale au PHP.
4. `routes/rows.php` reçoit la demande et appelle `RowService::update()`.
5. Le service vérifie les informations et exécute l’`UPDATE` avec PDO.
6. Après la réponse de réussite, `TableRows.jsx` recharge les lignes.

La vraie clé primaire identifie la ligne, y compris si elle contient plusieurs colonnes.
Sans clé exploitable, les boutons de modification et suppression sont désactivés.

## Les fichiers de configuration

- `docker-compose.yaml` : organise les conteneurs frontend, backend, MySQL, PostgreSQL, scheduler et tests.
- Les `Dockerfile` : décrivent comment préparer les conteneurs frontend et backend.
- `frontend/package.json` : liste les dépendances JavaScript et les commandes Vite.
- `frontend/vite.config.js` : configure Vite et transmet les demandes `/api` au backend.
- `backend/composer.json` : liste les dépendances PHP, dont FlightPHP.
- Les fichiers `package-lock.json` et `composer.lock` enregistrent les versions des dépendances.
- `.env` : contient la configuration locale ; ne pas le publier ni afficher ses secrets.
- `README.md` : installation, commandes utiles, fonctions disponibles et limites.

Pour vérifier une fonction qui touche aux données, utiliser une base de test dédiée.
Le renommage d’une base MySQL n’est pas proposé ; cette limite est expliquée dans le README.

## Intégration SafeBase

- `PostgresBackups.jsx` charge les bases, conserve la sélection et recharge après restauration.
- `PostgresBackupForm.jsx` gère la création du dump et ses messages.
- `BackupHistory.jsx` et `RestoreBackup.jsx` partagent les écrans via `engine`.
- `routes/postgres.php` appelle `services/PostgresBackupService.php`,
  `backups/PostgresBackupCatalog.php` et `services/PostgresRestoreService.php`.
- `bin/backup.php base [mysql|postgres]` lance la sauvegarde en CLI (MySQL par défaut).
- `bin/configure-cron.php` prépare une tâche quotidienne pour les deux moteurs.

Le [guide SafeBase](docs/SAFEBASE.md) décrit les commandes et limites.

## Où sont les tests automatisés ?

État au **28 octobre 2026** : le service Docker `tests` lance `backend/tests/run.php`
automatiquement après les contrôles de disponibilité de l’API et des deux moteurs SQL.

- `tests/run.php` lance les cinq tests dans l’ordre et compte les échecs.
- `tests/api.php` sert aux trois lectures HTTP : bases MySQL, bases PostgreSQL et
  alertes. Il vérifie le statut 200, le JSON et la présence de la liste attendue.
- `tests/mysql-backup-restore.php` et `tests/postgres-backup-restore.php` appellent
  directement les services PHP. Ils créent des bases à suffixe aléatoire,
  sauvegardent un produit témoin, restaurent et comparent les données attendues.
  Ils tentent ensuite de supprimer leurs bases, leur dump et son dossier.

Le conteneur s’arrête après la suite : `0` si tout réussit, `1` en cas d’échec,
y compris au nettoyage. Il ne bloque pas le frontend si un test échoue.
Le résultat communiqué est **5 tests exécutés, 0 échec(s).** La suite n’a pas été
relancée pour cette mise à jour documentaire.

Le [guide SafeBase](docs/SAFEBASE.md#tests-automatisés) détaille les commandes,
les cinq noms de tests, leurs limites et les éléments pouvant rester après un échec.
