import { useState } from "react";
import { request, tablePath } from "../services/api";
import Feedback from "./Feedback";

// Le même formulaire sert à ajouter une ligne ou à modifier la ligne reçue.
// Affiche les champs pour ajouter une ligne ou modifier les valeurs d’une ligne existante.
// La présence de row indique une modification ; son absence indique un ajout. Les colonnes calculées restent
// gérées par SQL. Les champs modifiés sont séparés des anciennes valeurs pour envoyer seulement les changements.
function RowForm({ database, table, columns, row, rowKey, onSaved, onCancel }) {
  const editing = Boolean(row);
  const [values, setValues] = useState(row || {});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Les colonnes calculées restent gérées par MySQL, comme les nouveaux identifiants automatiques.
  const editableColumns = columns.filter((column) => {
    if (column.GENERATION_EXPRESSION) return false;
    return editing || !column.EXTRA.includes("auto_increment");
  });

  // En modification, on envoie uniquement les champs réellement changés.
  const changedValues = {};
  for (const column of editableColumns) {
    const name = column.COLUMN_NAME;
    if (editing && values[name] !== row[name]) changedValues[name] = values[name];
  }
  // Met à jour un seul champ de la ligne en conservant les autres valeurs.
  // Le nom indique quel champ modifier. La copie des anciennes valeurs conserve tous les autres champs. La valeur
  // peut aussi être null, ce qui représente une absence de valeur dans SQL.
  function changeValue(name, value) {
    setValues((previous) => ({ ...previous, [name]: value }));
  }
  // Ajoute une ligne ou enregistre les champs modifiés en utilisant sa clé d’origine.
  // Le formulaire envoie POST pour une nouvelle ligne ou PATCH pour une modification. En modification, seules les
  // valeurs changées et la clé d’origine sont envoyées. onSaved() permet ensuite au parent de recharger les
  // données.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    setSuccess("");
    const body = { values: editing ? changedValues : values };
    if (editing) body.key = rowKey;
    try {
      const data = await request(`${tablePath(database, table)}/rows`, {
        method: editing ? "PATCH" : "POST", body,
      });
      if (!editing) setValues({});
      setSuccess(data.message);
      onSaved(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <form className="insert-row" onSubmit={handleSubmit}>
      <h3>{editing ? "Modifier la ligne" : "Ajouter une ligne"}</h3>
      {editing && <p className="row-identity">Clé primaire originale : {JSON.stringify(rowKey)}</p>}
      {editableColumns.map((column) => {
        const name = column.COLUMN_NAME;
        const id = `${editing ? "edit" : "insert"}-${name}`;
        return (
          <div key={name}>
            <label htmlFor={id}>{name}</label>
            <input id={id} value={values[name] ?? ""} disabled={loading || values[name] === null}
              onChange={(event) => changeValue(name, event.target.value)} />
            {column.IS_NULLABLE === "YES" && (
              <label className="null-choice">
                <input type="checkbox" checked={values[name] === null} disabled={loading}
                  onChange={(event) => changeValue(name, event.target.checked ? null : "")} /> NULL
              </label>
            )}
          </div>
        );
      })}
      <button disabled={loading || editableColumns.length === 0 || (editing && Object.keys(changedValues).length === 0)}>
        {editing ? "Enregistrer la ligne" : "Ajouter la ligne"}
      </button>
      {editing && <button type="button" disabled={loading} onClick={onCancel}>Annuler</button>}
      <Feedback loading={loading} error={error} success={success} />
    </form>
  );
}

export default RowForm;
