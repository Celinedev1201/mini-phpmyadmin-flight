# SafeBase : PostgreSQL, MySQL et automatisation

## Diagnostic du 23 octobre 2026

Les dumps PostgreSQL `2026-10-23_11-54-10_b216e39c.sql` et
`2026-10-23_12-02-29_4dd0d6a3.sql` font chacun 724 octets et ne contiennent
aucun `CREATE TABLE public.produits`. Restaurer ces fichiers ne peut pas créer
cette table. Le fichier réellement choisi lors de l’ancien essai n’est pas tracé :
on ne peut donc pas reconstituer ce clic avec certitude.
La lecture des bases confirme que `safebase_demo` contient deux produits et que
`safebase_verification` ne contient pas cette table. Ces bases n’ont pas été modifiées.

Le cycle HTTP vérifié pendant l’intégration a réussi avec un nouveau dump contenant
deux produits : structure, données, clé primaire et identité comparées ;
l’insertion suivante reçoit l’identifiant 3. Aucun changement de la commande
pg_dump ou psql n’a été nécessaire. La restauration utilise une transaction
et ON_ERROR_STOP ; un import SQL défectueux est entièrement annulé.
La base nouvellement créée reste vide en cas d’échec, pour inspection.
Les dumps proviennent du serveur local : ne pas y déposer de SQL non fiable.

## Interface et routes

Le bouton « Supprimer » demande de recopier exactement le nom de la base.
La confirmation est aussi vérifiée par l’API. La suppression conserve les dumps,
actualise la liste et retire la sélection supprimée. Les bases système, modèles
et la base configurée dans `POSTGRES_DB` sont protégées. Les connexions actives
ne sont pas interrompues : il faut les fermer avant de supprimer la base.

La liste PostgreSQL se recharge après restauration, conserve la base sélectionnée
si elle existe encore et choisit sinon la première base disponible. Le bouton
« Actualiser les bases » permet aussi de réessayer après une erreur réseau.
Le formulaire de sauvegarde est séparé de la gestion de cette liste.
Les composants d’historique et de restauration gardent `engine="mysql"` par défaut.

| Action | MySQL | PostgreSQL |
| --- | --- | --- |
| Bases | `/api/databases` | `/api/postgres/databases` |
| Supprimer (DELETE) | `/api/databases/{base}` | `/api/postgres/databases/{base}` |
| Créer/lister les dumps | `/api/databases/{base}/backups` | `/api/postgres/databases/{base}/backups` |
| Télécharger | `.../backups/{fichier}/download` | `.../backups/{fichier}/download` |
| Restaurer | `.../backups/{fichier}/restore` | `.../backups/{fichier}/restore` |

Le fichier dans l’URL est **sans `.sql`** ; PHP ajoute cette extension.
POST restore attend un objet JSON `{"target":"nouvelle_base"}`.
Un dump ancien ou petit peut être valide et ne contenir aucune table :
choisir sa version par date et fichier, puis vérifier les données restaurées.

## Sauvegardes quotidiennes

`BACKUP_DATABASE` choisit la base MySQL (défaut : `atelier_theoa`).
`PG_BACKUP_DATABASE` choisit la base PostgreSQL (défaut : `POSTGRES_DB`).
Les bases doivent déjà exister ; une seule base par moteur est configurée.
Ne pas modifier les mots de passe d’une installation existante pour ce réglage.

La tâche tourne à **12 h UTC** : 14 h à Paris en été, 13 h en hiver.
Le script privé `/run/safebase-backup.sh` reçoit les variables DB et PG, mode 0700.
Un verrou flock empêche le chevauchement des tâches automatiques.
Les deux moteurs sont tentés même si le premier échoue ; le résultat global vaut
1 si l’un échoue. Les dumps incomplets ne sont pas publiés dans l’historique.

Après changement de configuration, depuis PowerShell ou Ubuntu :

```sh
docker compose up -d --no-deps scheduler
docker compose logs --tail 20 scheduler
docker compose exec -T scheduler tail -n 30 /var/backups/safebase/cron.log
```

`cron.log` contient date UTC, moteur, base, fichier ou erreur.
Les erreurs techniques de restauration sont dans les journaux du backend.
Ne pas afficher le script privé : il contient les paramètres de connexion.
Ces journaux facilitent le diagnostic ; ils ne constituent pas un système
d’alertes avec notification, acquittement ou remontée automatique dans l’interface.
La rétention, la rotation des logs et le stockage externe restent à prévoir.

## Tests automatisés

État au **28 octobre 2026** : `backend/tests/run.php` lance les cinq tests
ci-dessous, dans l’ordre, chacun dans un processus PHP séparé. Il continue après
un test en échec et affiche le bilan final.

| Nom affiché | Ce qui est vérifié |
| --- | --- |
| API : liste des bases MySQL | `GET /api/databases` répond en HTTP 200 avec un JSON valide contenant une liste `databases`. |
| API : liste des bases PostgreSQL | `GET /api/postgres/databases` répond en HTTP 200 avec un JSON valide contenant une liste `databases`. |
| API : lecture des alertes | `GET /api/alerts` répond en HTTP 200 avec un JSON valide contenant une liste `alerts`. |
| MySQL : sauvegarde, restauration et comparaison des données | Création d’une base et d’un produit témoin, sauvegarde SQL non vide, restauration dans une autre base, puis comparaison exacte des lignes restaurées avec la valeur attendue. |
| PostgreSQL : sauvegarde, restauration et comparaison des données | Même cycle sur PostgreSQL, avec comparaison exacte des lignes restaurées avec la valeur attendue. |

Les **trois premiers tests** utilisent `tests/api.php` et font uniquement des
lectures HTTP sur `TEST_API_URL` (`http://backend:8000` dans Compose).
Une liste vide est acceptée. Ils ne vérifient pas le contenu détaillé de chaque
élément ni les routes de création, suppression ou restauration.

Les **deux derniers tests** utilisent `tests/mysql-backup-restore.php` et
`tests/postgres-backup-restore.php`. Ils appellent directement
`BackupService::create()` / `RestoreService::restore()` et
`PostgresBackupService::create()` / `PostgresRestoreService::restore()`.
Ils utilisent les vrais moteurs SQL et outils de sauvegarde/restauration,
mais ne passent ni par HTTP ni par l’interface React.

### Démarrage et résultat

```sh
docker compose up -d --build
docker compose logs tests
docker compose run --rm tests
```

La première commande construit les images et démarre les services, dont `tests`.
La deuxième affiche les résultats du service `tests`. La troisième relance la
suite dans un conteneur temporaire, supprimé à la fin grâce à `--rm`.

Compose attend les contrôles de santé de MySQL et PostgreSQL avant de démarrer
le backend. Le contrôle du backend demande `/api/databases` sur son adresse
locale. Le service `tests` attend que **les trois services soient déclarés sains**.
Les contrôles sont espacés de 5 secondes, avec un délai maximal de 5 secondes
par contrôle et 20 tentatives ; la requête du contrôle de l’API a un délai de
3 secondes. Les scripts de test n’ajoutent pas de boucle d’attente : chaque
lecture HTTP a un délai de 5 secondes.

Résultat réellement obtenu, communiqué pour cette documentation le
**28 octobre 2026** :

```text
5 tests exécutés, 0 échec(s).
```

Les tests n’ont pas été relancés lors de cette mise à jour documentaire.
Après exécution, le conteneur `tests` s’arrête normalement : code `0` si tous les
tests réussissent, code `1` si au moins un échoue. Un échec de nettoyage compte
aussi comme un échec. `restart: "no"` empêche le redémarrage automatique.
`docker compose ps -a` permet de voir ce conteneur arrêté.

**Un échec de test ne bloque pas actuellement le frontend.** Le frontend dépend
seulement du démarrage du backend ; aucune dépendance n’exige la réussite de `tests`.

### Bases temporaires et nettoyage

Chaque test de sauvegarde génère son suffixe aléatoire de huit caractères
hexadécimaux, puis utilise deux noms distincts :

| Moteur | Base source | Base cible |
| --- | --- | --- |
| MySQL | `safebase_test_mysql_<suffixe>` | `safebase_test_restore_<suffixe>` |
| PostgreSQL | `safebase_test_pg_<suffixe>` | `safebase_test_restore_pg_<suffixe>` |

La source contient une table `produits` avec une clé primaire `id` et une colonne
`nom`. La ligne témoin est `id = 1`, `nom = Produit témoin`. Après restauration,
le test lit `id` et `nom`, triés par identifiant, et exige exactement cette ligne,
sans ligne supplémentaire. La comparaison porte sur la valeur attendue ; ce
n’est pas une comparaison complète de toutes les propriétés du schéma.

Le bloc `finally` tente le nettoyage même après une erreur : suppression de la
source si sa création est confirmée, et de la cible si la restauration a retourné
un succès. La connexion de test PostgreSQL est fermée avant ces suppressions.
Si le chemin du dump est connu et que le fichier existe, le script tente de
supprimer le dump puis son dossier dans `backups/mysql/` ou `backups/postgres/`.
Il ne nettoie pas les anciens essais ni les bases ou sauvegardes personnelles.

Le nettoyage n’est pas garanti en cas d’échec :

- Si la restauration a été tentée sans retourner un succès, la cible n’est pas
  supprimée automatiquement. Son nom est signalé par « À vérifier ». Elle peut
  être vide ou partiellement importée sur MySQL ; un échec d’import PostgreSQL
  annule la transaction et laisse la base créée vide.
- Si une suppression échoue, la base, le dump ou son dossier peut rester ; le
  script signale l’échec et sort avec le code `1`.
- Si la création du dump échoue avant de retourner son chemin, le script de test
  ne nettoie pas son dossier. Les services tentent de supprimer le fichier
  `.partial`, mais un dossier vide, voire un fichier si cette suppression échoue,
  peut subsister. Une interruption brutale peut aussi empêcher le nettoyage.

## Vérifications manuelles

Pour vérifier la compilation du frontend :

```sh
docker compose exec -T frontend npm run build
```

Historique du **23 octobre 2026** : les anciens scripts de tests avaient été
retirés à la demande de la propriétaire et les bases et sauvegardes d’essai
`safebase_test_*` avaient été conservées sans nettoyage. Cette note décrit
l’ancienne intégration ; la suite actuelle est décrite ci-dessus.

Vérification manuelle de l’interface : restaurer un dump de test dans un nouveau
nom, vérifier le message de réussite, l’ajout de la base dans la liste et le maintien
de la sélection source. Contrôler le lien de téléchargement des deux moteurs.
Le passage horaire réel de cron doit être observé sur la prochaine échéance ;
le lancement manuel de sa commande vérifie l’exécution sans attendre midi UTC.
Le scheduler actif a été recréé avec cette configuration. Le lancement manuel de
sa commande a produit deux succès dans `cron.log` le 23 octobre 2026 à 13:15:24 UTC,
pour `atelier_theoa` et `safebase_demo`. La restauration via l’interface a aussi été
vérifiée : nouvelle base ajoutée et sélection source conservée. Les vérifications
effectuées avant le retrait des scripts, la syntaxe PHP et `git diff --check`
avaient réussi sur Docker Desktop Windows.

## Exigences du TP restant à couvrir

- Ajout et gestion de connexions via l’API/interface : ici, serveurs configurés par environnement.
- Véritable surveillance et notifications d’échec automatique.
- Étendre les tests automatisés aux routes de modification et aux parcours de
  l’interface ; ajouter des tests unitaires des composants et services et une
  couverture de sécurité plus complète,
  intégration continue et validation sur un hôte Ubuntu natif.
- Éléments de soutenance et dossier : besoins, maquettes, parcours, modèles de données,
  justification d’architecture, organisation du travail et procédure de déploiement.
- Publication GitHub à décider par la propriétaire ; aucun commit/push automatique.

Le CRUD PostgreSQL complet ne fait pas partie de cette étape.
