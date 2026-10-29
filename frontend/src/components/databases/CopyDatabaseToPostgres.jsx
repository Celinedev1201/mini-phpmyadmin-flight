import { useId, useState } from "react";
import { request, databasePath } from "../../services/api";
import Feedback from "../common/Feedback";

export default function CopyDatabaseToPostgres({ database, onBusy }) {
  const inputId = useId();
  const [open, setOpen] = useState(false);
  const [target, setTarget] = useState("");
  const [copying, setCopying] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  async function handleSubmit(event) {
    event.preventDefault();
    if (copying) return;
    setCopying(true);
    onBusy(true);
    setError("");
    setSuccess("");
    try {
      const data = await request(`${databasePath(database)}/copy-to-postgres`, {
        method: "POST", body: { target },
      });
      setSuccess(data.message);
      setOpen(false);
    } catch (error) {
      setError(error.message);
    } finally {
      setCopying(false);
      onBusy(false);
    }
  }

  return (
    <div className="database-copy-action">
      <button className="action-button" type="button" disabled={copying}
        aria-expanded={open} aria-controls={`${inputId}-panel`}
        onClick={() => { setOpen(true); setSuccess(""); setError(""); }}>
        Copier la base vers PostgreSQL
      </button>
      {open && <form id={`${inputId}-panel`} className="create-table database-copy-form" onSubmit={handleSubmit}>
        <label htmlFor={inputId}>Nom de la nouvelle base PostgreSQL</label>
        <input id={inputId} value={target} disabled={copying} required
          maxLength={63} pattern="[a-zA-Z][a-zA-Z0-9_]{0,62}"
          onChange={(event) => setTarget(event.target.value)} />
        <p>Toutes les tables sont copiées dans le schéma public, avec 1 000 lignes maximum par table.
          Une base existante est refusée. La base MySQL est conservée.</p>
        <div className="database-copy-buttons">
        <button className="action-button" disabled={copying || !target}>
          {copying ? "Copie en cours…" : "Confirmer"}
        </button>
        <button className="action-button" type="button" disabled={copying}
          onClick={() => { setOpen(false); setError(""); }}>Annuler</button>
        </div>
      </form>}
      <Feedback error={error} success={success} />
    </div>
  );
}
