import { useState } from "react";

function CreateTable({ database, onCreated }) {
  const [name, setName] = useState("");

  async function handleSubmit(event) {
    event.preventDefault();

    try {
      const response = await fetch(
        `/api/databases/${encodeURIComponent(database)}/tables`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ name }),
        },
      );

      if (!response.ok) {
        throw new Error(`Erreur HTTP ${response.status}`);
      }

      const data = await response.json();

      console.log("Réponse de PHP :", data);
      setName("");
      onCreated?.();
    } catch (error) {
      console.error("Envoi impossible :", error);
    }
  }

  return (
    <form className="create-table" onSubmit={handleSubmit}>
      <label htmlFor="table-name">Nouvelle table</label>

      <input
        id="table-name"
        type="text"
        value={name}
        onChange={(event) => setName(event.target.value)}
        placeholder="Ex. : produits"
        required
      />

      <button type="submit">Créer la table</button>
    </form>
  );
}

export default CreateTable;
