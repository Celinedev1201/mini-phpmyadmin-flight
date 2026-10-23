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

## Vérifications manuelles

Pour vérifier la compilation du frontend :

```sh
docker compose exec -T frontend npm run build
```

Les scripts de tests automatiques ajoutés pendant l’intégration ont été retirés
à la demande de la propriétaire. Les bases et sauvegardes d’essai `safebase_test_*`
sont conservées ; aucun nettoyage des données n’a été effectué.

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
- Tests fonctionnels automatisés (scripts retirés), tests unitaires des composants
  et services, couverture de sécurité plus complète,
  intégration continue et validation sur un hôte Ubuntu natif.
- Éléments de soutenance et dossier : besoins, maquettes, parcours, modèles de données,
  justification d’architecture, organisation du travail et procédure de déploiement.
- Publication GitHub à décider par la propriétaire ; aucun commit/push automatique.

Le CRUD PostgreSQL complet ne fait pas partie de cette étape.
