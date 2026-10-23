import { useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";

function RestoreBackup({ database, filename, onRestored }) {
  const [target, setTarget] = useState("");
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
      // Comme pour le téléchargement, l’URL ne contient pas « .sql ».
      const backupName = encodeURIComponent(filename.slice(0, -4));

      const data = await request(
        `${databasePath(database)}/backups/${backupName}/restore`,
        {
          method: "POST",
          body: { target: target.trim() },
        },
      );

      setSuccess(`${data.message} : ${data.database}`);
      setTarget("");
      onRestored?.();
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <form className="restore-backup" onSubmit={handleSubmit}>
      <label>
        Restaurer dans une nouvelle base
        <input
          type="text"
          value={target}
          onChange={(event) => setTarget(event.target.value)}
          placeholder="Ex. : atelier_theoa_test"
          pattern="[a-zA-Z][a-zA-Z0-9_]{0,63}"
          maxLength={64}
          disabled={loading}
          required
        />
      </label>

      <button type="submit" disabled={loading}>
        {loading ? "Restauration en cours…" : "Restaurer"}
      </button>

      <Feedback error={error} success={success} />
    </form>
  );
}

export default RestoreBackup;
