import { useState } from "react";
import { request } from "../services/api";
import Feedback from "./Feedback";
// Permet de choisir une base PostgreSQL puis de lancer sa sauvegarde.
function PostgresBackupForm({ database, databases, loading, onSelect, onSaved }) {
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Demande une sauvegarde au serveur et avertit le parent pour actualiser l’historique.
  async function handleBackup(event) {
    event.preventDefault();
    if (!database || saving) return;
    setSaving(true);
    setError("");
    setSuccess("");
    try {
      const name = encodeURIComponent(database);
      const data = await request(`/api/postgres/databases/${name}/backups`, {
        method: "POST",
      });
      setSuccess(`${data.message} : ${data.filename}`);
      onSaved();
    } catch (error) {
      setError(error.message);
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <form className="add-column" onSubmit={handleBackup}>
        <label htmlFor="postgres-database">Base PostgreSQL</label>
        <select id="postgres-database" value={database}
          disabled={loading || saving || databases.length === 0}
          onChange={(event) => {
            onSelect(event.target.value);
            setSuccess("");
            setError("");
          }}>
          {databases.map((name) => (
            <option key={name} value={name}>{name}</option>
          ))}
        </select>
        <button type="submit" disabled={loading || saving || !database}>
          {saving ? "Sauvegarde en cours…" : "Sauvegarder la base"}
        </button>
      </form>
      <Feedback error={error} success={success} />
    </>
  );
}

export default PostgresBackupForm;
