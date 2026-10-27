import { useState } from "react";
import { request, databasePath } from "../services/api";
import Feedback from "./Feedback";
// Affiche le bouton de sauvegarde de la base MySQL et le résultat de la demande.
function CreateBackup({ database, onCreated }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Demande une sauvegarde au serveur et avertit le parent pour actualiser l’historique.
  async function handleBackup() {
    if (loading) return;

    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const data = await request(
        `${databasePath(database)}/backups`,
        { method: "POST" }
      );

      setSuccess(`${data.message} : ${data.filename}`);
      onCreated?.();
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="backup-actions">
      <button
        type="button"
        className="action-button"
        disabled={loading}
        onClick={handleBackup}
      >
        {loading ? "Sauvegarde en cours…" : "Sauvegarder la base"}
      </button>

      <Feedback error={error} success={success} />
    </div>
  );
}

export default CreateBackup;