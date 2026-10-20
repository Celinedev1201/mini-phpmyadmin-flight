import { useState } from "react";

function CreateDatabase({ onCreated }) {
  const [name, setName] = useState("");

  async function handleSubmit(event) {
    event.preventDefault();
    console.log("Formulaire envoyé :", name);

    try {
      const response = await fetch("/api/databases", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ name }),
      });

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
    <form className="create-database" onSubmit={handleSubmit}>
      <label htmlFor="database-name">Nouvelle base</label>

      <input
        id="database-name"
        type="text"
        value={name}
        onChange={(event) => setName(event.target.value)}
        placeholder="Ex. : atelier_theoa"
        required
      />

      <button type="submit">Créer la base</button>
    </form>
  );
}

export default CreateDatabase;
