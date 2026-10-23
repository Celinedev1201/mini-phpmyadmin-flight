import { useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";

function RestoreBackup({ database, filename, onRestored, engine = "mysql" }) {
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
      // Chaque moteur utilise ses propres routes.
      const basePath =
        engine === "postgres"
          ? `/api/postgres/databases/${encodeURIComponent(database)}`
          : databasePath(database);

      const backupName = encodeURIComponent(filename.slice(0, -4));

      const data = await request(`${basePath}/backups/${backupName}/restore`, {
        method: "POST",
        body: { target: target.trim() },
      });

      setSuccess(`${data.message} : ${data.database}`);
      setTarget("");
      onRestored?.(data);
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
          placeholder={
            engine === "postgres"
              ? "Ex. : safebase_test"
              : "Ex. : atelier_theoa_test"
          }
          pattern={
            engine === "postgres"
              ? "[a-zA-Z][a-zA-Z0-9_]{0,62}"
              : "[a-zA-Z][a-zA-Z0-9_]{0,63}"
          }
          maxLength={engine === "postgres" ? 63 : 64}
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
