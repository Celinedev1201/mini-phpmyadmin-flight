import { useState } from "react";
import { request } from "../services/api";
import Feedback from "./Feedback";

function CreateDatabase({ onCreated }) {
  const [open, setOpen] = useState(false);
  const [name, setName] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

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
