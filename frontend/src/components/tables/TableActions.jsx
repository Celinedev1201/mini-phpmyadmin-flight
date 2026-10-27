import { useState } from "react";
import { request, tablePath } from "../../services/api";
import Feedback from "../common/Feedback";
import DeleteButton from "../common/DeleteButton";
// Permet de renommer la table ou de demander sa suppression.
// database et table construisent l’adresse de la table. Le champ name commence avec son nom actuel. Le composant
// réutilise DeleteButton pour garder la confirmation de suppression.
export default function TableActions({ database, table, onChanged }) {
  const [name, setName] = useState(table);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const path = tablePath(database, table);
  // Envoie le nouveau nom de la table et avertit le parent pour actualiser la sélection.
  // Le nom saisi est transmis par PATCH à l’adresse de la table. onChanged() avertit le parent après réussite pour
  // retrouver la table sous son nouveau nom. Le catch affiche le refus du serveur.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    try {
      const data = await request(path, { method: "PATCH", body: { name } });
      onChanged(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return <div className="table-panel">
    <form className="create-table" onSubmit={handleSubmit}>
      <label htmlFor="rename-table">Renommer la table {table}</label>
      <input id="rename-table" value={name} onChange={(event) => setName(event.target.value)}
        required maxLength={64} pattern="[a-zA-Z][a-zA-Z0-9_]{0,63}" disabled={loading} />
      <button disabled={loading || name === table}>Renommer</button>
      <DeleteButton path={path} target={`la table ${table}`} onDeleted={onChanged} disabled={loading} />
      <Feedback loading={loading} error={error} />
    </form>
  </div>;
}
