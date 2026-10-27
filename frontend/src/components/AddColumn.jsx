import { useState } from "react";
import { request, tablePath, columnTypes } from "../services/api";
import Feedback from "./Feedback";
// Affiche le formulaire qui ajoute une colonne à la table choisie.
function AddColumn({ database, table, onCreated }) {
  const [name, setName] = useState("");
  const [type, setType] = useState("VARCHAR");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  // Envoie le nom et le type de la nouvelle colonne, puis demande de rafraîchir la structure.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    try {
      const data = await request(`${tablePath(database, table)}/columns`, {
        method: "POST", body: { name, type },
      });
      setName("");
      onCreated(data);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return <form className="add-column" onSubmit={handleSubmit}>
    <label htmlFor="column-name">Nom de la colonne</label>
    <input id="column-name" value={name} onChange={(event) => setName(event.target.value)}
      placeholder="Ex. : nom" required maxLength={64} pattern="[a-zA-Z][a-zA-Z0-9_]{0,63}"
      disabled={loading} />
    <label htmlFor="column-type">Type de données</label>
    <select id="column-type" value={type} onChange={(event) => setType(event.target.value)} disabled={loading}>
      {Object.entries(columnTypes).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
    </select>
    <button disabled={loading}>Ajouter la colonne</button>
    <Feedback loading={loading} error={error} />
  </form>;
}

export default AddColumn;
