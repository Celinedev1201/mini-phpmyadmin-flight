import { useState } from "react";
import { request } from "../../services/api";
import Feedback from "../common/Feedback";
// Affiche le formulaire de création MySQL seulement quand on ouvre cette partie.
// open garde le formulaire fermé au départ. name contient le nom saisi et onCreated permet au parent
// d’actualiser les bases. Les messages indiquent si la demande a réussi ou échoué.
function CreateDatabase({ onCreated }) {
  const [open, setOpen] = useState(false);
  const [name, setName] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Crée la base demandée, vide le champ et recharge la liste après une réussite.
  // Le nom saisi est envoyé avec POST. Après réussite, le champ est vidé, le message est affiché et onCreated()
  // recharge les bases. Si la demande échoue, le nom reste saisi et le catch affiche l’erreur.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    setSuccess("");
    try {
      const data = await request("/api/databases", { method: "POST", body: { name } });
      setName("");
      setSuccess(data.message);
      onCreated();
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="disclosure">
      <button type="button" className="action-button" aria-expanded={open}
        aria-controls="create-database-panel" onClick={() => setOpen((value) => !value)}>
        {open ? "Fermer la création" : "Créer une base"}
      </button>
      <div id="create-database-panel" hidden={!open}>
    <form className="create-database" onSubmit={handleSubmit}>
      <label htmlFor="database-name">Nouvelle base</label>
      <input id="database-name" value={name} onChange={(event) => setName(event.target.value)}
        placeholder="Ex. : atelier_theoa" required maxLength={64}
        pattern="[a-zA-Z][a-zA-Z0-9_]{0,63}" disabled={loading} />
      <button disabled={loading}>Créer la base</button>
      <Feedback loading={loading} error={error} success={success} />
    </form>
      </div>
    </div>
  );
}

export default CreateDatabase;
