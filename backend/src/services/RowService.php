<?php

final class RowService
{
    private SchemaService $schema;
    // Prépare les informations de la table pour les actions sur ses lignes.
    // Les noms reçus servent à créer un SchemaService. Il retrouve la table et conserve sa connexion pour lire ou
    // modifier les lignes.
    public function __construct(string $database, string $table)
    {
        $this->schema = new SchemaService($database, $table);
    }
    // Lit au maximum 50 lignes et indique comment identifier chaque ligne.
    // SELECT lit au maximum 50 lignes pour limiter la quantité affichée. primaryKey() ajoute les noms des colonnes
    // qui identifient une ligne et la raison éventuelle qui empêche sa modification.
    public function list(): array
    {
        $rows = $this->schema->pdo->query("SELECT * FROM {$this->schema->qualified} LIMIT 50")->fetchAll();
        return ['rows' => $rows, ...$this->schema->primaryKey()];
    }
    // Vérifie les valeurs reçues puis ajoute une ligne avec une requête préparée.
    // rowValues() vérifie les colonnes envoyées. Les points d’interrogation remplacent les valeurs dans la requête
    // préparée. execute() transmet les valeurs séparément du SQL, puis lastInsertId() récupère l’identifiant créé.
    public function create(array $data): array
    {
        $this->schema->requireTable();
        $values = SqlValidation::rowValues($this->schema, $data['values'] ?? null, true);
        $columns = implode(', ', array_map(fn($name) => "`$name`", array_keys($values)));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $statement = $this->schema->pdo->prepare(
            "INSERT INTO {$this->schema->qualified} ($columns) VALUES ($placeholders)"
        );
        $statement->execute(array_values($values));
        return ['message' => 'Ligne ajoutée avec succès', 'id' => $this->schema->pdo->lastInsertId()];
    }
    // Demande la modification de la ligne identifiée par sa clé primaire.
    // La méthode transmet les données à change() avec false. Ce choix demande une modification et laisse change()
    // vérifier la clé et les valeurs reçues.
    public function update(array $data): array
    {
        return $this->change($data, false);
    }
    // Demande la suppression de la ligne identifiée par sa clé primaire.
    // La méthode transmet les données à change() avec true. Ce choix demande une suppression et laisse change()
    // contrôler la clé et la confirmation.
    public function delete(array $data): array
    {
        return $this->change($data, true);
    }

    // La clé complète et la transaction limitent l’action à la ligne demandée.
    // Modifie ou supprime une seule ligne, avec annulation si une étape échoue.
    // La clé primaire sert à retrouver une seule ligne. La transaction verrouille cette ligne pendant l’action.
    // commit() valide le résultat ; si une erreur survient, rollBack() annule les changements de la transaction.
    private function change(array $data, bool $deleting): array
    {
        $key = $this->schema->validateKey($data['key'] ?? null);
        if ($deleting) {
            SqlValidation::confirmed($data, '');
            $values = [];
        } else {
            $values = SqlValidation::rowValues($this->schema, $data['values'] ?? null, false);
        }
        $where = implode(' AND ', array_map(fn($name) => "`$name` = ?", array_keys($key)));
        $pdo = $this->schema->pdo;
        // La transaction permet de tout annuler si la modification ne peut pas se terminer.
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare("SELECT * FROM {$this->schema->qualified} WHERE $where FOR UPDATE");
            $statement->execute(array_values($key));
            $row = $statement->fetch();
            if (!$row) {
                throw new HttpError('Cette ligne n’existe plus. Actualisez les données.', 404);
            }
            // MySQL peut convertir « 1abc » en 1 : on exige aussi la valeur exacte lue.
            foreach ($key as $name => $value) {
                if ((string) $row[$name] !== (string) $value) {
                    throw new HttpError('La clé primaire ne correspond pas exactement à la ligne.', 404);
                }
            }
            $sql = "DELETE FROM {$this->schema->qualified} WHERE $where";
            if (!$deleting) {
                $set = implode(', ', array_map(fn($name) => "`$name` = ?", array_keys($values)));
                $sql = "UPDATE {$this->schema->qualified} SET $set WHERE $where";
            }
            $statement = $pdo->prepare($sql);
            $statement->execute([...array_values($values), ...array_values($key)]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
        return ['message' => $deleting ? 'Ligne supprimée avec succès' : 'Ligne modifiée avec succès'];
    }
}
