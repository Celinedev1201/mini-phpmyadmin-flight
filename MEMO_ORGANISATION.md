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

| Fichier | Rôle |
| --- | --- |
| `main.jsx` | Démarre React et affiche `App`. |
| `App.jsx` | Affiche le menu de gauche et garde le nom de la base choisie. |
| `components/DatabaseExplorer.jsx` | Affiche les tables de cette base et garde le nom de la table choisie. |
| `components/TableStructure.jsx` | Affiche les colonnes et assemble les formulaires et les données de la table. |
| `components/CreateDatabase.jsx` | Formulaire pour créer une base. |
| `components/CreateTable.jsx` | Formulaire pour créer une table. |
| `components/TableActions.jsx` | Renommer ou supprimer une table. |
| `components/AddColumn.jsx` | Formulaire pour ajouter une colonne. |
| `components/ColumnActions.jsx` | Renommer une colonne, changer son type ou la supprimer. |
| `components/TableRows.jsx` | Affiche les lignes et les boutons Modifier et Supprimer. |
| `components/RowForm.jsx` | Formulaire commun pour ajouter une ligne ou modifier une ligne existante. |
| `components/DeleteButton.jsx` | Demande confirmation ; pour une base, demande aussi de recopier son nom. |
| `components/Feedback.jsx` | Affiche les messages de chargement, réussite et erreur. |
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

## PHP : recevoir la demande et agir sur MySQL

`backend/public/index.php` charge les classes et les routes, puis démarre FlightPHP.

Les routes sont dans `backend/src/routes/` :

| Route | Service appelé | Opérations |
| --- | --- | --- |
| `databases.php` | `DatabaseService.php` | Lister, créer et supprimer les bases personnelles. |
| `tables.php` | `TableService.php` | Lister, créer, renommer et supprimer les tables. |
| `columns.php` | `ColumnService.php` | Lire, ajouter, modifier et supprimer les colonnes. |
| `rows.php` | `RowService.php` | Lire, ajouter, modifier et supprimer les lignes. |
| `health.php` | Utilise directement la connexion PDO | Vérifier que l’API et MySQL répondent. |

Les services et les classes communes sont dans `backend/src/` :

- `Database.php` : ouvre la connexion PDO à MySQL.
- `SchemaService.php` : demande à MySQL la description réelle de la table, ses colonnes
  et sa clé primaire. Par exemple, il découvre que `id` identifie un produit.
- `SqlValidation.php` : vérifie les noms, les types, les valeurs et les confirmations.
  Il protège aussi les bases système pour éviter une mauvaise manipulation.
- `Api.php` : lit le JSON envoyé par React et prépare les réponses ou les erreurs HTTP.

`api.js` travaille dans le navigateur ; `Api.php` travaille sur le serveur PHP.

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

- `docker-compose.yaml` : organise les conteneurs frontend, backend et MySQL.
- Les `Dockerfile` : décrivent comment préparer les conteneurs frontend et backend.
- `frontend/package.json` : liste les dépendances JavaScript et les commandes Vite.
- `frontend/vite.config.js` : configure Vite et transmet les demandes `/api` au backend.
- `backend/composer.json` : liste les dépendances PHP, dont FlightPHP.
- Les fichiers `package-lock.json` et `composer.lock` enregistrent les versions des dépendances.
- `.env` : contient la configuration locale ; ne pas le publier ni afficher ses secrets.
- `README.md` : installation, commandes utiles, fonctions disponibles et limites.

Pour vérifier une fonction qui touche aux données, utiliser une base de test dédiée.
Le renommage d’une base MySQL n’est pas proposé ; cette limite est expliquée dans le README.
