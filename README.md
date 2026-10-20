# Mini phpMyAdmin — FlightPHP & React

Projet pédagogique d’interface de gestion de bases MySQL.

L’application affiche actuellement la liste des bases disponibles.
Elle utilise une API FlightPHP et une interface React.

## Technologies

- PHP 8.3 et FlightPHP
- React et Vite
- MySQL 8.4
- Docker et Docker Compose

## Prérequis

- Git : https://git-scm.com/downloads
- Docker et Docker Compose
- Ports disponibles : 5173, 8000 et 3307

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

Créer un fichier `.env` à la racine :

```dotenv
MYSQL_ROOT_PASSWORD=MiniDev2026Local
DB_HOST=mysql
DB_PORT=3306
DB_USER=root
```

Le mot de passe ci-dessus est un exemple pour le développement local.

- `MYSQL_ROOT_PASSWORD` : mot de passe initial du compte root MySQL,
  également transmis au backend.
- `DB_HOST` : nom du service MySQL sur le réseau Docker.
- `DB_PORT` : port interne de MySQL.
- `DB_USER` : compte utilisé par le backend.

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
docker compose up -d
```

Au premier lancement, Docker télécharge l’image MySQL et initialise
son stockage.

Le backend attend que le contrôle de santé de MySQL réussisse.

### 6. Vérifier les conteneurs

```bash
docker compose ps
```

Résultat attendu :

- MySQL : `Up` et `healthy`
- Backend : `Up`
- Frontend : `Up`

## Accès à l’application

| Service            | Adresse                                   |
| ------------------ | ----------------------------------------- |
| Interface React    | http://localhost:5173                     |
| API FlightPHP      | http://localhost:8000                     |
| Vérification MySQL | http://localhost:8000/api/database/status |
| Liste des bases    | http://localhost:8000/api/databases       |

L’interface doit afficher le nombre de bases et leurs noms.

Sur une installation MySQL neuve, les quatre bases système sont :

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

## Limites actuelles

Cette configuration sert au développement local.

Le frontend utilise le serveur de développement Vite et le backend
le serveur de développement PHP.

Le proxy Vite configuré dans `server.proxy` ne fait pas partie des
fichiers compilés. Un déploiement en production nécessitera une
configuration adaptée pour servir le frontend et joindre l’API.
