import { useEffect, useId, useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";
// Affiche le formulaire de création d’une table dans la base sélectionnée.
function CreateTable({ database, onCreated }) {
  const formId = useId();
  const [open, setOpen] = useState(false);
  const [name, setName] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Une autre base repart avec un formulaire fermé et des champs vides.
  useEffect(() => {
    setOpen(false);
    setName("");
    setError("");
    setSuccess("");
  }, [database]);

  // Ouvre ou ferme le formulaire sans envoyer de demande au serveur.
  function toggleForm() {
    setOpen((current) => !current);
    setName("");
    setError("");
    setSuccess("");
  }

  // Crée la table demandée et avertit le composant parent pour recharger la liste.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    setSuccess("");
    try {
      const data = await request(`${databasePath(database)}/tables`, { method: "POST", body: { name } });
      setName("");
      setSuccess(data.message);
      setOpen(false);
      onCreated();
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <>
      {!open && (
        <button type="button" className="action-button" aria-expanded={false}
          aria-controls={formId} onClick={toggleForm}>
          Créer une table
        </button>
      )}
      <div id={formId} hidden={!open}>
      {open && <form className="create-table" onSubmit={handleSubmit}>
      <label htmlFor="table-name">Nouvelle table</label>
      <input id="table-name" value={name} onChange={(event) => setName(event.target.value)}
        placeholder="Ex. : produits" required maxLength={64}
        pattern="[a-zA-Z][a-zA-Z0-9_]{0,63}" disabled={loading} />
      <button disabled={loading}>Créer la table</button>
      <button type="button" onClick={toggleForm} disabled={loading}>Annuler</button>
      <Feedback loading={loading} error={error} />
      </form>}
      </div>
      <Feedback success={success} />
    </>
  );
}

export default CreateTable;
