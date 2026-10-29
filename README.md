# Mini phpMyAdmin — FlightPHP & React

Projet pédagogique de Théoa : gérer MySQL et sauvegarder/restaurer MySQL et PostgreSQL depuis React.
Stack : React/Vite, PHP 8.3/FlightPHP/PDO, MySQL 8.4, PostgreSQL 17 et Docker Compose.
Le design conserve les couleurs ivoire, prune, mauve et doré.

## Prérequis

- Git : https://git-scm.com/downloads
- Docker et Docker Compose
- Ports disponibles : 5173, 8000, 3307 et 5433

### Installation sous Windows

Documentation officielle :
https://docs.docker.com/desktop/setup/install/windows-install/

1. Ouvrir PowerShell **en administrateur** et lancer :

   ```powershell
   wsl --install --no-distribution
   ```

2. Redémarrer Windows si demandé.
3. Télécharger **Docker Desktop for Windows** depuis la page officielle :
   - **x86_64** pour les processeurs Intel et AMD.
   - **Arm** pour les processeurs ARM.
4. Exécuter l’installateur et sélectionner **Use WSL 2 instead of Hyper-V** si cette option apparaît.
5. Ouvrir Docker Desktop et attendre **Engine running**.
6. Fermer et rouvrir VS Code et les terminaux déjà ouverts.

L’installation d’une distribution Ubuntu séparée n’est pas nécessaire.

### Installation sous Ubuntu

Documentation officielle :
https://docs.docker.com/engine/install/ubuntu/

1. Suivre la section **Install using the apt repository**.
2. Installer les paquets indiqués, dont `docker-compose-plugin`.
3. Vérifier que le moteur Docker est démarré.

Pour utiliser Docker sans `sudo`, suivre **Manage Docker as a non-root user** :
https://docs.docker.com/engine/install/linux-postinstall/

L’ajout au groupe `docker` accorde des privilèges équivalents à root.
Se déconnecter puis se reconnecter pour appliquer ce changement.

### Autres distributions Linux

Choisir la procédure correspondant à sa distribution :
https://docs.docker.com/engine/install/

### Vérifier l’installation

Dans un nouveau terminal :

```bash
docker --version
docker compose version
docker info
```

La dernière commande doit afficher les informations du client
et du serveur sans erreur de connexion.

PHP, Composer, Node.js et MySQL sont fournis par Docker.
Il n’est pas nécessaire de les installer directement sur l’ordinateur.

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/Celinedev1201/mini-phpmyadmin-flight.git
cd mini-phpmyadmin-flight
```

Toutes les commandes suivantes s’exécutent à la racine du projet,
dans le dossier contenant `docker-compose.yaml`.

### 2. Créer la configuration locale

Copier `.env.exemple` vers `.env` (`Copy-Item .env.exemple .env` dans PowerShell,
`cp .env.exemple .env` dans Ubuntu), uniquement si `.env` n’existe pas déjà.
Choisir les mots de passe locaux avant le premier démarrage.
`POSTGRES_DB`, `POSTGRES_USER` et `POSTGRES_PASSWORD` initialisent PostgreSQL.
`DB_HOST=mysql`, `DB_PORT=3306` et `DB_USER=root` configurent l’accès MySQL.
Les paramètres PG_HOST/PG_PORT/PG_DATABASE/PG_USER/PG_PASSWORD sont transmis
par Compose au backend et au scheduler. Ne jamais publier les secrets.
Les mots de passe d’un volume déjà initialisé ne changent pas en modifiant `.env`.

Le fichier `.env` est ignoré par Git. Pour le vérifier :

```bash
git check-ignore .env
```

Le résultat attendu est `.env`.

### 3. Construire les images

```bash
docker compose build
```

Cette commande construit les images du backend et du frontend
à partir de leurs Dockerfile.

Les dépendances JavaScript sont installées pendant la construction
de l’image frontend.

### 4. Installer les dépendances PHP

```bash
docker compose run --rm --no-deps backend composer install
```

Cette commande utilise un conteneur temporaire pour installer les
dépendances définies dans `composer.lock`.

Le dossier `backend/vendor` est créé dans le dossier local partagé.
Le conteneur temporaire est ensuite supprimé.

### 5. Démarrer les services

```bash
docker compose up -d --build
```

Au premier lancement, Docker télécharge l’image MySQL et initialise
son stockage.

Le backend attend que MySQL et PostgreSQL soient déclarés disponibles par leurs
contrôles de santé. Le service `tests` attend ensuite aussi celui de l’API,
puis lance automatiquement les six tests de `backend/tests/run.php`.

### 6. Vérifier les conteneurs

```bash
docker compose ps
```

Résultat attendu :

- MySQL et PostgreSQL : `Up` et `healthy`
- Scheduler : `Up`
- Backend : `Up` et `healthy`
- Frontend : `Up`
- Tests : `Exited (0)` après réussite ; cet arrêt est normal.

Utiliser `docker compose ps -a` pour voir aussi le conteneur `tests` arrêté.

## Accès à l’application

| Service            | Adresse                                   |
| ------------------ | ----------------------------------------- |
| Interface React    | http://localhost:5173                     |
| API FlightPHP      | http://localhost:8000                     |
| Vérification MySQL | http://localhost:8000/api/database/status |
| Liste des bases    | http://localhost:8000/api/databases       |

L’interface doit afficher le nombre de bases et leurs noms.

Les quatre bases système suivantes sont masquées et protégées par l’application :

- information_schema
- mysql
- performance_schema
- sys

## Communication entre les services

Le navigateur demande `/api/databases` au serveur Vite.

Le proxy de développement Vite transmet la requête à
`http://backend:8000`.

L’API PHP contacte MySQL à l’adresse `mysql:3306`, puis renvoie
les résultats au navigateur au format JSON.

Depuis l’ordinateur, MySQL est accessible sur `localhost:3307`.
Entre les conteneurs, il reste accessible sur `mysql:3306`.

## Commandes utiles

### Démarrer ou reprendre le projet

```bash
docker compose up -d
```

### Afficher l’état des conteneurs

```bash
docker compose ps -a
```

### Consulter les logs

```bash
docker compose logs backend
docker compose logs frontend
docker compose logs mysql
```

### Suivre les logs du backend

```bash
docker compose logs -f backend
```

### Arrêter sans supprimer les conteneurs

```bash
docker compose stop
```

### Supprimer les conteneurs et le réseau du projet

```bash
docker compose down
```

Les volumes nommés sont conservés par défaut.

### Reconstruire après modification d’un Dockerfile

```bash
docker compose up -d --build
```

### Vérifier la compilation du frontend

```bash
docker compose exec frontend npm run build
```

Les fichiers compilés sont générés dans `frontend/dist`.

## Stockage des données

Le volume nommé `mysql_data` conserve les données MySQL
indépendamment du conteneur.

Chaque ordinateur possède son propre stockage Docker :
cloner le dépôt ne copie pas les bases d’un autre ordinateur.

Attention : `docker compose down -v` supprime également les volumes
du projet, dont les données MySQL.

Le mot de passe initial MySQL est appliqué lors de la première
initialisation du volume. Modifier ensuite `.env` ne modifie pas
automatiquement le mot de passe déjà enregistré dans MySQL.

## Dépannage

### La commande docker est introuvable

Vérifier que Docker est installé.

Après une installation sous Windows, fermer puis rouvrir le terminal.
Si le terminal est intégré à VS Code, fermer complètement VS Code
et le relancer pour actualiser son environnement.

### Le client Docker ne peut pas joindre le moteur

Sous Windows, ouvrir Docker Desktop et attendre que le moteur
soit démarré.

Relancer ensuite :

```bash
docker info
```

### Le backend ne trouve pas vendor/autoload.php

Installer les dépendances PHP :

```bash
docker compose run --rm --no-deps backend composer install
```

### Un service s’arrête ou ne répond pas

Consulter son état et ses logs :

```bash
docker compose ps -a
docker compose logs backend
```

Remplacer `backend` par `frontend` ou `mysql` selon le service concerné.

## Fonctions disponibles

- Lister les bases personnelles, créer une base et afficher ses tables et vues.
- Supprimer une base après confirmation et saisie exacte de son nom.
- Créer, renommer et supprimer une table avec confirmation de suppression.
- Consulter la structure, ajouter, renommer et supprimer une colonne.
- Changer un type vers VARCHAR(255), TEXT, INT, DECIMAL(10,2) ou DATE.
- Insérer et afficher les 50 premières lignes, distinguer NULL d’un texte vide.
- Modifier les valeurs d’une ligne, y compris sa clé, et supprimer avec confirmation.
- Afficher les chargements, réussites et erreurs ; bloquer les doubles soumissions.
- Actualiser après chaque réussite ; conserver la sélection renommée et retirer celle supprimée.

## Règles et limites

Les noms suivent la convention existante : commencer par une lettre ASCII,
puis lettres, chiffres ou underscores, au maximum 64 caractères.
Les bases système sont masquées et protégées sur le serveur, même par appel direct.
Les valeurs des lignes et les recherches de métadonnées utilisent des paramètres PDO.

Une modification/suppression de ligne exige la vraie clé primaire complète,
simple ou composite, vérifiée côté serveur. Aucun numéro de position n’est utilisé.
Les valeurs BIGINT/DECIMAL voyagent en chaînes pour préserver leur précision.
Sans clé exploitable, les boutons sont désactivés avec une explication.
Les clés binaires, flottantes, invisibles ou aux noms non pris en charge sont refusées.
Les mutations de lignes nécessitent InnoDB pour le verrou et la transaction.
Les vues restent consultables uniquement. Il n’y a pas encore de pagination.

Un renommage seul conserve la définition de colonne avec RENAME COLUMN.
Un changement de type conserve NULL, défaut simple, commentaire, auto-incrément
compatible, UNSIGNED numérique et collation textuelle compatible.
Les types et attributs complexes sont refusés pour éviter de perdre leur définition.
Le mode SQL strict refuse les conversions invalides ; une conversion valide peut
transformer les valeurs. Les contraintes MySQL restent actives, sans suppression forcée.
Les ALTER/DROP sont irréversibles dans l’interface et peuvent verrouiller les tables.
Les références SQL dans les vues, routines ou applications peuvent nécessiter une adaptation.

MySQL ne possède pas de commande RENAME DATABASE : cette fonction n’est pas proposée.
Une migration fiable demanderait un inventaire des tables, vues, index, contraintes,
triggers, routines, événements, droits, encodages et références au schéma source ;
une sauvegarde vérifiée, une stratégie de cohérence pendant les écritures,
une recréation contrôlée des objets et droits, une copie vérifiée des données,
des tests de dépendances et un plan de bascule et de retour arrière.
Aucune copie suivie d’une suppression n’est improvisée ici.
Voir les références MySQL : [ALTER TABLE](https://dev.mysql.com/doc/refman/8.4/en/alter-table.html)
et [RENAME TABLE](https://dev.mysql.com/doc/refman/8.4/en/rename-table.html).

## Où retrouver le code ?

Pour une explication simple des fichiers et un exemple d’action, lire le
[mémo d’organisation](MEMO_ORGANISATION.md).

Le code utilise directement les `useState`, `useEffect` et fonctions asynchrones
habituels de React, comme dans la première version du projet. Aucun hook personnalisé.

| Ce que je veux modifier | Fichier dans `frontend/src/` |
| --- | --- |
| Le menu des bases et la page d’accueil | `App.jsx` |
| La liste des tables et la table sélectionnée | `components/DatabaseExplorer.jsx` |
| La structure et la liste des colonnes | `components/TableStructure.jsx` |
| Le formulaire de création d’une base ou table | `components/CreateDatabase.jsx`, `CreateTable.jsx` |
| Ajouter ou modifier une colonne | `components/AddColumn.jsx`, `ColumnActions.jsx` |
| Renommer ou supprimer une table | `components/TableActions.jsx` |
| Afficher les lignes et leurs boutons | `components/TableRows.jsx` |
| Ajouter ou modifier les valeurs d’une ligne | `components/RowForm.jsx` |
| Les confirmations de suppression (question et saisie du nom) | `components/DeleteButton.jsx` |
| Les messages et les appels au PHP | `components/Feedback.jsx`, `services/api.js` |

Tous les styles sont réunis dans `frontend/src/index.css`, avec les couleurs et classes existantes.
Les fichiers source modifiés pour cette intégration restent sous 100 lignes.
Les fichiers historiques non modifiés, le CSS et la documentation peuvent dépasser cette limite.

Côté PHP, ouvrir d’abord la route de la fonction dans `backend/src/routes/`.
Elle appelle le service du même nom dans `backend/src/` : `databases.php` appelle
`DatabaseService.php`, puis même principe pour tables, colonnes et lignes.
Le SQL de modification et suppression des lignes est réuni dans `RowService.php`.
`SchemaService.php` décrit les colonnes et la clé primaire de la table.
`SqlValidation.php` vérifie les noms, les types et les valeurs reçus.
`Api.php` contient le traitement commun des réponses et erreurs HTTP.
`Database.php` contient la connexion PDO.

Exemple : le bouton « Modifier » d’une ligne est dans `TableRows.jsx`.
Il ouvre `RowForm.jsx`. Le formulaire envoie les valeurs par `services/api.js`.
La demande arrive dans `routes/rows.php`, qui appelle `RowService::update()`.
Après la réussite, `TableRows.jsx` recharge les lignes.

## Vérifications

### Tests automatisés SafeBase

Au démarrage avec `docker compose up -d --build`, le service `tests` exécute
les tests après disponibilité de l’API, de MySQL et de PostgreSQL.
Pour consulter le résultat ou relancer la suite :

```bash
docker compose logs tests
docker compose run --rm tests
```

La seconde commande crée un conteneur temporaire, supprimé après son exécution.
Résultat exécuté le 29 octobre 2026 avec `docker compose exec -T backend php tests/run.php` : **6 tests exécutés, 0 échec(s).**

La suite vérifie d’abord la copie complète MySQL → PostgreSQL, puis trois lectures HTTP (bases MySQL, bases PostgreSQL et alertes),
puis deux sauvegardes/restaurations via les services PHP, avec une donnée témoin.
Ces deux derniers tests créent des bases temporaires à suffixe aléatoire et tentent
leur nettoyage ainsi que celui de leur sauvegarde. Des éléments peuvent rester
si une opération échoue : voir le [détail des tests et du nettoyage](docs/SAFEBASE.md#tests-automatisés).

Le conteneur s’arrête après la suite : code `0` si tout réussit, `1` si au moins
un test échoue, y compris lors du nettoyage. Il ne redémarre pas automatiquement.
Un échec de test ne bloque pas actuellement le frontend : celui-ci dépend du
backend, pas de la réussite du service `tests`.

### Syntaxe PHP, compilation et essais manuels

```bash
docker compose exec -T backend sh -c 'find src public -name "*.php" -exec php -l {} \;'
docker compose exec -T frontend npm run build
```

Pour vérifier les opérations, utiliser uniquement une base de test dédiée.
Vérifier la modification et la suppression des lignes, colonnes, tables et bases,
les confirmations, les messages et l’actualisation des listes.
Vérifier aussi le refus des bases système et des actions sans clé primaire exploitable.
Ne jamais utiliser les données existantes pour ces essais.

Usage local pédagogique, sans authentification : ne pas exposer ces serveurs sur Internet.
La production demanderait authentification, contrôle des droits et configuration du proxy.

## SafeBase : sauvegardes des deux moteurs

Les sauvegardes SQL, l’historique, le téléchargement et la restauration dans une
nouvelle base sont disponibles pour MySQL et PostgreSQL. Une base existante est
refusée. Les fichiers restent dans `backups/mysql` et `backups/postgres`, hors Git.
Le PostgreSQL de cette intégration ne propose pas de CRUD complet.

Voir [le guide SafeBase](docs/SAFEBASE.md) pour cron, les tests, le diagnostic
et les exigences du TP restant à couvrir.

### Copier une base MySQL vers PostgreSQL

Sélectionner une base MySQL, cliquer sur « Copier la base vers PostgreSQL »
près de la sauvegarde, saisir un **nouveau** nom PostgreSQL et confirmer.
Le résultat indique la destination, le nombre de tables et de lignes copiées.
La route est `POST /api/databases/{database}/copy-to-postgres`, avec
`{"target":"nouvelle_base"}`. Le parcours de copie par table est retiré.

La copie accepte uniquement les tables InnoDB simples : INT, INT UNSIGNED
(BIGINT avec contrainte 0…4294967295), VARCHAR(n), TEXT, DECIMAL(p,s) et DATE.
Noms et ordre des colonnes, NULL, NOT NULL et clés primaires sont conservés.
AUTO_INCREMENT devient une identité dont le compteur reprend au prochain numéro
MySQL (y compris pour une table vide). Les noms doivent respecter le format de
l’application et la limite PostgreSQL de 63 octets.

Toute vue, clé étrangère, contrainte supplémentaire, index secondaire, déclencheur,
colonne calculée, type ou défaut non pris en charge entraîne un refus explicite
avec le nom de la table. Maximum **1 000 lignes par table**, sans troncature.
Les caractères NUL, non représentables dans les textes PostgreSQL, sont refusés.

Les données et métadonnées MySQL sont lues sous `FLUSH TABLES WITH READ LOCK` :
le compte doit avoir `RELOAD` ou `FLUSH_TABLES`. Ce verrou bloque brièvement
les écritures et changements de structure sur le serveur MySQL pendant la lecture ;
la source reste intacte. La copie est conservée en mémoire avant création de la cible.
Le compte PostgreSQL doit pouvoir créer des bases. Une destination existante
est toujours refusée. Toutes les tables et données sont écrites dans une seule
transaction. En cas d’échec, seule la nouvelle base créée est supprimée ; si ce
nettoyage échoue, son nom est affiché pour une intervention manuelle.

Le test `backend/tests/database-copy.php`, intégré au lanceur, compare deux tables
(dont une vide), les accents, NULL, types, clés et compteurs ; il vérifie aussi les
refus et la suppression de la destination après un échec pendant la copie.
Les parcours visuels React et l’échec du nettoyage lui-même ne sont pas automatisés.
