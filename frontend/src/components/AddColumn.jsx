import { useState } from "react";

function AddColumn({ database, table, onCreated }) {
  const [name, setName] = useState("");
  const [type, setType] = useState("VARCHAR");

  async function handleSubmit(event) {
    event.preventDefault();

    try {
      const response = await fetch(
        `/api/databases/${encodeURIComponent(database)}/tables/${encodeURIComponent(table)}/columns`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ name, type }),
        },
      );

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `Erreur HTTP ${response.status}`);
      }

      console.log("Réponse de PHP :", data);
      setName("");
      onCreated?.();
    } catch (error) {
      console.error("Ajout impossible :", error);
    }
  }

  return (
    <form className="add-column" onSubmit={handleSubmit}>
      <label htmlFor="column-name">Nom de la colonne</label>
      <input
        id="column-name"
        type="text"
        value={name}
        onChange={(event) => setName(event.target.value)}
        placeholder="Ex. : nom"
        required
      />

      <label htmlFor="column-type">Type de données</label>
      <select
        id="column-type"
        value={type}
        onChange={(event) => setType(event.target.value)}
      >
        <option value="VARCHAR">Texte court — VARCHAR(255)</option>
        <option value="TEXT">Texte long — TEXT</option>
        <option value="INT">Nombre entier — INT</option>
        <option value="DECIMAL">Nombre décimal — DECIMAL(10,2)</option>
        <option value="DATE">Date — DATE</option>
      </select>

      <button type="submit">Ajouter la colonne</button>
    </form>
  );
}

export default AddColumn;
