import { useState } from "react";
import { request } from "../services/api";
import Feedback from "./Feedback";
// Permet de choisir une base PostgreSQL puis de lancer sa sauvegarde.
// La base sélectionnée et les choix viennent du parent. onSelect lui transmet le nouveau choix et onSaved lui
// signale une sauvegarde terminée. saving bloque le bouton pendant la demande.
function PostgresBackupForm({ database, databases, loading, onSelect, onSaved }) {
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Demande une sauvegarde au serveur et avertit le parent pour actualiser l’historique.
  // La demande POST lance la sauvegarde de la base sélectionnée. Pendant son traitement, loading ou saving bloque
  // les doubles clics. Après réussite, le message est affiché et le parent est prévenu pour recharger
  // l’historique.
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
