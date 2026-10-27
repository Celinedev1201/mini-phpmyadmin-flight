import { useState } from "react";
import { request, databasePath } from "../../services/api";
import Feedback from "../common/Feedback";
// Affiche le bouton de sauvegarde de la base MySQL et le résultat de la demande.
// database indique quelle base MySQL exporter. loading bloque le bouton pendant la demande. onCreated avertit le
// parent après réussite pour actualiser la liste des sauvegardes.
function CreateBackup({ database, onCreated }) {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  // Demande une sauvegarde au serveur et avertit le parent pour actualiser l’historique.
  // La demande POST lance la sauvegarde de la base sélectionnée. Pendant son traitement, loading ou saving bloque
  // les doubles clics. Après réussite, le message est affiché et le parent est prévenu pour recharger
  // l’historique.
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