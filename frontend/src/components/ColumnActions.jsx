import { useState } from "react";
import { request, tablePath, columnTypes } from "../services/api";
import DeleteButton from "./DeleteButton";
import Feedback from "./Feedback";
// Permet de renommer une colonne, de changer son type ou de demander sa suppression.
// column contient la description actuelle de la colonne. editing décide si son formulaire est visible.
// TYPE_RESTRICTION explique pourquoi certains types ne peuvent pas être changés.
export default function ColumnActions({ column, database, table, disabled, onChanged }) {
  const [editing, setEditing] = useState(false);
  const [name, setName] = useState(column.COLUMN_NAME);
  const [type, setType] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const path = `${tablePath(database, table)}/columns/${encodeURIComponent(column.COLUMN_NAME)}`;
  // Prépare les valeurs actuelles de la colonne avant d’ouvrir son formulaire.
  // Le nom actuel de la colonne est remis dans le champ et le type reste vide pour le conserver par défaut.
  // L’erreur précédente est effacée avant d’afficher le formulaire.
  function openEditor() {
    setName(column.COLUMN_NAME);
    setType("");
    setError("");
    setEditing(true);
  }
  // Enregistre le nom et le type choisis, puis ferme l’éditeur si la demande réussit.
  // La demande PATCH envoie le nom et le type choisis. L’éditeur se ferme seulement après une réussite et
  // onChanged() actualise la structure. Le catch laisse le formulaire ouvert si le serveur refuse.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    try {
      const data = await request(path, { method: "PATCH", body: { name, type } });
      setEditing(false);
      onChanged(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return <div className="column-actions">
    <button type="button" className="action-button" disabled={disabled || loading}
      onClick={openEditor}>Modifier</button>
    <DeleteButton path={path} target={`la colonne ${column.COLUMN_NAME}`} onDeleted={onChanged}
      disabled={disabled || loading} />
    {editing && <form className="edit-column" onSubmit={handleSubmit}>
      <label>Nom de la colonne
        <input value={name} onChange={(event) => setName(event.target.value)} required maxLength={64}
          pattern="[a-zA-Z][a-zA-Z0-9_]{0,63}" disabled={loading} />
      </label>
      <label>Type de données
        <select value={type} onChange={(event) => setType(event.target.value)}
          disabled={loading || Boolean(column.TYPE_RESTRICTION)}>
          <option value="">Conserver {column.COLUMN_TYPE}</option>
          {Object.entries(columnTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
        </select>
      </label>
      {column.TYPE_RESTRICTION && <p>{column.TYPE_RESTRICTION}</p>}
      <p>Le changement de type peut convertir les valeurs existantes.</p>
      <button disabled={disabled || loading}>Enregistrer la colonne</button>
      <button type="button" disabled={loading} onClick={() => setEditing(false)}>Annuler</button>
    </form>}
    <Feedback loading={loading} error={error} />
  </div>;
}
